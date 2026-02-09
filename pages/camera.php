<?php
require_once __DIR__ . '/../auth.php';
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher';
$displayRole = ucfirst($userRole);
$userLabel = ($displayRole === 'Admin') ? 'Admin' : $displayRole;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WMSU Attendance Tracking</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../style.css" />
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      display: flex;
      background-color: #f5f5f5;
    }
  .main { flex: 1; padding: 20px; box-sizing: border-box; overflow:auto; max-height: calc(100vh - 40px); }
    header {
      background-color: #b30000; color: white; padding: 10px 20px;
      border-radius: 8px; display: flex; justify-content: space-between; align-items: center;
    }
    .admin-info { font-weight: bold; }
    .camera-wrapper { display: block; gap: 20px; margin-top: 20px; }
    .video-section {
      width: 100%; background: white; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      position: relative; padding: 10px; overflow: hidden; height: 120px;
    }
    #liveVideo {
      width: 100%; height: 100%; object-fit: cover;
      border-radius: 8px; border: 2px solid #b71c1c;
    }
    #scanner {
      position: absolute; border: 3px solid #00ff00;
      width: 120px; height: 120px; pointer-events: none;
      transition: all 0.2s ease; z-index: 2;
    }
    #toggleCameraBtn {
      position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
      padding: 8px 16px; background-color: #b71c1c; color: white; border: none;
      border-radius: 6px; font-weight: bold; cursor: pointer; z-index: 2;
    }
    /* removed right info column; manual button will be placed below */
    .manual-btn {
      background-color: #b71c1c; color: white; border: none; padding: 10px 16px;
      border-radius: 6px; font-weight: bold; cursor: pointer; text-align: center; display:inline-block;
    }
    .manual-btn:hover { background-color: darkred; }
    /* Sections table alignment tweaks */
    table.sections-table th { text-align: left; padding: 8px; border-bottom: 1px solid #eee; }
    table.sections-table th.action { text-align: right; }
    table.sections-table td { padding: 8px; border-bottom: 1px solid #eee; text-align: left; }
    table.sections-table td.action { text-align: right; }
    /* small button for actions */
    .small-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
      background: #b30000;
      color: #fff !important;
      padding: 6px 10px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      box-shadow: 0 4px 10px rgba(179,0,0,0.12);
      transition: transform .12s ease, box-shadow .12s ease, background .12s ease;
    }
    .small-btn:hover {
      background: #990000;
      transform: translateY(-2px);
      box-shadow: 0 8px 18px rgba(0,0,0,0.12);
    }
  /* inline-flex gap handles icon spacing */
  .small-btn i { vertical-align: middle; }
    table.sections-table th.action { width: 160px; }
  </style>
</head>
<body>
  <?php include '../sidebar.php'; ?>
  <div class="main">
    <header>
      <h2>Wmsu Attendance Tracking</h2>
      <div class="header-actions">
        <a href="/attendance-monitoring/pages/notifications.php" class="notif-btn" aria-label="Notifications" title="Notifications">
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
            <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
          </svg>
          <span class="notif-count"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <div class="admin-info">👤 <?php echo htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    </header>

    <!-- camera preview removed; sections are listed below -->
    
    <!-- Sections listing table (screenshot style) - only Grade 7 as requested -->
    <div style="margin-top:14px;background:#fff;padding:12px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.05)">
      <h3 style="margin:0 0 8px 0;color:#b71c1c">Grade 7 Sections</h3>
      <div style="overflow:auto;max-height:420px">
        <table class="sections-table" style="width:100%;border-collapse:collapse;font-size:14px">
          <thead style="background:#f7f7f7"><tr><th>Section</th><th>Time</th><th class="action"></th></tr></thead>
          <tbody>
            <tr>
              <td>7-Ruby</td>
              <td class="time-cell"></td>
              <td class="action"><a href="livecamera.php?year=7&amp;section=7-Ruby" class="small-btn">View List</a></td>
            </tr>
            <tr>
              <td>7-Mahogany</td>
              <td class="time-cell"></td>
              <td class="action"><a href="livecamera.php?year=7&amp;section=7-Mahogany" class="small-btn">View List</a></td>
            </tr>
            <tr>
              <td>7-Sunflower</td>
              <td class="time-cell"></td>
              <td class="action"><a href="livecamera.php?year=7&amp;section=7-Sunflower" class="small-btn">View List</a></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

        <!-- Grade 8 Sections (UI-only) - same style as Grade 7 -->
        <div style="margin-top:14px;background:#fff;padding:12px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.05)">
          <h3 style="margin:0 0 8px 0;color:#b71c1c">Grade 8 Sections</h3>
          <div style="overflow:auto;max-height:420px">
            <table class="sections-table" style="width:100%;border-collapse:collapse;font-size:14px">
              <thead style="background:#f7f7f7"><tr><th>Section</th><th>Time</th><th class="action"></th></tr></thead>
              <tbody>
                <tr>
                  <td>8-Ruby</td>
                  <td class="time-cell"></td>
                  <td class="action"><a href="livecamera.php?year=8&amp;section=8-Ruby" class="small-btn">View List</a></td>
                </tr>
                <tr>
                  <td>8-Mahogany</td>
                  <td class="time-cell"></td>
                  <td class="action"><a href="livecamera.php?year=8&amp;section=8-Mahogany" class="small-btn">View List</a></td>
                </tr>
                <tr>
                  <td>8-Sunflower</td>
                  <td class="time-cell"></td>
                  <td class="action"><a href="livacamera.php?year=8&amp;section=8-Sunflower" class="small-btn">View List</a></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Editing is done in manattendance.php; edit modal removed from camera page -->
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
  <script>
  const scanner = document.getElementById('scanner');
  const toggleBtn = document.getElementById('toggleCameraBtn');
  const liveVideo = document.getElementById('liveVideo');
    let trackingInterval;
    let cameraOn = true;

    function simulateFaceTracking() {
      // require liveVideo and scanner present
      if (!liveVideo || !scanner) return;
      trackingInterval = setInterval(() => {
        const maxX = Math.max(0, liveVideo.offsetWidth - 130);
        const maxY = Math.max(0, liveVideo.offsetHeight - 130);
        const randX = Math.floor(Math.random() * (maxX + 1));
        const randY = Math.floor(Math.random() * (maxY + 1));
        try {
          scanner.style.left = `${randX}px`;
          scanner.style.top = `${randY}px`;
        } catch (e) { /* ignore if styling fails */ }
      }, 1000);
    }

    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => {
        if (!liveVideo) return;
        if (cameraOn) {
          liveVideo.style.display = "none";
          clearInterval(trackingInterval);
          toggleBtn.textContent = "Turn On Camera";
          cameraOn = false;
        } else {
          liveVideo.style.display = "block";
          simulateFaceTracking();
          toggleBtn.textContent = "Turn Off Camera";
          cameraOn = true;
        }
      });
    }

    // start simulated tracking only when liveVideo exists
    simulateFaceTracking();

    // HLS.js to play .m3u8 stream in browsers (only if liveVideo exists)
    if (liveVideo) {
      if (Hls.isSupported()) {
        var hls = new Hls();
        hls.loadSource('http://localhost/stream/index.m3u8');
        hls.attachMedia(liveVideo);
        hls.on(Hls.Events.MANIFEST_PARSED, function () {
          liveVideo.play().catch(() => {});
        });
      } else if (liveVideo.canPlayType && liveVideo.canPlayType('application/vnd.apple.mpegurl')) {
        liveVideo.src = 'http://localhost/stream/index.m3u8';
        liveVideo.addEventListener('loadedmetadata', function () {
          liveVideo.play().catch(() => {});
        });
      }
    }


  // Load face-api.js models from the local `models/` folder relative to this page.
  // Use a relative path so the files resolve to e.g. /attendance-monitoring/models when served from /attendance-monitoring/camera.php
  Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri('./models'),
    faceapi.nets.faceLandmark68Net.loadFromUri('./models'),
    faceapi.nets.faceRecognitionNet.loadFromUri('./models')
  ]).then(startCamera).catch(err => {
    console.error('Failed to load face-api models from ./models:', err);
  })

  function startCamera() {
    // your existing camera startup code here
    console.log("✅ Models loaded and camera started")
  }
  
  // Render the user's local machine time in the Time column (updates every second)
  (function renderLocalMachineTime(){
    const opts = { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true };
    function updateLocalTime() {
      const now = new Date();
      const timeStr = now.toLocaleTimeString([], opts);
      const rows = document.querySelectorAll('table.sections-table tbody tr');
      rows.forEach(r => {
        // Prefer explicit .time-cell if present, otherwise use the second <td>
        let td = r.querySelector('td.time-cell');
        if (!td) {
          const tds = r.querySelectorAll('td');
          td = tds && tds.length >= 2 ? tds[1] : null;
        }
        if (td) td.textContent = timeStr;
      });
    }
    updateLocalTime();
    setInterval(updateLocalTime, 1000);
  })();
  // Editing is performed on manattendance.php; camera page now links there for manual edits.
  </script>
</body>
</html>
