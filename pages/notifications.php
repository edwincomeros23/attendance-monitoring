<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: /attendance-monitoring/auth/signin.php");
    exit;
}
require_once '../db.php';

// Load notifications from logs (SMS + Email)
$notifications = [];
$sql = "SELECT nl.student_id, nl.channel, nl.event_type, nl.status, nl.created_at, s.full_name, s.student_id AS student_code
    FROM notification_logs nl
    LEFT JOIN students s ON nl.student_id = s.id
    ORDER BY nl.created_at DESC";
if ($res = $conn->query($sql)) {
  while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
  }
  $res->free();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Notifications</title>
<link rel="icon" type="image/png" href="../wmsulogo_circular.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
<link rel="stylesheet" href="../style.css">
<style>
.main{flex:1;padding:20px;box-sizing:border-box;margin-left:220px;height:100vh;overflow-y:auto}
body{margin:0;display:flex;overflow:hidden}
.sidebar{position:fixed;left:0;top:0;height:100vh;overflow-y:auto;z-index:100}
header{background:#b30000;color:#fff;padding:10px 20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center}
.notif-list{background:#fff;border-radius:8px;padding:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
.notif-item{padding:12px;border-bottom:1px solid #f2f2f2}
.notif-item:last-child{border-bottom:none}
.notif-item a{color:#b30000;text-decoration:none;font-weight:700}
</style>
</head>
<body>
<?php include '../sidebar.php'; ?>
<div class="main">
  <header>
    <h2>Notifications</h2>
    <div class="header-actions">
      <a href="/attendance-monitoring/pages/notifications.php" class="notif-btn" aria-label="Notifications" title="Notifications">
        <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
          <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
        </svg>
        <span class="notif-count"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
      </a>
      <a href="dashboard.php" style="color:#fff;text-decoration:none">← Back</a>
    </div>
  </header>
  <section style="margin-top:12px">
    <div class="notif-list">
      <?php if (empty($notifications)): ?>
        <div style="padding:12px;color:#666">No notifications yet.</div>
      <?php else: ?>
        <?php foreach ($notifications as $n): ?>
          <?php
            $studentName = isset($n['full_name']) && $n['full_name'] !== '' ? $n['full_name'] : 'Unknown Student';
            $studentCode = isset($n['student_code']) ? $n['student_code'] : '';
            $eventType = isset($n['event_type']) ? $n['event_type'] : '';
            $eventText = ($eventType === 'time_out') ? 'left school' : 'arrived at school';
            $channel = isset($n['channel']) ? strtoupper($n['channel']) : 'SMS';
            $status = isset($n['status']) ? ucfirst($n['status']) : 'Sent';
            $createdAt = !empty($n['created_at']) ? date('M j, Y - h:i A', strtotime($n['created_at'])) : '';
            $title = $studentName . ' ' . $eventText;
            if ($studentCode !== '') {
              $title .= ' (' . $studentCode . ')';
            }
            $detail = $channel . ' | ' . $status;
          ?>
          <div class="notif-item">
            <div style="font-weight:700"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
            <div style="color:#666;font-size:13px;margin-top:6px"><?php echo htmlspecialchars($detail, ENT_QUOTES, 'UTF-8'); ?></div>
            <div style="color:#999;font-size:12px;margin-top:6px"><?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</div>
</body>
</html>