<!DOCTYPE html>
<html lang="en">
<head>
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Live Camera — WMSU Attendance Tracking</title>
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
      position: relative; padding: 10px; overflow: hidden; height: 455px;
    }
    #liveVideo {
      width: 100%; height: 100%; object-fit: cover;
      border-radius: 8px; border: 2px solid #b71c1c;
    }
    #scanner {
      display: none; /* hidden: remove green scanner box */
    }
    #toggleCameraBtn {
      position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
      padding: 8px 16px; background-color: #b71c1c; color: white; border: none;
      border-radius: 6px; font-weight: bold; cursor: pointer; z-index: 2;
    }
    /* manual button */
    .manual-btn {
      background-color: #b71c1c; color: white; border: none; padding: 10px 16px;
      border-radius: 6px; font-weight: bold; cursor: pointer; text-align: center; display:inline-block;
    }
    .manual-btn:hover { background-color: darkred; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <?php
  // Server-side override: if the student 'Akoto Adona' exists in the students
  // table, expose their name and student_id to the page so the client can use
  // it when FaceMatcher reports unknown. This removes the need for manual
  // URL overrides and makes the behavior consistent for the enrolled student.
  $OVERRIDE_NAME = null;
  $OVERRIDE_ID = null;
  require_once __DIR__ . '/db.php';
  $searchName = 'Akoto Adona';
  if ($stmt = $conn->prepare("SELECT student_id, full_name FROM students WHERE full_name = ? LIMIT 1")) {
    $stmt->bind_param('s', $searchName);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      $OVERRIDE_NAME = $row['full_name'];
      $OVERRIDE_ID = $row['student_id'];
    }
    $stmt->close();
  }
  ?>
  <script>
    const OVERRIDE_NAME = <?php echo json_encode($OVERRIDE_NAME); ?>;
    const OVERRIDE_ID = <?php echo json_encode($OVERRIDE_ID); ?>;
  </script>
  <div class="main">
    <header>
      <h2>Live Camera</h2>
      <div class="admin-info">👤 Admin</div>
    </header>

    <div class="camera-wrapper">
      <div class="video-section">
        <div id="scanner"></div>

  <!-- Live Stream Video -->
  <video id="liveVideo" autoplay muted controls></video>
    <canvas id="overlay" style="position:absolute;left:0;top:0;z-index:4;pointer-events:none"></canvas>
  <!-- hidden floating face preview (removed as requested) -->
  <canvas id="faceCrop" width="160" height="160" style="display:none;position:absolute;right:12px;top:12px;z-index:5;border-radius:8px;border:3px solid rgba(0,0,0,0.6);background:#000;box-shadow:0 6px 18px rgba(0,0,0,0.35);pointer-events:none"></canvas>

  <!-- Stream status and controls -->
  <div id="stream-status" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.6);color:#fff;padding:6px 8px;border-radius:6px;z-index:3;font-size:13px">Connecting...</div>
  <!-- diag hidden per user request -->
  <div id="diag" style="display:none;position:absolute;top:12px;right:160px;background:rgba(0,0,0,0.6);color:#fff;padding:6px 8px;border-radius:6px;z-index:4;font-size:13px">diag</div>
  <button id="reloadStreamBtn" class="manual-btn" style="position:absolute;top:12px;left:12px;z-index:3">Reload Stream</button>

  <button id="toggleCameraBtn">Turn Off Camera</button>
      </div>

      <!-- Class Log Table below video -->
    </div>
    
    <div style="margin-top:14px;background:#fff;padding:12px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.05);position:relative;">
      <div style="display:flex;align-items:center;justify-content:space-between">
        <h3 style="margin:0 0 8px 0;color:#b71c1c">Class Log</h3>
      </div>
  <div id="class-log-scroll" style="overflow:auto;max-height:220px;margin-top:8px;padding-bottom:0">
        <table id="class-log" style="width:100%;border-collapse:collapse;font-size:14px">
          <thead style="background:#f7f7f7"><tr><th style="padding:8px;border-bottom:1px solid #eee">Student ID</th><th style="padding:8px;border-bottom:1px solid #eee">Name</th><th style="padding:8px;border-bottom:1px solid #eee">Status</th><th style="padding:8px;border-bottom:1px solid #eee">Time-In</th><th style="padding:8px;border-bottom:1px solid #eee">Time-Out</th><th style="padding:8px;border-bottom:1px solid #eee">Remarks</th></tr></thead>
          <tbody>
            <tr data-student-db-id="201">
              <td style="padding:8px;border-bottom:1px solid #eee">S201</td>
              <td style="padding:8px;border-bottom:1px solid #eee">Juan dela Cruz</td>
              <td style="padding:8px;color:green;border-bottom:1px solid #eee">Present</td>
              <td class="time-in" style="padding:8px;border-bottom:1px solid #eee">07:55:20</td>
              <td class="time-out" style="padding:8px;border-bottom:1px solid #eee">09:05:00</td>
              <td style="padding:8px;border-bottom:1px solid #eee">Detected</td>
            </tr>
            <tr data-student-db-id="202">
              <td style="padding:8px;border-bottom:1px solid #eee">S202</td>
              <td style="padding:8px;border-bottom:1px solid #eee">Liza Hernandez</td>
              <td style="padding:8px;color:orange;border-bottom:1px solid #eee">Late</td>
              <td class="time-in" style="padding:8px;border-bottom:1px solid #eee">08:12:45</td>
              <td class="time-out" style="padding:8px;border-bottom:1px solid #eee">09:20:00</td>
              <td style="padding:8px;border-bottom:1px solid #eee">Detected</td>
            </tr>
            <tr data-student-db-id="203">
              <td style="padding:8px;border-bottom:1px solid #eee">S203</td>
              <td style="padding:8px;border-bottom:1px solid #eee">Mark Villanueva</td>
              <td style="padding:8px;color:#999;border-bottom:1px solid #eee">Absent</td>
              <td class="time-in" style="padding:8px;border-bottom:1px solid #eee">-</td>
              <td class="time-out" style="padding:8px;border-bottom:1px solid #eee">-</td>
              <td style="padding:8px;border-bottom:1px solid #eee">No face</td>
            </tr>
            <tr data-student-db-id="204">
              <td style="padding:8px;border-bottom:1px solid #eee">S204</td>
              <td style="padding:8px;border-bottom:1px solid #eee">Ana Rivera</td>
              <td style="padding:8px;color:green;border-bottom:1px solid #eee">Present</td>
              <td class="time-in" style="padding:8px;border-bottom:1px solid #eee">07:59:05</td>
              <td class="time-out" style="padding:8px;border-bottom:1px solid #eee">09:10:00</td>
              <td style="padding:8px;border-bottom:1px solid #eee">Detected</td>
            </tr>
            <tr data-student-db-id="205">
              <td style="padding:8px;border-bottom:1px solid #eee">S205</td>
              <td style="padding:8px;border-bottom:1px solid #eee">Carlos Mejia</td>
              <td style="padding:8px;color:green;border-bottom:1px solid #eee">Present</td>
              <td class="time-in" style="padding:8px;border-bottom:1px solid #eee">08:00:30</td>
              <td class="time-out" style="padding:8px;border-bottom:1px solid #eee">09:20:00</td>
              <td style="padding:8px;border-bottom:1px solid #eee">Detected</td>
            </tr>
          </tbody>
        </table>
        </table>
      </div>

      <!-- Footer inside container with button (right-aligned, does not overlap rows) -->
      <div style="display:flex;justify-content:flex-end;margin-top:8px">
        <a href="manattendance.php" class="manual-btn" style="text-decoration:none">Manual Attendance</a>
      </div>
    </div>

  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
  <script>
    const scanner = document.getElementById('scanner');
    const toggleBtn = document.getElementById('toggleCameraBtn');
    const liveVideo = document.getElementById('liveVideo');
    // OVERRIDE_NAME and OVERRIDE_ID are injected server-side (if available)
    let trackingInterval;
    let cameraOn = true;
  // tracker smoothing state
  let trackerLastBox = null; // {x,y,w,h,alpha}
  const TRACKER_SMOOTHING = 0.22; // 0..1, lower = more smoothing
  const TRACKER_MIN_ALPHA = 0.08;
  // global matcher and labeled count so we can refresh descriptors without reload
  let globalFaceMatcher = null;
  let globalLabeledCount = 0;

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

  // simulated scanner disabled

  // HLS.js to play .m3u8 stream in browsers with retry/cache-bust
  // NOTE: point this at the HLS output inside your XAMPP htdocs folder so Apache can serve it.
  // We'll use a path relative to the site: /thesis2/stream/index.m3u8
  const HLS_URL = '/thesis2/stream/index.m3u8';
    const statusEl = document.getElementById('stream-status');
    const reloadBtn = document.getElementById('reloadStreamBtn');
    let hls = null;
  // hls recovery/backoff state
  let hlsRecoveryAttempts = 0;
  const HLS_RECOVERY_MAX = 6;

    function setStatus(text, color) {
      statusEl.textContent = text;
      statusEl.style.background = color || 'rgba(0,0,0,0.6)';
    }

    let hlsStarted = false;
    function startHls() {
      if (hlsStarted) return;
      hlsStarted = true;
      setStatus('Connecting...', 'rgba(0,0,0,0.6)');
      // destroy previous instance
      try { if (hls) { hls.destroy(); hls = null; } } catch (e) { console.warn(e); }

      const url = HLS_URL + '?_=' + Date.now(); // cache-bust

      if (Hls.isSupported()) {
        // more tolerant HLS config to handle small gaps and fragment load misses
        hls = new Hls({
          maxRetry: 4,
          // allow somewhat larger holes to tolerate missing/late segments
          // while still attempting soft recovery. Raising this reduces the
          // chance of immediate bufferSeekOverHole failures when segments
          // are briefly missing on the server.
          maxBufferLength: 30,
          maxMaxBufferLength: 60,
          maxBufferHole: 8,
          liveSyncDuration: 3,
          autoStartLoad: true,
          // timeout for fragment loads
          fragLoadingTimeOut: 20000,
          xhrSetup: function(xhr, url) { xhr.withCredentials = false; }
        });
        hls.attachMedia(liveVideo);
        hls.on(Hls.Events.MEDIA_ATTACHED, function() {
          hls.loadSource(url);
          // force start at live edge
          try { hls.startLoad(-1); } catch(e) { console.warn('startLoad failed', e); }
        });
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
          setStatus('Live', 'rgba(0,128,0,0.8)');
          liveVideo.play().catch(()=>{});
        });
        hls.on(Hls.Events.ERROR, function(event, data) {
          // Enhanced logging: print data object to console for debugging
          try { console.error('Hls error', data); } catch(e) { console.warn('Could not stringify Hls error', e); }

          // If we see repeated errors, escalate backoff to avoid tight retry loops
          function scheduleRecovery(delayMs) {
            hlsRecoveryAttempts = Math.min(HLS_RECOVERY_MAX, hlsRecoveryAttempts + 1);
            setStatus('Reconnecting (attempt ' + hlsRecoveryAttempts + ')', 'rgba(255,140,0,0.9)');
            setTimeout(() => {
              try { startHls(); } catch(e) { console.warn('startHls failed', e); }
            }, delayMs);
          }

          // detailed handling: frag 404 (old/missing segment)
          if (data && data.details === 'fragLoadError' && data.response && data.response.code === 404) {
            console.warn('Frag 404 — resource not found', failedUrl, data);
            try { hls.stopLoad(); } catch(e){}
            try { hls.detachMedia(); } catch(e){}
            const backoff = 800 * Math.pow(2, Math.max(0, hlsRecoveryAttempts));
            scheduleRecovery(backoff);
            return;
          }

          // buffer nudge attempt (soft recovery)
          if (data && data.details === 'bufferNudgeOnStall') {
            console.info('Hls bufferNudgeOnStall — nudge attempted', { currentTime: liveVideo.currentTime, buffered: (() => { try { if (liveVideo.buffered && liveVideo.buffered.length) return [liveVideo.buffered.start(0), liveVideo.buffered.end(liveVideo.buffered.length-1)]; } catch(e){} return []; })() });
          }

          // buffer stalled: try a targeted recovery (seek to live edge) then restart if it persists
          if (data && data.type === 'mediaError' && data.details === 'bufferStalledError') {
            console.warn('Buffer stalled — attempting seek to live');
            try {
              if (liveVideo.buffered && liveVideo.buffered.length) {
                const end = liveVideo.buffered.end(liveVideo.buffered.length - 1);
                liveVideo.currentTime = Math.max(0, end - 0.2);
              }
            } catch(e) { console.warn('seek failed', e); }
            // schedule a reload if stalls continue
            scheduleRecovery(1000 * Math.pow(2, Math.max(0, hlsRecoveryAttempts)));
            return;
          }

          // Seek-over-hole occurs when a seek jumps into an unbuffered gap
          // (missing/removed segments). Try a soft recovery by seeking to
          // the live edge and restarting load. This prevents the player
          // from repeatedly failing with bufferSeekOverHole in unstable
          // segment/FFmpeg states.
          if (data && data.details === 'bufferSeekOverHole') {
            console.warn('bufferSeekOverHole — attempting seek-to-live and restart', data);
            try {
              if (liveVideo.buffered && liveVideo.buffered.length) {
                const end = liveVideo.buffered.end(liveVideo.buffered.length - 1);
                liveVideo.currentTime = Math.max(0, end - 0.2);
              } else {
                // fallback: try moving to the end of duration (best-effort)
                if (liveVideo.duration && !isNaN(liveVideo.duration) && liveVideo.duration > 0) {
                  liveVideo.currentTime = liveVideo.duration - 0.1;
                }
              }
            } catch (e) { console.warn('seek-to-live failed', e); }
            try { if (hls) hls.startLoad(-1); } catch(e) { console.warn('hls.startLoad failed', e); }
            // attempt a quick recovery and escalate if it keeps failing
            scheduleRecovery(800);
            return;
          }

          // fatal fallback: full restart
          if (data && data.fatal) {
            console.warn('Fatal HLS error — full restart');
            try { hls.stopLoad(); hls.detachMedia(); hls.destroy(); } catch(e){}
            hls = null; hlsStarted = false;
            setStatus('Error - reconnecting...', 'rgba(128,0,0,0.8)');
            scheduleRecovery(1500);
          }
        });
      } else if (liveVideo.canPlayType('application/vnd.apple.mpegurl')) {
        liveVideo.src = url;
        liveVideo.addEventListener('loadedmetadata', function () {
          setStatus('Live', 'rgba(0,128,0,0.8)');
          liveVideo.play().catch(()=>{});
        });
      } else {
        setStatus('No HLS support in this browser', 'rgba(128,0,0,0.8)');
      }
    }

    reloadBtn.addEventListener('click', () => startHls());
    // start on load (but first check whether the HLS manifest exists)
    async function checkStreamStatus() {
      try {
        const res = await fetch('/thesis2/stream_status.php', { cache: 'no-store' });
        const j = await res.json();
        if (j && j.exists) {
          // manifest present, start HLS if not already started
          setStatus('Manifest present, starting stream...', 'rgba(0,128,0,0.8)');
          startHls();
        } else {
          setStatus('No manifest yet — waiting...', 'rgba(128,0,0,0.8)');
        }
      } catch (err) {
        console.warn('Stream status check failed', err);
        setStatus('Status check failed', 'rgba(128,0,0,0.8)');
      }
    }

    // poll for manifest (useful during ffmpeg startup)
    checkStreamStatus();
    setInterval(checkStreamStatus, 2500);


    Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri('/thesis2/models'),
      faceapi.nets.faceLandmark68Net.loadFromUri('/thesis2/models'),
      faceapi.nets.faceRecognitionNet.loadFromUri('/thesis2/models')
    ]).then(startCamera)

    function startCamera() {
      // your existing camera startup code here
      console.log("✅ Models loaded and camera started")
    }
    
    // --- Client-side recognition using face-api.js ---
    // Loads labeled images from server, builds LabeledFaceDescriptors and runs
    // a recognition loop on the live video. Updates overlay canvas and the class log.
    async function loadLabeledDescriptors() {
      try {
        const res = await fetch('/thesis2/python/get_known_faces.php', {cache: 'no-store'});
        const j = await res.json();
        if (!j.ok) return [];
        const data = j.data;
        const labeled = [];
        for (const label of Object.keys(data)) {
          const imgs = data[label];
          const descriptors = [];
          for (const url of imgs) {
            try {
              const img = await faceapi.fetchImage(url);
              const detection = await faceapi.detectSingleFace(img, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
              if (detection && detection.descriptor) descriptors.push(detection.descriptor);
            } catch (e) {
              console.warn('Failed to load/encode', url, e);
            }
          }
          if (descriptors.length > 0) {
            labeled.push(new faceapi.LabeledFaceDescriptors(label, descriptors));
            console.log('Loaded labeled descriptor for', label);
          }
        }
        return labeled;
      } catch (err) {
        console.warn('Failed to load known faces', err);
        return [];
      }
    }

      // reload labeled descriptors into globalFaceMatcher (no page reload required)
      async function reloadLabeledDescriptorsGlobal() {
        const labeled = await loadLabeledDescriptors();
        globalLabeledCount = labeled.length;
        if (labeled.length > 0) {
          globalFaceMatcher = new faceapi.FaceMatcher(labeled, 0.6);
          console.log('Global descriptors loaded:', globalLabeledCount);
        } else {
          globalFaceMatcher = null;
          console.log('No labeled descriptors found');
        }
        return { matcher: globalFaceMatcher, count: globalLabeledCount };
      }

    async function startRecognition() {
      const overlay = document.getElementById('overlay');
      const video = document.getElementById('liveVideo');
      function resizeOverlay() {
        overlay.width = video.clientWidth;
        overlay.height = video.clientHeight;
        overlay.style.left = video.offsetLeft + 'px';
        overlay.style.top = video.offsetTop + 'px';
      }

      window.addEventListener('resize', resizeOverlay);

      // Wait until video has some dimensions
      await new Promise(resolve => {
        const check = () => {
          if (video.clientWidth > 0 && video.clientHeight > 0) return resolve();
          setTimeout(check, 200);
        };
        check();
      });
      resizeOverlay();

      // initial load of labeled descriptors into globals
      await reloadLabeledDescriptorsGlobal();
      if (!globalFaceMatcher) console.warn('No labeled descriptors — running detection-only mode');
  const ctx = overlay.getContext('2d');
  const diagEl = document.getElementById('diag');
  // floating face preview removed — no DOM canvas used
  const faceCropEl = null;
  const faceCropCtx = null;
  // cache student info fetched from server by student_id (label)
  const studentInfoCache = {};
  async function fetchStudentInfo(studentId) {
    if (!studentId) return null;
    if (studentInfoCache[studentId]) return studentInfoCache[studentId];
    try {
      const res = await fetch('/thesis2/get_student_info.php?student_id=' + encodeURIComponent(studentId), {cache: 'no-store'});
      const j = await res.json();
      if (j && j.ok && j.student) {
        studentInfoCache[studentId] = j.student;
        return j.student;
      }
    } catch (e) {
      console.warn('Failed to fetch student info for', studentId, e);
    }
    studentInfoCache[studentId] = null;
    return null;
  }
      // removed fullscreen preview toggling per user request
      // initial diag state
      if (diagEl) diagEl.textContent = `Matcher:${globalFaceMatcher? 'yes':'no'} | labeled:${globalLabeledCount} | det:0 | best:0.00`;

      const classLog = document.getElementById('class-log');

      async function detectLoop() {
        if (video.paused || video.ended) {
          setTimeout(detectLoop, 500);
          return;
        }
        const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptors();
        ctx.clearRect(0,0,overlay.width,overlay.height);
        const ratioX = video.videoWidth ? overlay.width / video.videoWidth : 1;
        const ratioY = video.videoHeight ? overlay.height / video.videoHeight : 1;

  // Choose the best detection to track.
        // If faceMatcher is available, prefer a matched known face (lowest distance).
        // Otherwise pick the detection with highest score (or largest box) as the bestDet.
        let bestDet = null;
        let bestMatchInfo = null;
        let bestScore = -Infinity;
        detections.forEach(det => {
          const score = (det.detection && typeof det.detection.score === 'number') ? det.detection.score : (det.detection.box.width * det.detection.box.height);
          if (globalFaceMatcher) {
              const match = globalFaceMatcher.findBestMatch(det.descriptor);
            if (match && match.label && match.label !== 'unknown') {
              if (!bestMatchInfo || match.distance < bestMatchInfo.distance) {
                bestMatchInfo = match;
                bestDet = det;
              }
            } else if (!bestMatchInfo) {
              // fallback: choose highest scoring detection if no labeled match found yet
              if (score > bestScore) {
                bestScore = score;
                bestDet = det;
              }
            }
          } else {
            // detection-only mode: pick highest scoring detection
            if (score > bestScore) {
              bestScore = score;
              bestDet = det;
            }
          }
        });

  // clear canvas before drawing
    ctx.clearRect(0,0,overlay.width,overlay.height);

  if (bestDet) {
          const box = bestDet.detection.box;
          const x = box.x * ratioX;
          const y = box.y * ratioY;
          const w = box.width * ratioX;
          const h = box.height * ratioY;

          // smoothing interpolation
          if (!trackerLastBox) {
            trackerLastBox = { x, y, w, h, alpha: 1 };
          } else {
            trackerLastBox.x += (x - trackerLastBox.x) * TRACKER_SMOOTHING;
            trackerLastBox.y += (y - trackerLastBox.y) * TRACKER_SMOOTHING;
            trackerLastBox.w += (w - trackerLastBox.w) * TRACKER_SMOOTHING;
            trackerLastBox.h += (h - trackerLastBox.h) * TRACKER_SMOOTHING;
            trackerLastBox.alpha = Math.min(1, trackerLastBox.alpha + 0.2);
          }

          // draw green rounded rectangle with slight fill
          ctx.save();
          ctx.globalAlpha = Math.max(TRACKER_MIN_ALPHA, trackerLastBox.alpha);
          ctx.lineWidth = 3;
          ctx.strokeStyle = 'rgba(0,200,0,0.95)';
          ctx.fillStyle = 'rgba(0,170,0,0.08)';
          const rx = trackerLastBox.x;
          const ry = trackerLastBox.y;
          const rw = trackerLastBox.w;
          const rh = trackerLastBox.h;
          const r = Math.min(12, Math.floor(Math.min(rw, rh) * 0.08));
          // rounded rect path
          ctx.beginPath();
          ctx.moveTo(rx + r, ry);
          ctx.arcTo(rx + rw, ry, rx + rw, ry + rh, r);
          ctx.arcTo(rx + rw, ry + rh, rx, ry + rh, r);
          ctx.arcTo(rx, ry + rh, rx, ry, r);
          ctx.arcTo(rx, ry, rx + rw, ry, r);
          ctx.closePath();
          ctx.fill();
          ctx.stroke();

          // label + confidence
          // Normalize FaceMatcher output: it returns 'unknown' (lowercase) for
          // non-matches. Treat any case-variation of 'unknown' as Unknown so
          // the UI branch that overrides anonymous detections will run.
          let label = bestMatchInfo ? bestMatchInfo.label : 'unknown';
          const isUnknown = String(label).toLowerCase() === 'unknown';
          if (isUnknown) label = 'Unknown';
          const conf = bestMatchInfo ? (1 - bestMatchInfo.distance).toFixed(2) : (bestDet.detection && bestDet.detection.score ? bestDet.detection.score.toFixed(2) : '0.00');
          // try to map label (student_id like S999) to real student details from DB
          // By default use the raw label as displayName. If label is known, fetch DB info.
          // Special case: if the matcher returns 'Unknown' we still want to show the
          // user's name 'Akoto Adona' in the overlay (per request) but we DO NOT
          // treat this as a verified DB match for attendance upsert.
          let displayName = label;
          let studentDbId = null;
          let studentPhoto = null;
          let stuObj = null;
          if (!isUnknown) {
            try {
              stuObj = await fetchStudentInfo(label);
              if (stuObj) {
                displayName = stuObj.full_name || stuObj.student_id || label;
                studentDbId = stuObj.id || null;
                studentPhoto = stuObj.photo || null;
              } 
            } catch (e) {
              // ignore and fall back to raw label
            }
          } else {
            // Override display for anonymous detections to show a friendly name.
            // Prefer an explicit URL override (useful for testing), then fall
            // back to the hardcoded name.
            displayName = OVERRIDE_NAME || 'Akoto Adona';
            // If an override id was provided, prepare the studentDbId so the
            // class log can be upserted using that id.
            if (OVERRIDE_ID) {
              studentDbId = OVERRIDE_ID;
            }
          }

          // show only the student's full name in the overlay (no id/confidence)
          // If the matcher reported unknown, force the friendly override name.
          let txt = (isUnknown ? (OVERRIDE_NAME || 'Akoto Adona') : (displayName || label));
          ctx.font = '16px Arial';
          ctx.textBaseline = 'top';
          ctx.fillStyle = 'rgba(0,0,0,0.6)';
          const padding = 6;
          const txtW = ctx.measureText(txt).width + padding * 2;
          const txtH = 22;
          // background box for text (only draw when we have a label to show)
          if (txt) {
            const tx = rx;
            const ty = Math.max(ry - txtH - 6, 4);
            ctx.fillRect(tx, ty, txtW, txtH);
            ctx.fillStyle = 'white';
            ctx.fillText(txt, tx + padding, ty + 3);
          }
          ctx.restore();

          // floating face preview drawing removed per user request

          // update class log (upsert using DB id if available, else student_id label)
          // If we have a known label or an override id, upsert the class log.
          if (!isUnknown || (isUnknown && OVERRIDE_ID)) {
            const lookupId = studentDbId ? String(studentDbId) : String(label);
            const existing = classLog.querySelector('tbody tr[data-student-db-id="' + lookupId + '"]');
            const time = new Date().toLocaleTimeString();
            if (existing) {
              // update existing row's status/time
              const timeInEl = existing.querySelector('.time-in');
              if (timeInEl) timeInEl.textContent = time;
              const statusCell = existing.querySelector('td:nth-child(3)');
              if (statusCell) { statusCell.textContent = 'Present'; statusCell.style.color = 'green'; }
            } else {
              // create new row; prefer displayName and student_id
              const tr = document.createElement('tr');
              tr.setAttribute('data-student-db-id', lookupId);
              const nameCell = displayName || label;
              const idCell = (studentDbId ? (stuObj && stuObj.student_id ? stuObj.student_id : label) : label);
              tr.innerHTML = `<td style="padding:8px;border-bottom:1px solid #eee">${idCell}</td><td style="padding:8px;border-bottom:1px solid #eee">${nameCell}</td><td style="padding:8px;color:green;border-bottom:1px solid #eee">Present</td><td class="time-in" style="padding:8px;border-bottom:1px solid #eee">${time}</td><td class="time-out" style="padding:8px;border-bottom:1px solid #eee">-</td><td style="padding:8px;border-bottom:1px solid #eee">Detected</td>`;
              classLog.querySelector('tbody').prepend(tr);
            }
          }
          // diag updates removed per user request
        } else {
          // no matched face — fade out the tracker
          if (trackerLastBox) {
            trackerLastBox.alpha = Math.max(0, trackerLastBox.alpha - 0.12);
              if (trackerLastBox.alpha <= 0.02) trackerLastBox = null;
            else {
              // draw faint box at last position while fading
              ctx.save();
              ctx.globalAlpha = Math.max(TRACKER_MIN_ALPHA, trackerLastBox.alpha * 0.8);
              ctx.lineWidth = 2;
              ctx.strokeStyle = 'rgba(0,200,0,0.6)';
              ctx.strokeRect(trackerLastBox.x, trackerLastBox.y, trackerLastBox.w, trackerLastBox.h);
              ctx.restore();
              }
          }
        }

        setTimeout(detectLoop, 700);
      }

      detectLoop();
    }

    // Start recognition after HLS manifest leads to video playback
    document.getElementById('liveVideo').addEventListener('play', () => {
      // give models a small moment
      setTimeout(() => startRecognition().catch(e => console.error(e)), 500);
    });
    // Editing is performed on manattendance.php; camera page now links there for manual edits.
  </script>
</body>
</html>

<script>
  // Extra aggressive removal: delete any button/anchor whose visible text
  // contains the word 'enroll' (case-insensitive). This runs after the page
  // load and also watches for later insertions.
  (function(){
    function removeEnrollButtons(root) {
      try {
        const sels = (root || document).querySelectorAll ? (root || document).querySelectorAll('button, a') : [];
        const removed = [];
        for (const el of sels) {
          try {
            const txt = (el.textContent || '').trim();
            if (!txt) continue;
            if (/\benroll\b/i.test(txt)) {
              if (el.parentNode) el.parentNode.removeChild(el);
              removed.push(txt);
            }
          } catch(e){}
        }
        if (removed.length) console.info('Removed enroll buttons:', removed);
      } catch(e) { console.warn('removeEnrollButtons failed', e); }
    }

    // initial removal
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => removeEnrollButtons(document));
    } else removeEnrollButtons(document);

    // observe future additions
    try {
      const mo = new MutationObserver(muts => {
        for (const m of muts) {
          if (!m.addedNodes) continue;
          for (const n of m.addedNodes) {
            try { if (n.nodeType === 1) removeEnrollButtons(n); } catch(e){}
          }
        }
      });
      mo.observe(document.documentElement || document.body, { childList: true, subtree: true });
    } catch(e){}
  })();
</script>
