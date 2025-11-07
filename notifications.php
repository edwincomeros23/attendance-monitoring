<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: signin.php");
    exit;
}
require_once 'db.php';

// For now use sample notifications. Later we can pull from a notifications table.
$notifications = [
    ['id'=>1,'title'=>'Juan Dela Cruz left the room','detail'=>'Subject: Math — Grade 7 Ruby','section'=>'Ruby','time'=>'Oct 19, 2025 — 10:12 AM'],
    ['id'=>2,'title'=>'Jane Smith left the room','detail'=>'Subject: Science — Grade 8 Emerald','section'=>'Emerald','time'=>'Oct 19, 2025 — 9:48 AM'],
];
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Notifications</title>
<link rel="stylesheet" href="style.css">
<style>
.main{flex:1;padding:20px;box-sizing:border-box}
header{background:#b30000;color:#fff;padding:10px 20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center}
.notif-list{background:#fff;border-radius:8px;padding:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
.notif-item{padding:12px;border-bottom:1px solid #f2f2f2}
.notif-item:last-child{border-bottom:none}
.notif-item a{color:#b30000;text-decoration:none;font-weight:700}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main">
  <header>
    <h2>Notifications</h2>
    <div><a href="dashboard.php" style="color:#fff;text-decoration:none">← Back</a></div>
  </header>
  <section style="margin-top:12px">
    <div class="notif-list">
      <?php foreach($notifications as $n): ?>
        <div class="notif-item">
          <a href="studentattendance.php?section=<?php echo urlencode($n['section']); ?>"><?php echo htmlspecialchars($n['title'],ENT_QUOTES); ?></a>
          <div style="color:#666;font-size:13px;margin-top:6px"><?php echo htmlspecialchars($n['detail'],ENT_QUOTES); ?></div>
          <div style="color:#999;font-size:12px;margin-top:6px"><?php echo htmlspecialchars($n['time'],ENT_QUOTES); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
</body>
</html>