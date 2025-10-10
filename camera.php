
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
    .main { flex: 1; padding: 20px; box-sizing: border-box; }
    header {
      background-color: #b30000; color: white; padding: 10px 20px;
      border-radius: 8px; display: flex; justify-content: space-between; align-items: center;
    }
    .admin-info { font-weight: bold; }
    .camera-wrapper { display: flex; gap: 20px; margin-top: 20px; height: 520px; }
    .video-section {
      width: 72%; background: white; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      position: relative; padding: 10px; overflow: hidden;
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
    .info-section {
      width: 28%; background: #f9f9f9; border-left: 5px solid #b71c1c;
      border-radius: 10px; padding: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      display: flex; flex-direction: column; justify-content: flex-start; gap: 10px;
      font-size: 14px; max-height: 200px;
    }
    .info-box { margin-bottom: 8px; }
    .info-box h3 { margin-top: 0; margin-bottom: 4px; color: #b71c1c; font-size: 15px; }
    .info-box p { margin: 2px 0; }
    .manual-btn {
      background-color: #b71c1c; color: white; border: none; padding: 10px;
      border-radius: 6px; font-weight: bold; cursor: pointer; text-align: center;
    }
    .manual-btn:hover { background-color: darkred; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main">
    <header>
      <h2>Wmsu Attendance Tracking</h2>
      <div class="admin-info">👤 Admin</div>
    </header>

    <div class="camera-wrapper">
      <div class="video-section">
        <div id="scanner"></div>

        <!-- Live Stream Video -->
        <video id="liveVideo" autoplay muted controls></video>

        <button id="toggleCameraBtn">Turn Off Camera</button>
      </div>

      <div class="info-section">
        <div class="info-box">
          <h3>Student Information</h3>
          <p><strong>Name:</strong> Juan Dela Cruz</p>
          <p><strong>ID:</strong> 2023-00123</p>
          <p><strong>Course:</strong> BS Computer Science</p>
          <p><strong>Year:</strong> 3rd Year</p>
        </div>
        <div class="info-box">
          <h3>Attendance</h3>
          <p><strong>Status:</strong> Present</p>
          <p><strong>Date:</strong> <?php echo date("F d, Y"); ?></p>
          <p><strong>Time In:</strong> <?php echo date("h:i A"); ?></p>
        </div>
        <a href="manattendance.php" class="manual-btn" style="text-decoration: none;">Manual Attendance</a>
      </div>
    </div>
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
  </script>
</body>
</html>
