
<!DOCTYPE html>
<html lang="en">
<head>
  <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WMSU Attendance Tracking</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css" />
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
  <?php include 'sidebar.php'; ?>
  <div class="main">
    <header>
      <h2>Wmsu Attendance Tracking</h2>
      <div class="admin-info">👤 Admin</div>
    </header>

    <!-- camera preview removed; sections are listed below -->
    
    <!-- Sections listing table (screenshot style) -->
    <div style="margin-top:14px;background:#fff;padding:12px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.05)">
      <h3 style="margin:0 0 8px 0;color:#b71c1c">Sections</h3>
      <div style="overflow:auto;max-height:420px">
        <table class="sections-table" style="width:100%;border-collapse:collapse;font-size:14px">
          <thead style="background:#f7f7f7"><tr><th>Section</th><th>Current Subject</th><th>Time</th><th class="action"></th></tr></thead>
          <tbody>
                        <tr><td>7-Ruby</td><td>Science</td><td>08:00 am - 09:30am</td><td class="action"><a href="livecamera.php?section=7-Ruby" class="small-btn"><i class="fa fa-video" aria-hidden="true"></i>View Live Camera</a></td></tr>
                        <tr><td>8-Mahogany</td><td>Math</td><td>08:00 am - 09:30am</td><td class="action"><a href="livecamera.php?section=8-Mahogany" class="small-btn"><i class="fa fa-video" aria-hidden="true"></i>View Live Camera</a></td></tr>
                        <tr><td>10-Sunflower</td><td>English</td><td>08:00 am - 09:30am</td><td class="action"><a href="livecamera.php?section=10-Sunflower" class="small-btn"><i class="fa fa-video" aria-hidden="true"></i>View Live Camera</a></td></tr>
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
      trackingInterval = setInterval(() => {
        const maxX = liveVideo.offsetWidth - 130;
        const maxY = liveVideo.offsetHeight - 130;
        const randX = Math.floor(Math.random() * maxX);
        const randY = Math.floor(Math.random() * maxY);
        scanner.style.left = `${randX}px`;
        scanner.style.top = `${randY}px`;
      }, 1000);
    }

    toggleBtn.addEventListener('click', () => {
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

    simulateFaceTracking()

    // HLS.js to play .m3u8 stream in browsers
    if (Hls.isSupported()) {
  var hls = new Hls();
  hls.loadSource('http://localhost/stream/index.m3u8');
  hls.attachMedia(liveVideo);
  hls.on(Hls.Events.MANIFEST_PARSED, function () {
    liveVideo.play();
  });
} else if (liveVideo.canPlayType('application/vnd.apple.mpegurl')) {
  liveVideo.src = 'http://localhost/stream/index.m3u8';
  liveVideo.addEventListener('loadedmetadata', function () {
    liveVideo.play();
  });
}


  Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
    faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
    faceapi.nets.faceRecognitionNet.loadFromUri('/models')
  ]).then(startCamera)

  function startCamera() {
    // your existing camera startup code here
    console.log("✅ Models loaded and camera started")
  }
  // Editing is performed on manattendance.php; camera page now links there for manual edits.
  </script>
</body>
</html>
