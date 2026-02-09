<?php
/**
 * Email Functions Library (Gmail SMTP)
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/sms_config.php';
require_once __DIR__ . '/sms_functions.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmailNotification($toEmail, $subject, $bodyText) {
    if (!EMAIL_ENABLED) {
        return ['success' => false, 'message' => 'Email is disabled'];
    }

    if (EMAIL_DEBUG_MODE) {
        return ['success' => true, 'message' => 'DEBUG MODE: Email logged but not sent', 'debug' => true];
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return ['success' => false, 'message' => 'PHPMailer is not installed. Run composer install.'];
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_FROM_ADDRESS;
        $mail->Password = EMAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($toEmail);

        $mail->Subject = $subject;
        $mail->Body = $bodyText;
        $mail->AltBody = $bodyText;

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email send failed', 'response' => $mail->ErrorInfo];
    }
}

function sendAttendanceEmailNotification($studentId, $studentName, $studentIdNumber, $eventType) {
    global $conn;

    // Check if this event type is enabled (reuse SMS toggles for time in/out)
    if ($eventType == 'time_in' && !SMS_SEND_TIME_IN) {
        return false;
    }
    if ($eventType == 'time_out' && !SMS_SEND_TIME_OUT) {
        return false;
    }

    $stmt = $conn->prepare("SELECT guardian_email FROM students WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if (!$student || empty($student['guardian_email'])) {
        return false;
    }

    $email = $student['guardian_email'];
    $time = date('g:i A');
    $subject = $eventType == 'time_in' ? EMAIL_SUBJECT_TIME_IN : EMAIL_SUBJECT_TIME_OUT;
    $template = $eventType == 'time_in' ? EMAIL_TIME_IN_MESSAGE : EMAIL_TIME_OUT_MESSAGE;

    $message = str_replace(
        ['{STUDENT_NAME}', '{STUDENT_ID}', '{TIME}'],
        [$studentName, $studentIdNumber, $time],
        $template
    );

    $result = sendEmailNotification($email, $subject, $message);

    $status = $result['success'] ? 'sent' : 'failed';
    $response = isset($result['response']) ? $result['response'] : $result['message'];
    logNotification($studentId, 'email', $email, $eventType, $message, $status, $response);

    return $result['success'];
}
