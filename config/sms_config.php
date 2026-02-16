<?php
/**
 * SMS Configuration File
 * Configure Semaphore SMS API settings here
 */

// Semaphore API Key - Get from https://semaphore.co
define('SEMAPHORE_API_KEY', 'fa2412ea7df0eec96e13ff58abc91c96');

// Enable/Disable SMS Notifications
define('SMS_ENABLED', true);

// Send SMS on Time-In
define('SMS_SEND_TIME_IN', true);

// Send SMS on Time-Out
define('SMS_SEND_TIME_OUT', true);

// Debug Mode (logs but doesn't send SMS)
define('SMS_DEBUG_MODE', false);

// Throttle period in minutes (prevent duplicate SMS)
define('SMS_THROTTLE_MINUTES', 60);

// SMS Message Templates
define('SMS_TIME_IN_MESSAGE', 'Good day! Your child {STUDENT_NAME} ({STUDENT_ID}) has arrived at school at {TIME}. - WMSU Attendance System');
define('SMS_TIME_OUT_MESSAGE', 'Good day! Your child {STUDENT_NAME} ({STUDENT_ID}) has left school at {TIME}. - WMSU Attendance System');

// Email Notifications (Gmail SMTP)
define('EMAIL_ENABLED', true);
define('EMAIL_DEBUG_MODE', false);
define('EMAIL_FROM_ADDRESS', 'yourgmail@gmail.com');
define('EMAIL_FROM_NAME', 'WMSU Attendance System');
define('EMAIL_APP_PASSWORD', 'edwin01');
define('EMAIL_SUBJECT_TIME_IN', 'Student Arrival Notification');
define('EMAIL_SUBJECT_TIME_OUT', 'Student Departure Notification');
define('EMAIL_TIME_IN_MESSAGE', 'Good day! Your child {STUDENT_NAME} ({STUDENT_ID}) has arrived at school at {TIME}.');
define('EMAIL_TIME_OUT_MESSAGE', 'Good day! Your child {STUDENT_NAME} ({STUDENT_ID}) has left school at {TIME}.');

// Telegram Notifications
define('TELEGRAM_ENABLED', true);
define('TELEGRAM_DEBUG_MODE', false);
define('TELEGRAM_BOT_TOKEN', '7965118798:AAEq2FYUzDNzGlJV-_A67OGSLnktsN5kv0Q');
define('TELEGRAM_CHAT_ID', '6826928347');
define('TELEGRAM_TIME_IN_MESSAGE', 'Good day! Your child {STUDENT_NAME} ({STUDENT_ID}) has arrived at school at {TIME}.');
define('TELEGRAM_TIME_OUT_MESSAGE', 'Good day! Your child {STUDENT_NAME} ({STUDENT_ID}) has left school at {TIME}.');
