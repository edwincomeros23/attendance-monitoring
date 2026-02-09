<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';


$OVERRIDE_NAME = null;
$OVERRIDE_ID = null;

// Fetch students for the class log based on URL parameters
// Use session to remember last selected year/section
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$students = [];
$year = isset($_GET['year']) ? trim($_GET['year']) : (isset($_SESSION['last_year']) ? $_SESSION['last_year'] : '');
$section = isset($_GET['section']) ? trim($_GET['section']) : (isset($_SESSION['last_section']) ? $_SESSION['last_section'] : '');

// Store in session if provided
if (isset($_GET['year']) && isset($_GET['section'])) {
  $_SESSION['last_year'] = $year;
  $_SESSION['last_section'] = $section;
}

if ($year && $section) {
  // Convert year to database format (e.g., "7" -> "Grade 7")
  if (is_numeric($year)) {
    $year_level = "Grade " . $year;
  } else {
    $year_level = $year;
  }
  
  // Extract section name (remove year prefix if present, e.g., "7-Ruby" -> "Ruby")
  $section_name = $section;
  if (preg_match('/^(\d+)[-\s]*(.+)$/', $section, $matches)) {
    $section_name = $matches[2];
  }
  $section_raw = $section;
  $section_for_save = $section_name;

  // Fetch schedule (time_in/time_out) for this grade/section, prioritizing matching day & section
  $scheduleTimeIn = '-';
  $scheduleTimeOut = '-';
  $today = date('l');

  // Try exact section + day match first
  if ($stmt = $conn->prepare("SELECT time_in, time_out FROM curriculum WHERE (grade_level = ? OR grade_level = ?) AND (section = ? OR section = ?) AND (day_of_week = ? OR day_of_week = '' OR day_of_week IS NULL) ORDER BY id DESC LIMIT 1")) {
    $stmt->bind_param('sssss', $year_level, $year, $section_name, $section_raw, $today);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      if (!empty($row['time_in'])) $scheduleTimeIn = date('g:i A', strtotime($row['time_in']));
      if (!empty($row['time_out'])) $scheduleTimeOut = date('g:i A', strtotime($row['time_out']));
    }
    $stmt->close();
  }

  // Fallback: any schedule for grade (no section restriction)
  if ($scheduleTimeIn === '-' && $stmt = $conn->prepare("SELECT time_in, time_out FROM curriculum WHERE (grade_level = ? OR grade_level = ?) AND (day_of_week = ? OR day_of_week = '' OR day_of_week IS NULL) ORDER BY id DESC LIMIT 1")) {
    $stmt->bind_param('sss', $year_level, $year, $today);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      if (!empty($row['time_in'])) $scheduleTimeIn = date('g:i A', strtotime($row['time_in']));
      if (!empty($row['time_out'])) $scheduleTimeOut = date('g:i A', strtotime($row['time_out']));
    }
    $stmt->close();
  }

  // Query students
  if ($stmt = $conn->prepare("SELECT id, student_id, full_name, section FROM students WHERE year_level = ? AND (section = ? OR section = ?) ORDER BY full_name")) {
    $stmt->bind_param('sss', $year_level, $section_name, $section_raw);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      if (!empty($row['section'])) {
        $section_for_save = $row['section'];
      }
      $students[] = $row;
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Live Camera — WMSU Attendance Tracking</title>
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
    /* Class Log table alignment & widths */
    #class-log { width: 100%; border-collapse: collapse; font-size: 14px; table-layout: auto; }
    #class-log th, #class-log td { padding: 8px; border-bottom: 1px solid #eee; }
    /* Column widths: ID small, Status/Times narrow, others flexible */
    #class-log th:nth-child(1), #class-log td:nth-child(1) { width: 110px; text-align: left; }
    #class-log th:nth-child(2), #class-log td:nth-child(2) { width: auto; text-align: left; }
    #class-log th:nth-child(3), #class-log td:nth-child(3) { width: 110px; text-align: center; }
    #class-log th:nth-child(4), #class-log td:nth-child(4),
    #class-log th:nth-child(5), #class-log td:nth-child(5) { width: 120px; text-align: center; }
    #class-log th:nth-child(6), #class-log td:nth-child(6) { width: auto; text-align: left; }
    .admin-info { font-weight: bold; }
    .camera-wrapper { display: block; gap: 20px; margin-top: 20px; }
    .video-section {
      width: 100%; background: white; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      position: relative; padding: 10px; overflow: hidden; height: 100%;
    }
    #liveVideo {
      width: 100%; height: 100%; object-fit: cover;
      border-radius: 8px; border: 2px solid #b71c1c;
    }
    .video-section:fullscreen {
      width: 100vw; height: 100vh; padding: 0; margin: 0; position: relative;
    }
    .video-section:fullscreen #liveVideo {
      width: 100vw; height: 100vh; border-radius: 0;
    }
    .video-section:fullscreen #overlay {
      position: fixed !important;
      width: 100vw !important;
      height: 100vh !important;
      left: 0 !important;
      top: 0 !important;
      z-index: 2147483647 !important;
      display: block !important;
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
    header a{
      text-decoration: none;
      color: #fff;
      font-weight: 600;
    }
    .back-btn{
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 6px;
      background: rgba(255,255,255,0.12);
    }
    .back-btn:hover{ background: rgba(255,255,255,0.2); }
  </style>
