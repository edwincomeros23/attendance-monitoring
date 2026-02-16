<?php
/**
 * SMS Functions Library
 * Contains all SMS-related helper functions
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/sms_config.php';

/**
 * Send SMS via Semaphore API
 */
function sendSMS($phoneNumber, $message) {
    if (!SMS_ENABLED) {
        return ['success' => false, 'message' => 'SMS is disabled'];
    }

    // Format phone number to international format
    $phoneNumber = formatPhoneNumber($phoneNumber);
    
    // Debug mode - log but don't send
    if (SMS_DEBUG_MODE) {
        return [
            'success' => true,
            'message' => 'DEBUG MODE: SMS logged but not sent',
            'debug' => true
        ];
    }

    // Send via Semaphore API
    $ch = curl_init();
    $parameters = array(
        'apikey' => SEMAPHORE_API_KEY,
        'number' => $phoneNumber,
        'message' => $message,
        'sendername' => 'SEMAPHORE'
    );
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'response' => $response
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send SMS',
            'response' => $response,
            'http_code' => $httpCode
        ];
    }
}

/**
 * Format phone number to international format
 */
function formatPhoneNumber($phone) {
    // Remove spaces, dashes, parentheses
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert 09XX to 639XX
    if (substr($phone, 0, 1) == '0') {
        $phone = '63' . substr($phone, 1);
    }
    
    // Add 63 prefix if not present
    if (substr($phone, 0, 2) != '63') {
        $phone = '63' . $phone;
    }
    
    return $phone;
}

/**
 * Check if SMS can be sent (throttle check)
 */
function canSendSMS($studentId, $eventType) {
    global $conn;
    
    $throttleMinutes = SMS_THROTTLE_MINUTES;
    $stmt = $conn->prepare("
        SELECT created_at 
        FROM notification_logs 
        WHERE student_id = ? 
        AND channel = 'sms'
        AND event_type = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->bind_param("isi", $studentId, $eventType, $throttleMinutes);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows == 0;
}

/**
 * Check if Telegram can be sent (throttle check)
 */
function canSendTelegram($studentId, $eventType) {
    global $conn;

    $throttleMinutes = SMS_THROTTLE_MINUTES;
    $stmt = $conn->prepare("
        SELECT created_at 
        FROM notification_logs 
        WHERE student_id = ? 
        AND channel = 'telegram'
        AND event_type = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ORDER BY created_at DESC 
        LIMIT 1
    ");

    $stmt->bind_param("isi", $studentId, $eventType, $throttleMinutes);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows == 0;
}

/**
 * Log notification to database
 */
function logNotification($studentId, $channel, $recipient, $eventType, $message, $status, $response = '') {
    global $conn;

    $stmt = $conn->prepare("
        INSERT INTO notification_logs 
        (student_id, channel, recipient, event_type, message, status, response, sent_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("issssss", $studentId, $channel, $recipient, $eventType, $message, $status, $response);
    return $stmt->execute();
}

/**
 * Log SMS notification to database (backwards compatible wrapper)
 */
function logSMSNotification($studentId, $phoneNumber, $eventType, $message, $status, $response = '') {
    return logNotification($studentId, 'sms', $phoneNumber, $eventType, $message, $status, $response);
}

/**
 * Send attendance notification
 */
function sendAttendanceNotification($studentId, $studentName, $studentIdNumber, $eventType) {
    global $conn;
    
    // Check if this event type is enabled
    if ($eventType == 'time_in' && !SMS_SEND_TIME_IN) {
        return false;
    }
    if ($eventType == 'time_out' && !SMS_SEND_TIME_OUT) {
        return false;
    }
    
    // Check throttle
    if (!canSendSMS($studentId, $eventType)) {
        return false;
    }
    
    // Get student phone number
    $stmt = $conn->prepare("SELECT phone_no FROM students WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    if (!$student || empty($student['phone_no'])) {
        return false;
    }
    
    $phoneNumber = $student['phone_no'];
    
    // Prepare message
    $time = date('g:i A');
    $template = $eventType == 'time_in' ? SMS_TIME_IN_MESSAGE : SMS_TIME_OUT_MESSAGE;
    $message = str_replace(
        ['{STUDENT_NAME}', '{STUDENT_ID}', '{TIME}'],
        [$studentName, $studentIdNumber, $time],
        $template
    );
    
    // Send SMS
    $result = sendSMS($phoneNumber, $message);
    
    // Log to database
    $status = $result['success'] ? 'sent' : 'failed';
    $response = isset($result['response']) ? $result['response'] : $result['message'];
    logSMSNotification($studentId, $phoneNumber, $eventType, $message, $status, $response);
    
    return $result['success'];
}

/**
 * Send Telegram message
 */
function sendTelegramMessage($chatId, $message) {
    if (!TELEGRAM_ENABLED) {
        return ['success' => false, 'message' => 'Telegram is disabled'];
    }

    if (TELEGRAM_DEBUG_MODE) {
        return [
            'success' => true,
            'message' => 'DEBUG MODE: Telegram logged but not sent',
            'debug' => true
        ];
    }

    if (TELEGRAM_BOT_TOKEN === '' || $chatId === '') {
        return ['success' => false, 'message' => 'Telegram token or chat id missing'];
    }

    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    $payload = [
        'chat_id' => $chatId,
        'text' => $message,
        'disable_web_page_preview' => true
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'message' => 'Telegram sent', 'response' => $response];
    }
    return ['success' => false, 'message' => 'Telegram send failed', 'response' => $response, 'http_code' => $httpCode];
}

/**
 * Send attendance notification via Telegram
 */
function sendAttendanceTelegramNotification($studentId, $studentName, $studentIdNumber, $eventType) {
    if (!TELEGRAM_ENABLED) {
        return false;
    }

    if ($eventType == 'time_in' && !SMS_SEND_TIME_IN) {
        return false;
    }
    if ($eventType == 'time_out' && !SMS_SEND_TIME_OUT) {
        return false;
    }

    if (!canSendTelegram($studentId, $eventType)) {
        return false;
    }

    $time = date('g:i A');
    $template = $eventType == 'time_in' ? TELEGRAM_TIME_IN_MESSAGE : TELEGRAM_TIME_OUT_MESSAGE;
    $message = str_replace(
        ['{STUDENT_NAME}', '{STUDENT_ID}', '{TIME}'],
        [$studentName, $studentIdNumber, $time],
        $template
    );

    $result = sendTelegramMessage(TELEGRAM_CHAT_ID, $message);

    $status = $result['success'] ? 'sent' : 'failed';
    $response = isset($result['response']) ? $result['response'] : $result['message'];
    logNotification($studentId, 'telegram', TELEGRAM_CHAT_ID, $eventType, $message, $status, $response);

    return $result['success'];
}
