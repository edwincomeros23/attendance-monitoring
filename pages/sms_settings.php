<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /attendance-monitoring/pages/dashboard.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $configFile = __DIR__ . '/../config/sms_config.php';
    $configContent = file_get_contents($configFile);
    
    $apiKey = isset($_POST['api_key']) ? trim($_POST['api_key']) : 'YOUR_SEMAPHORE_API_KEY';
    $smsEnabled = isset($_POST['sms_enabled']) ? 'true' : 'false';
    $sendTimeIn = isset($_POST['send_time_in']) ? 'true' : 'false';
    $sendTimeOut = isset($_POST['send_time_out']) ? 'true' : 'false';
    $debugMode = isset($_POST['debug_mode']) ? 'true' : 'false';
    $throttleMinutes = isset($_POST['throttle_minutes']) ? intval($_POST['throttle_minutes']) : 60;
    $emailEnabled = isset($_POST['email_enabled']) ? 'true' : 'false';
    $emailDebugMode = isset($_POST['email_debug_mode']) ? 'true' : 'false';
    $emailFromAddress = isset($_POST['email_from_address']) ? trim($_POST['email_from_address']) : '';
    $emailFromName = isset($_POST['email_from_name']) ? trim($_POST['email_from_name']) : '';
    $emailAppPassword = isset($_POST['email_app_password']) ? trim($_POST['email_app_password']) : '';
    $telegramEnabled = isset($_POST['telegram_enabled']) ? 'true' : 'false';
    $telegramDebugMode = isset($_POST['telegram_debug_mode']) ? 'true' : 'false';
    $telegramBotToken = isset($_POST['telegram_bot_token']) ? trim($_POST['telegram_bot_token']) : '';
    $telegramChatId = isset($_POST['telegram_chat_id']) ? trim($_POST['telegram_chat_id']) : '';
    
    $configContent = preg_replace("/define\('SEMAPHORE_API_KEY', '.*?'\);/", "define('SEMAPHORE_API_KEY', '$apiKey');", $configContent);
    $configContent = preg_replace("/define\('SMS_ENABLED', .*?\);/", "define('SMS_ENABLED', $smsEnabled);", $configContent);
    $configContent = preg_replace("/define\('SMS_SEND_TIME_IN', .*?\);/", "define('SMS_SEND_TIME_IN', $sendTimeIn);", $configContent);
    $configContent = preg_replace("/define\('SMS_SEND_TIME_OUT', .*?\);/", "define('SMS_SEND_TIME_OUT', $sendTimeOut);", $configContent);
    $configContent = preg_replace("/define\('SMS_DEBUG_MODE', .*?\);/", "define('SMS_DEBUG_MODE', $debugMode);", $configContent);
    $configContent = preg_replace("/define\('SMS_THROTTLE_MINUTES', .*?\);/", "define('SMS_THROTTLE_MINUTES', $throttleMinutes);", $configContent);
    $configContent = preg_replace("/define\('EMAIL_ENABLED', .*?\);/", "define('EMAIL_ENABLED', $emailEnabled);", $configContent);
    $configContent = preg_replace("/define\('EMAIL_DEBUG_MODE', .*?\);/", "define('EMAIL_DEBUG_MODE', $emailDebugMode);", $configContent);
    $configContent = preg_replace("/define\('EMAIL_FROM_ADDRESS', '.*?'\);/", "define('EMAIL_FROM_ADDRESS', '$emailFromAddress');", $configContent);
    $configContent = preg_replace("/define\('EMAIL_FROM_NAME', '.*?'\);/", "define('EMAIL_FROM_NAME', '$emailFromName');", $configContent);
    $configContent = preg_replace("/define\('EMAIL_APP_PASSWORD', '.*?'\);/", "define('EMAIL_APP_PASSWORD', '$emailAppPassword');", $configContent);
    $configContent = preg_replace("/define\('TELEGRAM_ENABLED', .*?\);/", "define('TELEGRAM_ENABLED', $telegramEnabled);", $configContent);
    $configContent = preg_replace("/define\('TELEGRAM_DEBUG_MODE', .*?\);/", "define('TELEGRAM_DEBUG_MODE', $telegramDebugMode);", $configContent);
    $configContent = preg_replace("/define\('TELEGRAM_BOT_TOKEN', '.*?'\);/", "define('TELEGRAM_BOT_TOKEN', '$telegramBotToken');", $configContent);
    $configContent = preg_replace("/define\('TELEGRAM_CHAT_ID', '.*?'\);/", "define('TELEGRAM_CHAT_ID', '$telegramChatId');", $configContent);
    
    if (file_put_contents($configFile, $configContent)) {
        $message = '<div class="alert-success"><i class="fas fa-check-circle"></i> Settings saved successfully!</div>';
    } else {
        $message = '<div class="alert-error"><i class="fas fa-times-circle"></i> Failed to save settings!</div>';
    }
}