</head>
<body>
  <?php include '../sidebar.php'; ?>
  <script>
    const OVERRIDE_NAME = <?php echo json_encode($OVERRIDE_NAME); ?>;
    const OVERRIDE_ID = <?php echo json_encode($OVERRIDE_ID); ?>;
    const SECTION_NAME = <?php echo json_encode($section_for_save ?? ($section_name ?? '')); ?>;
    const YEAR_LEVEL = <?php echo json_encode($year_level ?? ''); ?>;
  </script>
  <div class="main">
    <header>
      <h2>Live Camera</h2>
      <?php
        // preserve filters when going back to camera
        $backUrl = 'camera.php';
        if (!empty($year) && !empty($section)) {
          $backUrl .= '?year=' . urlencode($year) . '&section=' . urlencode($section);
        }
      ?>
      <div class="header-actions">
        <a href="/attendance-monitoring/pages/notifications.php" class="notif-btn" aria-label="Notifications" title="Notifications">
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
            <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
          </svg>
          <span class="notif-count"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="back-btn">&larr; Back</a>
      </div>
    </header>

    <div class="camera-wrapper">
      <div class="video-section">
        <div id="scanner"></div>

  <!-- Live Stream Video -->
  <video id="liveVideo" autoplay muted controls></video>
    <canvas id="overlay" style="position:absolute;left:0;top:0;z-index:4;pointer-events:none;display:block"></canvas>
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
        <button onclick="location.reload()" style="padding:6px 12px;background:#b71c1c;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:13px" title="Refresh student list"><i class="fa fa-sync"></i> Refresh</button>
      </div>
  <div id="class-log-scroll" style="overflow:auto;max-height:220px;margin-top:8px;padding-bottom:0">
        <table id="class-log" style="width:100%;border-collapse:collapse;font-size:14px">
          <thead style="background:#f7f7f7"><tr><th style="padding:8px;border-bottom:1px solid #eee">Student ID</th><th style="padding:8px;border-bottom:1px solid #eee">Name</th><th style="padding:8px;border-bottom:1px solid #eee">Status</th><th style="padding:8px;border-bottom:1px solid #eee">Time-In</th><th style="padding:8px;border-bottom:1px solid #eee">Time-Out</th><th style="padding:8px;border-bottom:1px solid #eee">Remarks</th></tr></thead>
          <tbody>
            <?php if (!empty($students)): ?>
              <?php foreach ($students as $student): ?>
                <tr data-student-db-id="<?php echo htmlspecialchars($student['id']); ?>">
                  <td style="padding:8px;border-bottom:1px solid #eee"><?php echo htmlspecialchars($student['student_id']); ?></td>
                  <td style="padding:8px;border-bottom:1px solid #eee"><?php echo htmlspecialchars($student['full_name']); ?></td>
                  <td style="padding:8px;color:#999;border-bottom:1px solid #eee">Absent</td>
                  <td class="time-in" style="padding:8px;border-bottom:1px solid #eee">-</td>
                  <td class="time-out" style="padding:8px;border-bottom:1px solid #eee">-</td>
                  <td style="padding:8px;border-bottom:1px solid #eee">Not yet detected</td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" style="padding:8px;text-align:center;color:#999">No students found for this section. Please provide year and section parameters.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        </table>
      </div>

      <!-- Footer inside container with buttons (right-aligned, does not overlap rows) -->
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px">
        <button id="submitAttendanceBtn" class="manual-btn" style="cursor:pointer">Submit Attendance</button>
        <?php
          // preserve current filters when jumping to manual attendance
          $maYear = isset($year_level) ? $year_level : '';
          $maSection = isset($section_name) ? $section_name : '';
          $maUrl = 'manattendance.php';
          if ($maYear !== '' && $maSection !== '') {
            $maUrl .= '?year=' . urlencode($maYear) . '&section=' . urlencode($maSection);
          }
        ?>
        <a href="<?php echo htmlspecialchars($maUrl); ?>" class="manual-btn" style="text-decoration:none">Manual Attendance</a>
      </div>
    </div>

  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
  <script>
    const scanner = document.getElementById('scanner');
    const toggleBtn = document.getElementById('toggleCameraBtn');
    const liveVideo = document.getElementById('liveVideo');
    const videoSection = document.querySelector('.video-section');
    // Scheduled subject time window (from curriculum lookup)
    const SCHEDULE_TIME_IN = <?php echo json_encode($scheduleTimeIn ?? '-'); ?>;
    const SCHEDULE_TIME_OUT = <?php echo json_encode($scheduleTimeOut ?? '-'); ?>;
    // OVERRIDE_NAME and OVERRIDE_ID are injected server-side (if available)
    let trackingInterval;
    let cameraOn = true;
    // client-side map of students in this section for quick label->name lookup
    const normId = (id) => (id || '').toString().trim().toUpperCase();
    const STUDENT_MAP = <?php
      $studentMap = [];
      if (!empty($students ?? [])) {
        foreach ($students as $s) {
          $sid = isset($s['student_id']) ? trim($s['student_id']) : '';
          if ($sid !== '') {
            $key = strtoupper($sid);
            $obj = [
              'id' => (int)$s['id'],
              'student_id' => $sid,
              'full_name' => $s['full_name'] ?? '',
              'photo' => $s['photo1'] ?? 'students/default-avatar.png'
            ];
            $studentMap[$key] = $obj;
            // also alias by primary key prefixed with S{dbId} to match face folder labels
            $aliasKey = 'S' . (int)$s['id'];
            if (!isset($studentMap[$aliasKey])) {
              $studentMap[$aliasKey] = $obj;
            }
          }
        }
      }
      echo json_encode($studentMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    ?>;

    function getStudentFromMap(studentId) {
      const key = normId(studentId);
      if (!key) return null;
      return STUDENT_MAP[key] || null;
    }
  // tracker smoothing state - keyed by recognized student id/label for stability
  let trackerLastBoxes = {}; // key: trackKey, value: {x,y,w,h,alpha,lastSeen,labelText,isUnknown,lookupId}
  const TRACKER_SMOOTHING = 0.12; // 0..1, lower = more smoothing
  const TRACKER_MIN_ALPHA = 0.12;
  const TRACKER_HOLD_MS = 500; // keep last box briefly when detection drops
  const MIN_CONFIDENCE_THRESHOLD = 0.3; // minimum confidence to track a face
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
  // We'll use a path relative to the site: /attendance-monitoring/stream/index.m3u8
  const HLS_URL = `${location.origin}/attendance-monitoring/stream/index.m3u8`;
    const statusEl = document.getElementById('stream-status');
    const reloadBtn = document.getElementById('reloadStreamBtn');
    let hls = null;
  // hls recovery/backoff state
  let hlsRecoveryAttempts = 0;
  const HLS_RECOVERY_MAX = 6;
  // Downscaled detection canvas to reduce CPU load
  const detectionCanvas = document.createElement('canvas');
  const detectionCtx = detectionCanvas.getContext('2d', { willReadFrequently: true });

  // Persist attendance to the server so the subject Class Log can read it
  const attendanceSaveCache = {}; // key: studentDbId, value: last serialized payload
  function toSqlTime(ts) {
    const d = ts instanceof Date ? ts : new Date(ts);
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }

  function parseScheduleTimeToday(timeStr) {
    if (!timeStr || timeStr === '-') return null;
    const m = String(timeStr).trim().match(/^\s*(\d{1,2}):(\d{2})\s*([AP]M)\s*$/i);
    if (!m) return null;
    let hours = parseInt(m[1], 10);
    const minutes = parseInt(m[2], 10);
    const mer = m[3].toUpperCase();
    if (mer === 'PM' && hours < 12) hours += 12;
    if (mer === 'AM' && hours === 12) hours = 0;
    const d = new Date();
    d.setHours(hours, minutes, 0, 0);
    return d;
  }

  async function saveAttendanceUpdate(studentDbId, { status = 'Present', timeInTs = null, timeOutTs = null } = {}) {
    if (!studentDbId || !SECTION_NAME) return;
    const payload = {
      student_db_id: studentDbId,
      status,
      section: SECTION_NAME
    };
    if (timeInTs) payload.time_in = toSqlTime(timeInTs);
    if (timeOutTs) payload.time_out = toSqlTime(timeOutTs);

    const cacheKey = JSON.stringify(payload);
    if (attendanceSaveCache[studentDbId] === cacheKey) return; // avoid duplicate posts
    attendanceSaveCache[studentDbId] = cacheKey;

    try {
      await fetch('/attendance-monitoring/crud/save_manual_attendance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(payload)
      });
      
      // Send SMS notification - determine event type based on which timestamp is present
      const eventType = timeInTs ? 'time_in' : (timeOutTs ? 'time_out' : 'time_in');
      fetch('/attendance-monitoring/crud/send_sms_notification.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          student_id: studentDbId,
          event_type: eventType
        })
      }).catch(err => console.warn('SMS notification failed', err));
      
    } catch (err) {
      console.warn('Attendance save failed', err);
    }
  }

    function setStatus(text, color) {
      statusEl.textContent = text;
      statusEl.style.background = color || 'rgba(0,0,0,0.6)';
    }

    function resizeOverlayToVideo() {
      const overlay = document.getElementById('overlay');
      if (!overlay || !liveVideo) return;
      
      const isFs = !!document.fullscreenElement;
      if (isFs) {
        overlay.width = window.innerWidth;
        overlay.height = window.innerHeight;
        overlay.style.position = 'fixed';
        overlay.style.left = '0px';
        overlay.style.top = '0px';
        overlay.style.display = 'block';
      } else {
        overlay.style.position = 'absolute';
        if (liveVideo.clientWidth === 0 || liveVideo.clientHeight === 0) return;
        overlay.width = liveVideo.clientWidth;
        overlay.height = liveVideo.clientHeight;
        overlay.style.left = liveVideo.offsetLeft + 'px';
        overlay.style.top = liveVideo.offsetTop + 'px';
      }
    }

    document.addEventListener('fullscreenchange', resizeOverlayToVideo);

    let hlsStarted = false;
    function startHls() {
      if (hlsStarted) return;
      hlsStarted = true;
      setStatus('Connecting...', 'rgba(0,0,0,0.6)');
      // destroy previous instance
      try { if (hls) { hls.destroy(); hls = null; } } catch (e) { console.warn(e); }

      const url = HLS_URL + '?_=' + Date.now(); // cache-bust

      if (Hls.isSupported()) {
        // Optimized HLS config: larger buffer, longer timeouts, better error recovery
        // With 30 segments * 2 sec each = 60 sec buffer available from FFmpeg
        hls = new Hls({
          maxRetry: 6,
          maxBufferLength: 60,          // Increase buffered content to 60 seconds
          maxMaxBufferLength: 120,      // Allow up to 120 seconds in extreme cases
          maxBufferHole: 12,            // Tolerate gaps up to 12 seconds (covers 6 segments)
          liveSyncDuration: 2,          // Sync 2 seconds from live edge (vs 10+ default)
          liveBackBufferLength: 30,     // Keep 30 seconds of past content
          autoStartLoad: true,
          startLevel: -1,               // Auto-select quality
          fragLoadingTimeOut: 30000,    // Increase timeout to 30 sec (was 20)
          fragLoadingMaxRetry: 8,       // More retries for each fragment
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
          try { console.error('Hls error', event, data); } catch(e) { console.warn('Could not stringify Hls error', e); }

          // Ignore non-fatal errors on first attempt
          if (!data.fatal && hlsRecoveryAttempts === 0) {
            console.info('Ignoring non-fatal HLS error on initial load', data.details);
            return;
          }

          // If we see repeated errors, escalate backoff to avoid tight retry loops
          function scheduleRecovery(delayMs) {
            hlsRecoveryAttempts = Math.min(HLS_RECOVERY_MAX, hlsRecoveryAttempts + 1);
            setStatus('Reconnecting (attempt ' + hlsRecoveryAttempts + ')', 'rgba(255,140,0,0.9)');
            setTimeout(() => {
              try { startHls(); } catch(e) { console.warn('startHls failed', e); }
            }, delayMs);
          }

          // Handle 403 Forbidden errors (manifest or segment access denied)
          if (data && data.details === 'fragLoadError' && data.response && data.response.code === 403) {
            const url = (data && data.frag && data.frag.url) ? data.frag.url : '(unknown)';
            console.error('Frag 403 — Access Denied', url, data);
            setStatus('Access Denied (403) — check server permissions', 'rgba(128,0,0,0.8)');
            // Don't retry 403 immediately, it's a permission issue
            scheduleRecovery(3000);
            return;
          }

          // Handle 404 errors (segment missing/not found)
          if (data && data.details === 'fragLoadError' && data.response && data.response.code === 404) {
            // data.frag.url contains the missing segment; guard in case it's undefined
            const failedUrl = (data && data.frag && data.frag.url) ? data.frag.url : '(unknown fragment)';
            console.warn('Frag 404 — resource not found', failedUrl, data);
            try { hls.stopLoad(); } catch(e){}
            try { hls.detachMedia(); } catch(e){}
            const backoff = 800 * Math.pow(2, Math.max(0, hlsRecoveryAttempts));
            scheduleRecovery(backoff);
            return;
          }

          // Handle manifest 404 (playlist not found yet)
          if (data && data.details === 'manifestLoadError' && data.response && data.response.code === 404) {
            console.warn('Manifest 404 — waiting for stream to start');
            const backoff = 1000 * Math.pow(2, Math.max(0, hlsRecoveryAttempts));
            scheduleRecovery(backoff);
            return;
          }

          // buffer nudge attempt (soft recovery)
          if (data && data.details === 'bufferNudgeOnStall') {
            console.info('Hls bufferNudgeOnStall — nudge attempted', { currentTime: liveVideo.currentTime, buffered: (() => { try { if (liveVideo.buffered && liveVideo.buffered.length) return [liveVideo.buffered.start(0), liveVideo.buffered.end(liveVideo.buffered.length-1)]; } catch(e){} return []; })() });
          }

          // buffer stalled: try a targeted recovery (seek to live edge) then restart if it persists
          if (data && data.type === 'mediaError' && data.details === 'bufferStalledError') {
            console.warn('Buffer stalled — attempting seek to live', { buffer: data.buffer, buffered: liveVideo.buffered });
            try {
              if (liveVideo.buffered && liveVideo.buffered.length) {
                const end = liveVideo.buffered.end(liveVideo.buffered.length - 1);
                // Jump to 0.5 seconds before the end (vs 0.2) to avoid edge cases
                liveVideo.currentTime = Math.max(0, end - 0.5);
              }
            } catch(e) { console.warn('seek failed', e); }
            try { if (hls) hls.recoverMediaError(); } catch(e) { console.warn('recoverMediaError failed', e); }
            // Shorter recovery interval for stalls (faster auto-recovery)
            const backoff = Math.min(2000, 500 * Math.pow(2, Math.max(0, hlsRecoveryAttempts - 2)));
            scheduleRecovery(backoff);
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

          // fatal fallback: full restart (only if really fatal and after retries)
          if (data && data.fatal && hlsRecoveryAttempts >= 2) {
            console.warn('Fatal HLS error after retries — full restart');
            try { hls.stopLoad(); hls.detachMedia(); hls.destroy(); } catch(e){}
            hls = null; hlsStarted = false;
            setStatus('Error - reconnecting...', 'rgba(128,0,0,0.8)');
            scheduleRecovery(1500);
          } else if (data && data.fatal) {
            // First fatal error: try recovery without destroying
            console.warn('Fatal HLS error — attempt recovery');
            scheduleRecovery(800);
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
        const res = await fetch('/attendance-monitoring/config/stream_status.php', { cache: 'no-store' });
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
      faceapi.nets.tinyFaceDetector.loadFromUri('/attendance-monitoring/models'),
      faceapi.nets.faceLandmark68Net.loadFromUri('/attendance-monitoring/models'),
      faceapi.nets.faceRecognitionNet.loadFromUri('/attendance-monitoring/models')
    ]).then(startCamera)
    ;

    // Do not prefill time-in/out for undetected students.

    function startCamera() {
      // your existing camera startup code here
      console.log("✅ Models loaded and camera started")
    }
    
    // --- Client-side recognition using face-api.js ---
    // Loads labeled images from server, builds LabeledFaceDescriptors and runs
    // a recognition loop on the live video. Updates overlay canvas and the class log.
    async function loadLabeledDescriptors() {
      try {
        // Pass current section context so only enrolled students' faces are loaded
        const params = new URLSearchParams({
          year: YEAR_LEVEL,
          section: SECTION_NAME
        });
        const res = await fetch('/attendance-monitoring/python/get_known_faces.php?' + params.toString(), {cache: 'no-store'});
        const j = await res.json();
        if (!j.ok) return [];
        const data = j.data;
        const labeled = [];
        for (const label of Object.keys(data)) {
          const imgs = data[label];
          let baseLabel = label;
          if (label.indexOf('_') !== -1) {
            if (label.toLowerCase().startsWith('s_no')) {
              baseLabel = label.split('_').slice(0, 2).join('_');
            } else if (label.toLowerCase().startsWith('s_')) {
              baseLabel = label.split('_').slice(0, 2).join('_');
            } else {
              baseLabel = label.split('_')[0];
            }
          }
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
            labeled.push(new faceapi.LabeledFaceDescriptors(baseLabel, descriptors));
            console.log('Loaded labeled descriptor for', label, '->', baseLabel);
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
      window.addEventListener('resize', resizeOverlayToVideo);

      // Wait until video has some dimensions
      await new Promise(resolve => {
        const check = () => {
          if (video.clientWidth > 0 && video.clientHeight > 0) return resolve();
          setTimeout(check, 200);
        };
        check();
      });
      resizeOverlayToVideo();

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
  // first detection timestamps per student (to fix Time-In)
  const firstSeenMap = {};
  // last detection timestamp for Time-Out on leave
  const lastSeenMap = {};
  // status per student (Present or Late) to avoid flipping
  const statusMap = {};
  const scheduleStart = parseScheduleTimeToday(SCHEDULE_TIME_IN);
  const lateCutoff = scheduleStart ? new Date(scheduleStart.getTime() + (15 * 60 * 1000)) : null;
  // set of labels seen in previous loop
  let prevSeenLabels = new Set();
  async function fetchStudentInfo(studentId) {
    const key = normId(studentId);
    if (!key) return null;
    if (studentInfoCache[key]) return studentInfoCache[key];
    const local = getStudentFromMap(key);
    if (local) {
      studentInfoCache[key] = local;
      return local;
    }
    try {
      const res = await fetch('/attendance-monitoring/get_student_info.php?student_id=' + encodeURIComponent(key), {cache: 'no-store'});
      const j = await res.json();
      if (j && j.ok && j.student) {
        studentInfoCache[key] = j.student;
        return j.student;
      }
    } catch (e) {
      console.warn('Failed to fetch student info for', key, e);
    }
    studentInfoCache[key] = null;
    return null;
  }
      // removed fullscreen preview toggling per user request
      // initial diag state
      if (diagEl) diagEl.textContent = `Matcher:${globalFaceMatcher? 'yes':'no'} | labeled:${globalLabeledCount} | det:0 | best:0.00`;

      const classLog = document.getElementById('class-log');
      // Tunable detector options: inputSize must be divisible by 32 for TinyFaceDetector
      // Balanced accuracy: slightly higher inputSize with lower threshold
      const detectorOptions = new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.1 });

      function drawTrackedBox(tracker, labelText, isUnknown) {
        if (!tracker) return;
        ctx.save();
        ctx.globalAlpha = Math.max(TRACKER_MIN_ALPHA, tracker.alpha || 0);
        ctx.lineWidth = 3;
        ctx.strokeStyle = 'rgba(0,200,0,0.95)';
        ctx.fillStyle = 'rgba(0,170,0,0.08)';

        const rx = tracker.x;
        const ry = tracker.y;
        const rw = tracker.w;
        const rh = tracker.h;
        const r = Math.min(12, Math.floor(Math.min(rw, rh) * 0.08));

        ctx.beginPath();
        ctx.moveTo(rx + r, ry);
        ctx.arcTo(rx + rw, ry, rx + rw, ry + rh, r);
        ctx.arcTo(rx + rw, ry + rh, rx, ry + rh, r);
        ctx.arcTo(rx, ry + rh, rx, ry, r);
        ctx.arcTo(rx, ry, rx + rw, ry, r);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        const txt = labelText || (isUnknown ? 'Unknown' : '');
        if (txt) {
          ctx.font = '16px Arial';
          ctx.textBaseline = 'top';
          ctx.fillStyle = 'rgba(0,0,0,0.6)';
          const padding = 6;
          const txtW = ctx.measureText(txt).width + padding * 2;
          const txtH = 22;
          const tx = rx;
          const ty = Math.max(ry - txtH - 6, 4);
          ctx.fillRect(tx, ty, txtW, txtH);
          ctx.fillStyle = 'white';
          ctx.fillText(txt, tx + padding, ty + 3);
        }
        ctx.restore();
      }

      let detectBusy = false;
      async function detectLoop() {
        if (detectBusy) { setTimeout(detectLoop, 50); return; }
        if (video.paused || video.ended) {
          setTimeout(detectLoop, 200);
          return;
        }
        detectBusy = true;
        // Downscale frame before detection to reduce CPU
        // Slightly higher resolution feed to improve distance detection
        if (!video.videoWidth || !video.videoHeight) {
          detectBusy = false;
          setTimeout(detectLoop, 150);
          return;
        }
        const detW = 480;
        const detH = Math.max(1, Math.round(video.videoHeight / video.videoWidth * detW));
        if (detW <= 0 || detH <= 0) {
          detectBusy = false;
          setTimeout(detectLoop, 150);
          return;
        }
        detectionCanvas.width = detW;
        detectionCanvas.height = detH;
        try { detectionCtx.drawImage(video, 0, 0, detW, detH); } catch(e) {}

        const detections = await faceapi.detectAllFaces(detectionCanvas, detectorOptions).withFaceLandmarks().withFaceDescriptors();
        ctx.clearRect(0,0,overlay.width,overlay.height);
        const ratioX = overlay.width / detW;
        const ratioY = overlay.height / detH;

        // Process ALL detections - track and display each one
        const processedLabels = new Set();
        const currentTrackKeys = new Set();

        const meta = [];
        for (let i = 0; i < detections.length; i++) {
          const det = detections[i];
          const score = (det.detection && typeof det.detection.score === 'number') ? det.detection.score : (det.detection.box.width * det.detection.box.height);
          if (score < MIN_CONFIDENCE_THRESHOLD) continue;

          let label = 'Unknown';
          let matchInfo = null;
          let displayName = 'Unknown';
          let studentDbId = null;
          let stuObj = null;
          let isUnknown = true;

          if (globalFaceMatcher && det.descriptor) {
            const match = globalFaceMatcher.findBestMatch(det.descriptor);
            if (match && match.label && match.label !== 'unknown' && match.distance < 0.6) {
              label = match.label;
              matchInfo = match;
              isUnknown = false;
              try {
                stuObj = await fetchStudentInfo(label);
                if (stuObj) {
                  displayName = stuObj.full_name || stuObj.student_id || label;
                  studentDbId = stuObj.id || null;
                }
              } catch (e) {
                console.warn('Failed to fetch student info for', label, e);
              }
            }
          }

          meta.push({ det, score, label, matchInfo, displayName, studentDbId, isUnknown });
        }

        // Deduplicate recognized faces by keeping best match per label
        const bestByLabel = new Map();
        const unknowns = [];
        for (const m of meta) {
          if (!m.isUnknown) {
            const key = m.studentDbId ? String(m.studentDbId) : String(m.label);
            const prev = bestByLabel.get(key);
            const better = !prev || (m.matchInfo && prev.matchInfo && m.matchInfo.distance < prev.matchInfo.distance) || (!prev.matchInfo && m.matchInfo) || (m.score > prev.score);
            if (better) bestByLabel.set(key, m);
          } else {
            unknowns.push(m);
          }
        }

        // Suppress overlapping unknown detections (simple NMS)
        function iou(a, b) {
          const ax1 = a.x, ay1 = a.y, ax2 = a.x + a.width, ay2 = a.y + a.height;
          const bx1 = b.x, by1 = b.y, bx2 = b.x + b.width, by2 = b.y + b.height;
          const ix1 = Math.max(ax1, bx1), iy1 = Math.max(ay1, by1);
          const ix2 = Math.min(ax2, bx2), iy2 = Math.min(ay2, by2);
          const iw = Math.max(0, ix2 - ix1), ih = Math.max(0, iy2 - iy1);
          const inter = iw * ih;
          const ua = (ax2 - ax1) * (ay2 - ay1) + (bx2 - bx1) * (by2 - by1) - inter;
          return ua > 0 ? inter / ua : 0;
        }
        unknowns.sort((a, b) => b.score - a.score);
        const keptUnknowns = [];
        for (const u of unknowns) {
          const boxU = u.det.detection.box;
          let overlap = false;
          for (const k of keptUnknowns) {
            const boxK = k.det.detection.box;
            if (iou(boxU, boxK) > 0.4) { overlap = true; break; }
          }
          if (!overlap) keptUnknowns.push(u);
        }

        const finalDetections = [...bestByLabel.values(), ...keptUnknowns];

        for (let i = 0; i < finalDetections.length; i++) {
          const item = finalDetections[i];
          const det = item.det;
          const score = item.score;

          let label = item.label;
          let matchInfo = item.matchInfo;
          let displayName = item.displayName;
          let studentDbId = item.studentDbId;
          let isUnknown = item.isUnknown;
          let lookupId = null;
          
          // DO NOT use OVERRIDE for unknown faces — only show/track recognized students
          // isUnknown stays true if no match found, so unknown faces remain "Unknown"
          // and won't update class log or trigger attendance
          
          if (!isUnknown) {
            lookupId = studentDbId ? String(studentDbId) : String(label);
          }
          const trackKey = !isUnknown && lookupId ? `r_${lookupId}` : `u_${i}`;
          currentTrackKeys.add(trackKey);

          const box = det.detection.box;
          const x = box.x * ratioX;
          const y = box.y * ratioY;
          const w = box.width * ratioX;
          const h = box.height * ratioY;
          const now = performance.now();

          // Get or create tracking box for this face
          if (!trackerLastBoxes[trackKey]) {
            trackerLastBoxes[trackKey] = {
              x,
              y,
              w,
              h,
              alpha: 0.85,
              lastSeen: now,
              labelText: '',
              isUnknown: true,
              lookupId: null
            };
          }

          // Smoothing interpolation
          const tracker = trackerLastBoxes[trackKey];
          tracker.x += (x - tracker.x) * TRACKER_SMOOTHING;
          tracker.y += (y - tracker.y) * TRACKER_SMOOTHING;
          tracker.w += (w - tracker.w) * TRACKER_SMOOTHING;
          tracker.h += (h - tracker.h) * TRACKER_SMOOTHING;
          tracker.alpha = Math.min(1, tracker.alpha + 0.2);
          tracker.lastSeen = now;
          tracker.isUnknown = isUnknown;
          tracker.labelText = isUnknown ? 'Unknown' : (displayName || label);
          tracker.lookupId = (!isUnknown && lookupId) ? lookupId : null;

          drawTrackedBox(tracker, tracker.labelText, tracker.isUnknown);
          
          // Update class log for recognized students in this section
          if (!isUnknown) {
            const lookupId = studentDbId ? String(studentDbId) : String(label);
            const existing = classLog.querySelector('tbody tr[data-student-db-id="' + lookupId + '"]');
            
            if (existing) {
              // Update existing row if student is in this section
              // Set Time-In ONLY on first capture
              if (!firstSeenMap[lookupId]) {
                firstSeenMap[lookupId] = Date.now();
                const timeInEl = existing.querySelector('.time-in');
                if (timeInEl) timeInEl.textContent = new Date(firstSeenMap[lookupId]).toLocaleTimeString();
                const isLate = !!(lateCutoff && firstSeenMap[lookupId] > lateCutoff.getTime());
                const status = isLate ? 'Late' : 'Present';
                statusMap[lookupId] = status;
                // Persist attendance with time-in when first captured
                if (studentDbId) saveAttendanceUpdate(studentDbId, { status, timeInTs: firstSeenMap[lookupId] });
              }
              // Track last seen time for Time-Out updates when leaving
              lastSeenMap[lookupId] = Date.now();
              const statusCell = existing.querySelector('td:nth-child(3)');
              if (statusCell) {
                const currentStatus = statusMap[lookupId] || 'Present';
                statusCell.textContent = currentStatus;
                statusCell.style.color = currentStatus === 'Late' ? '#d97706' : 'green';
              }
              const remarksCell = existing.querySelector('td:nth-child(6)');
              if (remarksCell) {
                const currentStatus = statusMap[lookupId] || 'Present';
                remarksCell.textContent = currentStatus === 'Late' ? 'Late' : 'Detected';
              }
            }
            
            processedLabels.add(lookupId);
          }
        }
        
        // For any previously seen label not in the current frame, set Time-Out to last seen
        prevSeenLabels.forEach(id => {
          if (!processedLabels.has(id)) {
            const row = classLog.querySelector('tbody tr[data-student-db-id="' + id + '"]');
            if (row) {
              const timeOutEl = row.querySelector('.time-out');
              if (timeOutEl) timeOutEl.textContent = new Date(lastSeenMap[id] || Date.now()).toLocaleTimeString();
              const remarksCell = row.querySelector('td:nth-child(6)');
              if (remarksCell) remarksCell.textContent = 'Left';
              // Persist attendance with time-out when student leaves frame
              const studentDbId = parseInt(id, 10);
              if (!Number.isNaN(studentDbId)) {
                const ts = lastSeenMap[id] || Date.now();
                saveAttendanceUpdate(studentDbId, { status: 'Present', timeOutTs: ts });
              }
            }
          }
        });
        // Update prevSeenLabels for next iteration
        prevSeenLabels = new Set(processedLabels);
        
        // Clean up tracking boxes for faces no longer detected
        const nowHold = performance.now();
        Object.keys(trackerLastBoxes).forEach(key => {
          if (currentTrackKeys.has(key)) return;
          const tracker = trackerLastBoxes[key];
          const isRecognized = key.startsWith('r_');
          if (isRecognized && tracker && (nowHold - (tracker.lastSeen || 0)) <= TRACKER_HOLD_MS) {
            // Keep the last box briefly to reduce jitter and avoid rapid timeout
            tracker.alpha = Math.max(TRACKER_MIN_ALPHA, (tracker.alpha || 0) - 0.08);
            drawTrackedBox(tracker, tracker.labelText, tracker.isUnknown);
            if (tracker.lookupId) {
              processedLabels.add(tracker.lookupId);
              lastSeenMap[tracker.lookupId] = Date.now();
            }
            return;
          }
          delete trackerLastBoxes[key];
        });

        detectBusy = false;
        setTimeout(detectLoop, 150);
      }

      detectLoop();
    }

    // Start recognition after HLS manifest leads to video playback
    document.getElementById('liveVideo').addEventListener('play', () => {
      // give models a small moment
      setTimeout(() => startRecognition().catch(e => console.error(e)), 500);
    });

    // Submit all attendance (Present from detection + Absent for undetected students)
    document.getElementById('submitAttendanceBtn').addEventListener('click', async () => {
      if (!SECTION_NAME) {
        alert('No section specified. Please provide year and section.');
        return;
      }

      const rows = document.querySelectorAll('#class-log tbody tr[data-student-db-id]');
      const toSubmit = [];

      for (const row of rows) {
        const dbId = parseInt(row.getAttribute('data-student-db-id'), 10);
        const statusCell = row.querySelector('td:nth-child(3)');
        const status = statusCell ? statusCell.textContent.trim() : 'Absent';
        const timeInCell = row.querySelector('.time-in');
        const timeOutCell = row.querySelector('.time-out');
        const timeIn = timeInCell && timeInCell.textContent !== '-' ? timeInCell.textContent : null;
        const timeOut = timeOutCell && timeOutCell.textContent !== '-' ? timeOutCell.textContent : null;

        toSubmit.push({
          student_db_id: dbId,
          status: status === 'Present' ? 'Present' : 'Absent',
          section: SECTION_NAME,
          time_in: timeIn,
          time_out: timeOut
        });
      }

      // Batch submit all records
      let submitted = 0;
      let failed = 0;

      for (const payload of toSubmit) {
        try {
          const res = await fetch('/attendance-monitoring/crud/save_manual_attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
          });
          const json = await res.json();
          if (json.success) {
            submitted++;
          } else {
            failed++;
          }
        } catch (err) {
          console.warn('Submit failed for student', payload.student_db_id, err);
          failed++;
        }
      }

      alert(`Attendance submitted! ${submitted} records saved${failed > 0 ? `, ${failed} failed` : ''}.`);
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