require_once __DIR__ . '/../config/sms_config.php';

$statsQuery = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed FROM notification_logs";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult ? $statsResult->fetch_assoc() : ['total' => 0, 'sent' => 0, 'failed' => 0];

$recentQuery = "SELECT nl.*, s.full_name, s.student_id FROM notification_logs nl LEFT JOIN students s ON nl.student_id = s.id ORDER BY nl.created_at DESC LIMIT 15";
$recentResult = $conn->query($recentQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS & Email Notifications</title>
    <link rel="icon" type="image/png" href="../wmsulogo_circular.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Override and extend main styles for SMS settings page */
        body {
            overflow: hidden;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .main {
            background: #f4f4f4;
            padding: 30px;
            margin-left: 220px;
            height: 100vh;
            overflow-y: auto;
        }

        .page-title {
            background: linear-gradient(135deg, #b71c1c 0%, #8a0000 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .page-title h1 {
            margin: 0 0 5px 0;
            font-size: 28px;
            font-weight: 700;
        }

        .page-title p {
            margin: 0;
            font-size: 14px;
            opacity: 0.95;
        }

        .alert-success, .alert-error {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            border-left: 5px solid #b71c1c;
        }

        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 44px;
            font-weight: 700;
            color: #b71c1c;
            margin: 0;
        }

        .settings-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin: 0 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 3px solid #b71c1c;
        }

        .section-header {
            font-size: 15px;
            font-weight: 700;
            color: #333;
            margin: 25px 0 15px 0;
        }

        .form-section {
            background: #fafafa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #b71c1c;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            max-width: 500px;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #b71c1c;
            box-shadow: 0 0 0 3px rgba(183,28,28,0.1);
        }

        .form-help {
            font-size: 12px;
            color: #888;
            margin-top: 6px;
        }

        .form-help a {
            color: #b71c1c;
            text-decoration: none;
            font-weight: 600;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .checkbox-item {
            display: flex;
            gap: 12px;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s;
        }

        .checkbox-item:hover {
            border-color: #b71c1c;
            background: #fff9f9;
        }

        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #b71c1c;
            flex-shrink: 0;
        }

        .checkbox-label {
            flex: 1;
            cursor: pointer;
        }

        .checkbox-label strong {
            display: block;
            color: #333;
            font-size: 14px;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .checkbox-desc {
            font-size: 12px;
            color: #888;
        }

        .btn {
            display: inline-block;
            padding: 10px 24px;
            background: #b71c1c;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        .btn:hover {
            background: #8a0000;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .btn:active {
            transform: translateY(0);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: #fafafa;
            padding: 12px;
            text-align: left;
            font-weight: 700;
            border-bottom: 2px solid #b71c1c;
            color: #333;
            font-size: 13px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        tbody tr:hover {
            background: #fafafa;
        }

        .status-sent {
            color: #28a745;
            font-weight: 600;
        }

        .status-failed {
            color: #dc3545;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .checkbox-grid {
                grid-template-columns: 1fr;
            }

            .form-group input[type="text"],
            .form-group input[type="number"] {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    
    <div class="main">
        <div class="page-title">
            <h1><i class="fas fa-bell"></i> SMS & Email Notifications</h1>
            <p>Configure guardian notifications via SMS, Gmail, and Telegram</p>
        </div>

        <?php if ($message) echo $message; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total Notifications</div>
                <div class="stat-number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Successfully Sent</div>
                <div class="stat-number"><?php echo $stats['sent']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Failed</div>
                <div class="stat-number"><?php echo $stats['failed']; ?></div>
            </div>
        </div>

        <!-- Settings Card -->
        <div class="settings-card">
            <h2 class="card-title"><i class="fas fa-cog"></i> Settings</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">

                <!-- API Key Section -->
                <div class="form-section">
                    <div class="form-group">
                        <label for="api_key"><i class="fas fa-key"></i> Semaphore API Key</label>
                        <input type="text" id="api_key" name="api_key" value="<?php echo htmlspecialchars(SEMAPHORE_API_KEY); ?>" placeholder="Paste your API key">
                        <div class="form-help">Get a free account at <a href="https://semaphore.co/" target="_blank">semaphore.co</a></div>
                    </div>
                </div>

                <!-- Gmail Section -->
                <div class="section-header"><i class="fas fa-envelope"></i> Gmail Settings</div>
                <div class="form-section">
                    <div class="form-group">
                        <label for="email_from_address">Gmail Address (Sender)</label>
                        <input type="text" id="email_from_address" name="email_from_address" value="<?php echo htmlspecialchars(EMAIL_FROM_ADDRESS); ?>" placeholder="yourgmail@gmail.com">
                    </div>
                    <div class="form-group">
                        <label for="email_from_name">Sender Name</label>
                        <input type="text" id="email_from_name" name="email_from_name" value="<?php echo htmlspecialchars(EMAIL_FROM_NAME); ?>" placeholder="WMSU Attendance System">
                    </div>
                    <div class="form-group">
                        <label for="email_app_password">Gmail App Password</label>
                        <input type="password" id="email_app_password" name="email_app_password" value="<?php echo htmlspecialchars(EMAIL_APP_PASSWORD); ?>" placeholder="App password from Google">
                        <div class="form-help">Use a Gmail App Password (not your normal password). Requires PHPMailer (composer install).</div>
                    </div>
                </div>

                <!-- Telegram Section -->
                <div class="section-header"><i class="fab fa-telegram-plane"></i> Telegram Settings</div>
                <div class="form-section">
                    <div class="form-group">
                        <label for="telegram_bot_token">Bot Token</label>
                        <input type="text" id="telegram_bot_token" name="telegram_bot_token" value="<?php echo htmlspecialchars(TELEGRAM_BOT_TOKEN); ?>" placeholder="123456:ABCDEF...">
                        <div class="form-help">Create a bot with @BotFather to get the token.</div>
                    </div>
                    <div class="form-group">
                        <label for="telegram_chat_id">Chat ID</label>
                        <input type="text" id="telegram_chat_id" name="telegram_chat_id" value="<?php echo htmlspecialchars(TELEGRAM_CHAT_ID); ?>" placeholder="e.g., 123456789 or -100...">
                        <div class="form-help">Send a message to your bot, then use @userinfobot to get your chat ID.</div>
                    </div>
                </div>

                <!-- Feature Toggles -->
                <div class="section-header"><i class="fas fa-toggle-on"></i> Features</div>
                <div class="checkbox-grid">
                    <div class="checkbox-item">
                        <input type="checkbox" id="sms_enabled" name="sms_enabled" <?php echo SMS_ENABLED ? 'checked' : ''; ?>>
                        <label for="sms_enabled" class="checkbox-label">
                            <strong>Enable SMS Notifications</strong>
                            <div class="checkbox-desc">Master switch - enable/disable all SMS</div>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="email_enabled" name="email_enabled" <?php echo EMAIL_ENABLED ? 'checked' : ''; ?>>
                        <label for="email_enabled" class="checkbox-label">
                            <strong>Enable Gmail Notifications</strong>
                            <div class="checkbox-desc">Send emails to guardian email addresses</div>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="telegram_enabled" name="telegram_enabled" <?php echo TELEGRAM_ENABLED ? 'checked' : ''; ?>>
                        <label for="telegram_enabled" class="checkbox-label">
                            <strong>Enable Telegram Notifications</strong>
                            <div class="checkbox-desc">Send Telegram messages to a chat</div>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="send_time_in" name="send_time_in" <?php echo SMS_SEND_TIME_IN ? 'checked' : ''; ?>>
                        <label for="send_time_in" class="checkbox-label">
                            <strong>Time-In Notifications</strong>
                            <div class="checkbox-desc">Alert when student arrives</div>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="send_time_out" name="send_time_out" <?php echo SMS_SEND_TIME_OUT ? 'checked' : ''; ?>>
                        <label for="send_time_out" class="checkbox-label">
                            <strong>Time-Out Notifications</strong>
                            <div class="checkbox-desc">Alert when student leaves</div>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="debug_mode" name="debug_mode" <?php echo SMS_DEBUG_MODE ? 'checked' : ''; ?>>
                        <label for="debug_mode" class="checkbox-label">
                            <strong>Debug Mode</strong>
                            <div class="checkbox-desc">Log only - no SMS sent (testing)</div>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="email_debug_mode" name="email_debug_mode" <?php echo EMAIL_DEBUG_MODE ? 'checked' : ''; ?>>
                        <label for="email_debug_mode" class="checkbox-label">
                            <strong>Email Debug Mode</strong>
                            <div class="checkbox-desc">Log only - no email sent (testing)</div>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="telegram_debug_mode" name="telegram_debug_mode" <?php echo TELEGRAM_DEBUG_MODE ? 'checked' : ''; ?>>
                        <label for="telegram_debug_mode" class="checkbox-label">
                            <strong>Telegram Debug Mode</strong>
                            <div class="checkbox-desc">Log only - no Telegram sent (testing)</div>
                        </label>
                    </div>
                </div>

                <!-- Throttle Settings -->
                <div class="section-header"><i class="fas fa-hourglass-half"></i> Throttle Settings</div>
                <div class="form-section">
                    <div class="form-group">
                        <label for="throttle">Throttle Period (minutes)</label>
                        <input type="number" id="throttle" name="throttle_minutes" value="<?php echo SMS_THROTTLE_MINUTES; ?>" min="1" max="1440">
                        <div class="form-help">Prevents duplicate SMS to same student within X minutes</div>
                    </div>
                </div>

                <button type="submit" class="btn"><i class="fas fa-save"></i> Save Settings</button>
            </form>
        </div>

        <!-- Recent Notifications -->
        <div class="settings-card">
            <h2 class="card-title"><i class="fas fa-history"></i> Recent Notifications</h2>
            <?php if ($recentResult && $recentResult->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Student</th>
                                <th>Channel</th>
                                <th>Event Type</th>
                                <th>Recipient</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $recentResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, g:i A', strtotime($row['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name'] ?: 'Unknown'); ?></td>
                                <td><?php echo strtoupper(htmlspecialchars($row['channel'])); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $row['event_type'])); ?></td>
                                <td><?php echo htmlspecialchars($row['recipient']); ?></td>
                                <td class="status-<?php echo $row['status']; ?>">
                                    <i class="fas fa-<?php echo $row['status'] === 'sent' ? 'check-circle' : 'times-circle'; ?>"></i>
                                    <?php echo ucfirst($row['status']); ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p><strong>No notifications yet</strong></p>
                    <p>SMS alerts will appear here once sent</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
