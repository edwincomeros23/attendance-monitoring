<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: /attendance-monitoring/auth/signin.php");
    exit;
}

// Fetch user details and role
if (file_exists('../db.php')) include '../db.php';
$userName = 'User';
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher';
$displayRole = ucfirst($userRole); // Capitalize first letter

if (isset($conn) && isset($_SESSION['username'])) {
    // Try to get name from teachers table if it's a teacher
    if ($userRole === 'teacher') {
        $stmt = $conn->prepare("SELECT first_name, last_name FROM teachers WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param('s', $_SESSION['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $userName = trim($row['first_name'] . ' ' . $row['last_name']);
            }
            $stmt->close();
        }
    } else {
        // For admin, use username or default
        $userName = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
    }
}

$sectionsConfig = file_exists(__DIR__ . '/../config/sections.php')
    ? include __DIR__ . '/../config/sections.php'
    : ['7' => ['Ruby', 'Mahogany', 'Sunflower'], '8' => ['Ruby', 'Mahogany', 'Sunflower']];

// Ensure attendance table exists for aggregates
if (isset($conn)) {
    $create_sql = "CREATE TABLE IF NOT EXISTS attendance (
      id INT AUTO_INCREMENT PRIMARY KEY,
      student_id INT NOT NULL,
      section VARCHAR(100) DEFAULT NULL,
      date DATE NOT NULL,
      status VARCHAR(50) DEFAULT NULL,
      time_in TIME DEFAULT NULL,
      time_out TIME DEFAULT NULL,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_student_date (student_id, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $conn->query($create_sql);
}

// Metrics
$total_year_levels = 0;
$presentToday = 0;
$absentToday = 0;
$lateToday = 0;
$today = date('Y-m-d');
$schoolYear = isset($_SESSION['school_year']) ? trim($_SESSION['school_year']) : '';
$hasSchoolYearStudents = false;
$hasSchoolYearAttendance = false;
$metricsDate = $today;
$metricsDateLabel = 'Today';
$dashboardDate = isset($_SESSION['dashboard_date']) ? trim($_SESSION['dashboard_date']) : '';
$dashboardStatus = isset($_SESSION['dashboard_status']) ? trim($_SESSION['dashboard_status']) : '';

if (isset($conn)) {
    if ($col = $conn->query("SHOW COLUMNS FROM students LIKE 'school_year'")) {
        $hasSchoolYearStudents = $col->num_rows > 0;
        $col->free();
    }
    if ($col = $conn->query("SHOW COLUMNS FROM attendance LIKE 'school_year'")) {
        $hasSchoolYearAttendance = $col->num_rows > 0;
        $col->free();
    }
}

// Recent notifications for header dropdown
$notifItems = [];
if (isset($conn)) {
    $sql = "SELECT nl.student_id, nl.channel, nl.event_type, nl.status, nl.created_at, s.full_name, s.student_id AS student_code
            FROM notification_logs nl
            LEFT JOIN students s ON nl.student_id = s.id
            ORDER BY nl.created_at DESC
            LIMIT 6";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $notifItems[] = $row;
        }
        $res->free();
    }
}

if (isset($conn)) {
    // Distinct year levels
    if ($hasSchoolYearStudents && $schoolYear !== '') {
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT year_level) AS cnt FROM students WHERE school_year = ?");
        if ($stmt) {
            $stmt->bind_param('s', $schoolYear);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) $total_year_levels = (int)$row['cnt'];
            $stmt->close();
        }
    } else if ($res = $conn->query("SELECT COUNT(DISTINCT year_level) AS cnt FROM students")) {
        $row = $res->fetch_assoc();
        $total_year_levels = isset($row['cnt']) ? (int)$row['cnt'] : 0;
        $res->free();
    }

    // Attendance counts for today; fallback to latest available date
    $hasToday = false;
    if ($hasSchoolYearAttendance && $schoolYear !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM attendance WHERE date = ? AND school_year = ?");
        if ($stmt) {
            $stmt->bind_param('ss', $today, $schoolYear);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) $hasToday = ((int)$row['cnt'] > 0);
            $stmt->close();
        }
    } else if ($hasSchoolYearStudents && $schoolYear !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM attendance a INNER JOIN students s ON a.student_id = s.id WHERE a.date = ? AND s.school_year = ?");
        if ($stmt) {
            $stmt->bind_param('ss', $today, $schoolYear);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) $hasToday = ((int)$row['cnt'] > 0);
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM attendance WHERE date = ?");
        if ($stmt) {
            $stmt->bind_param('s', $today);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) $hasToday = ((int)$row['cnt'] > 0);
            $stmt->close();
        }
    }

    if ($dashboardDate !== '') {
        $metricsDate = $dashboardDate;
        $metricsDateLabel = date('M j, Y', strtotime($metricsDate));
        $hasToday = true;
    }

    if (!$hasToday) {
        if ($hasSchoolYearAttendance && $schoolYear !== '') {
            $stmt = $conn->prepare("SELECT MAX(date) AS d FROM attendance WHERE school_year = ?");
            if ($stmt) {
                $stmt->bind_param('s', $schoolYear);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) $metricsDate = $row['d'] ?: $today;
                $stmt->close();
            }
        } else if ($hasSchoolYearStudents && $schoolYear !== '') {
            $stmt = $conn->prepare("SELECT MAX(a.date) AS d FROM attendance a INNER JOIN students s ON a.student_id = s.id WHERE s.school_year = ?");
            if ($stmt) {
                $stmt->bind_param('s', $schoolYear);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) $metricsDate = $row['d'] ?: $today;
                $stmt->close();
            }
        } else {
            if ($res = $conn->query("SELECT MAX(date) AS d FROM attendance")) {
                $row = $res->fetch_assoc();
                $metricsDate = $row['d'] ?: $today;
                $res->free();
            }
        }
        if ($metricsDate && $metricsDate !== $today) {
            $metricsDateLabel = date('M j, Y', strtotime($metricsDate));
        }
    }

    if ($dashboardStatus !== '') {
        if ($hasSchoolYearAttendance && $schoolYear !== '') {
            $stmt = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM attendance WHERE date = ? AND school_year = ? AND status = ? GROUP BY status");
            if ($stmt) {
                $stmt->bind_param('sss', $metricsDate, $schoolYear, $dashboardStatus);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $status = $row['status'];
                    $cnt = (int)$row['cnt'];
                    if ($status === 'Present') $presentToday = $cnt;
                    elseif ($status === 'Absent') $absentToday = $cnt;
                    elseif ($status === 'Late') $lateToday = $cnt;
                }
                $stmt->close();
            }
        } else if ($hasSchoolYearStudents && $schoolYear !== '') {
            $stmt = $conn->prepare("SELECT a.status, COUNT(*) AS cnt FROM attendance a INNER JOIN students s ON a.student_id = s.id WHERE a.date = ? AND s.school_year = ? AND a.status = ? GROUP BY a.status");
            if ($stmt) {
                $stmt->bind_param('sss', $metricsDate, $schoolYear, $dashboardStatus);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $status = $row['status'];
                    $cnt = (int)$row['cnt'];
                    if ($status === 'Present') $presentToday = $cnt;
                    elseif ($status === 'Absent') $absentToday = $cnt;
                    elseif ($status === 'Late') $lateToday = $cnt;
                }
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM attendance WHERE date = ? AND status = ? GROUP BY status");
            if ($stmt) {
                $stmt->bind_param('ss', $metricsDate, $dashboardStatus);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $status = $row['status'];
                    $cnt = (int)$row['cnt'];
                    if ($status === 'Present') $presentToday = $cnt;
                    elseif ($status === 'Absent') $absentToday = $cnt;
                    elseif ($status === 'Late') $lateToday = $cnt;
                }
                $stmt->close();
            }
        }
    } else if ($hasSchoolYearAttendance && $schoolYear !== '') {
        $stmt = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM attendance WHERE date = ? AND school_year = ? GROUP BY status");
        if ($stmt) {
            $stmt->bind_param('ss', $metricsDate, $schoolYear);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $status = $row['status'];
                $cnt = (int)$row['cnt'];
                if ($status === 'Present') $presentToday = $cnt;
                elseif ($status === 'Absent') $absentToday = $cnt;
                elseif ($status === 'Late') $lateToday = $cnt;
            }
            $stmt->close();
        }
    } else if ($hasSchoolYearStudents && $schoolYear !== '') {
        $stmt = $conn->prepare("SELECT a.status, COUNT(*) AS cnt FROM attendance a INNER JOIN students s ON a.student_id = s.id WHERE a.date = ? AND s.school_year = ? GROUP BY a.status");
        if ($stmt) {
            $stmt->bind_param('ss', $metricsDate, $schoolYear);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $status = $row['status'];
                $cnt = (int)$row['cnt'];
                if ($status === 'Present') $presentToday = $cnt;
                elseif ($status === 'Absent') $absentToday = $cnt;
                elseif ($status === 'Late') $lateToday = $cnt;
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM attendance WHERE date = ? GROUP BY status");
        if ($stmt) {
            $stmt->bind_param('s', $metricsDate);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $status = $row['status'];
                $cnt = (int)$row['cnt'];
                if ($status === 'Present') $presentToday = $cnt;
                elseif ($status === 'Absent') $absentToday = $cnt;
                elseif ($status === 'Late') $lateToday = $cnt;
            }
            $stmt->close();
        }
    }
}

// Additional dashboard insights
$totalStudents = 0;
$attendanceRateToday = 0.0;
$onTimeRate = 0.0;
$trendLabels = [];
$trendRates = [];
$trendPresent = [];
$trendLate = [];
$trendAbsent = [];
$alwaysPresentLeaders = [];
$topSubjects = [];
$sectionLeaders = [];

function get_total_students($conn, $schoolYear, $hasSchoolYearStudents) {
    if (!$conn) return 0;
    $total = 0;
    if ($hasSchoolYearStudents && $schoolYear !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM students WHERE school_year = ?");
        if ($stmt) {
            $stmt->bind_param('s', $schoolYear);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) $total = (int)$row['cnt'];
            $stmt->close();
        }
    } else {
        if ($res = $conn->query("SELECT COUNT(*) AS cnt FROM students")) {
            $row = $res->fetch_assoc();
            $total = isset($row['cnt']) ? (int)$row['cnt'] : 0;
            $res->free();
        }
    }
    return $total;
}

function get_attendance_counts($conn, $date, $schoolYear, $hasSchoolYearAttendance, $hasSchoolYearStudents) {
    $counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0];
    if (!$conn || !$date) return $counts;
    if ($hasSchoolYearAttendance && $schoolYear !== '') {
        $stmt = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM attendance WHERE date = ? AND school_year = ? GROUP BY status");
        if ($stmt) {
            $stmt->bind_param('ss', $date, $schoolYear);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $status = $row['status'];
                if (isset($counts[$status])) $counts[$status] = (int)$row['cnt'];
            }
            $stmt->close();
        }
    } else if ($hasSchoolYearStudents && $schoolYear !== '') {
        $stmt = $conn->prepare("SELECT a.status, COUNT(*) AS cnt FROM attendance a INNER JOIN students s ON a.student_id = s.id WHERE a.date = ? AND s.school_year = ? GROUP BY a.status");
        if ($stmt) {
            $stmt->bind_param('ss', $date, $schoolYear);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $status = $row['status'];
                if (isset($counts[$status])) $counts[$status] = (int)$row['cnt'];
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM attendance WHERE date = ? GROUP BY status");
        if ($stmt) {
            $stmt->bind_param('s', $date);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $status = $row['status'];
                if (isset($counts[$status])) $counts[$status] = (int)$row['cnt'];
            }
            $stmt->close();
        }
    }
    return $counts;
}

if (isset($conn)) {
    $totalStudents = get_total_students($conn, $schoolYear, $hasSchoolYearStudents);
    $attendedToday = $presentToday + $lateToday;
    $attendanceRateToday = $totalStudents > 0 ? round(($attendedToday / $totalStudents) * 100, 1) : 0.0;
    $onTimeDenom = $presentToday + $lateToday;
    $onTimeRate = $onTimeDenom > 0 ? round(($presentToday / $onTimeDenom) * 100, 1) : 0.0;

    $baseDate = $metricsDate ?: $today;
    $trendStart = new DateTime($baseDate);
    $trendStart->modify('-6 days');
    for ($i = 0; $i < 7; $i++) {
        $d = $trendStart->format('Y-m-d');
        $label = date('M j', strtotime($d));
        $counts = get_attendance_counts($conn, $d, $schoolYear, $hasSchoolYearAttendance, $hasSchoolYearStudents);
        $present = $counts['Present'];
        $late = $counts['Late'];
        $absent = $counts['Absent'];
        $rate = $totalStudents > 0 ? round((($present + $late) / $totalStudents) * 100, 1) : 0.0;
        $trendLabels[] = $label;
        $trendRates[] = $rate;
        $trendPresent[] = $present;
        $trendLate[] = $late;
        $trendAbsent[] = $absent;
        $trendStart->modify('+1 day');
    }

    $rangeStart = (new DateTime($baseDate))->modify('-6 days')->format('Y-m-d');
    $rangeEnd = $baseDate;
    $presentSql = "SELECT a.student_id, s.full_name, s.student_id AS student_code, COUNT(*) AS present_count
                   FROM attendance a
                   INNER JOIN students s ON a.student_id = s.id
                   WHERE a.status = 'Present' AND a.date BETWEEN ? AND ?";
    $presentTypes = 'ss';
    $presentParams = [$rangeStart, $rangeEnd];
    if ($hasSchoolYearAttendance && $schoolYear !== '') {
        $presentSql .= " AND a.school_year = ?";
        $presentTypes .= 's';
        $presentParams[] = $schoolYear;
    } else if ($hasSchoolYearStudents && $schoolYear !== '') {
        $presentSql .= " AND s.school_year = ?";
        $presentTypes .= 's';
        $presentParams[] = $schoolYear;
    }
    $presentSql .= " GROUP BY a.student_id, s.full_name, s.student_id ORDER BY present_count DESC, s.full_name ASC LIMIT 5";
    $stmt = $conn->prepare($presentSql);
    if ($stmt) {
        $stmt->bind_param($presentTypes, ...$presentParams);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $alwaysPresentLeaders[] = $row;
        }
        $stmt->close();
    }

    if ($res = $conn->query("SELECT subject_name, COUNT(*) AS cnt FROM curriculum WHERE subject_name IS NOT NULL AND subject_name <> '' GROUP BY subject_name ORDER BY cnt DESC, subject_name ASC LIMIT 5")) {
        while ($row = $res->fetch_assoc()) {
            $topSubjects[] = $row;
        }
        $res->free();
    }

    $reportSections = [];
    foreach ($sectionsConfig as $grade => $sections) {
        foreach ($sections as $sectionName) {
            $reportSections[] = $grade . '-' . $sectionName;
        }
    }
    $reportSections = array_values(array_unique($reportSections));
    $sectionSql = "SELECT
                    CASE
                      WHEN section REGEXP '^[0-9]+[ -]' THEN section
                      ELSE CONCAT(REPLACE(year_level, 'Grade ', ''), '-', section)
                    END AS section_label,
                    COUNT(*) AS cnt
                   FROM students
                   WHERE section IS NOT NULL AND section <> ''";
    $sectionTypes = '';
    $sectionParams = [];
    if (!empty($reportSections)) {
        $sectionPlaceholders = implode(',', array_fill(0, count($reportSections), '?'));
        $sectionSql .= " AND (CASE WHEN section REGEXP '^[0-9]+[ -]' THEN section ELSE CONCAT(REPLACE(year_level, 'Grade ', ''), '-', section) END) IN ($sectionPlaceholders)";
        $sectionTypes .= str_repeat('s', count($reportSections));
        $sectionParams = array_merge($sectionParams, $reportSections);
    }
    if ($hasSchoolYearStudents && $schoolYear !== '') {
        $sectionSql .= " AND school_year = ?";
        $sectionTypes .= 's';
        $sectionParams[] = $schoolYear;
    }
    $sectionSql .= " GROUP BY section_label ORDER BY cnt DESC, section_label ASC LIMIT 5";
    $stmt = $conn->prepare($sectionSql);
    if ($stmt) {
        if ($sectionTypes !== '') {
            $stmt->bind_param($sectionTypes, ...$sectionParams);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $sectionLeaders[] = [
                'section' => $row['section_label'],
                'cnt' => $row['cnt']
            ];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>WMSU Attendance Tracking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="../style.css" />
    <style>
        /* Modal (Pop-up) styles */
        .modal {
            display: none; /* This hides the modal by default */
            position: fixed; /* Positions it over the other content */
            z-index: 1000; /* Ensures it's on top of everything */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4); /* Dark overlay */
        }
        .card{
          background: #b30000;
        }
header {
      background-color: #b30000; color: white; padding: 10px 20px;
      border-radius: 8px; display: flex; justify-content: space-between; align-items: center;
    }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        #submit-btn {
            width: 100%;
            padding: 10px;
            background-color: #990000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        #submit-btn:hover {
            background-color: #990000;
        }
        
        /* New Table Button Styles */
        .edit-row {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            font-size: 0.85em;
            background-color: #0275d8;
            margin-right: 5px;
        }
        .delete-row {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            font-size: 0.85em;
            background-color: #dc3545;
        }
        .insights {
            margin-top: 18px;
            background: linear-gradient(135deg, #fff4f0 0%, #fff7e6 60%, #fef0ea 100%);
            border: 1px solid #f2d7c9;
            border-radius: 14px;
            padding: 16px;
            position: relative;
            overflow: hidden;
        }
        .insights::before {
            content: '';
            position: absolute;
            width: 260px;
            height: 260px;
            right: -120px;
            top: -120px;
            background: radial-gradient(circle, rgba(179,0,0,0.14) 0%, rgba(179,0,0,0) 70%);
        }
        .insights::after {
            content: '';
            position: absolute;
            width: 220px;
            height: 220px;
            left: -110px;
            bottom: -110px;
            background: radial-gradient(circle, rgba(255,173,79,0.18) 0%, rgba(255,173,79,0) 68%);
        }
        .insights-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .insights-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            color: #4a0c0c;
        }
        .insights-sub {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 13px;
            color: #6b3d33;
            margin-top: 2px;
        }
        .insights-badge {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 999px;
            background: #b30000;
            color: #fff;
            box-shadow: 0 6px 18px rgba(179,0,0,0.18);
        }
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
            margin-top: 14px;
            position: relative;
            z-index: 1;
        }
        .insight-card {
            background: #fff;
            border: 1px solid #f0e6e1;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 8px 24px rgba(179,0,0,0.06);
        }
        .insight-card h4 {
            margin: 0 0 8px 0;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 15px;
            color: #4a0c0c;
        }
        .rate-card .rate-label {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 13px;
            color: #7a4c40;
        }
        .rate-card .rate-value {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 36px;
            font-weight: 700;
            color: #b30000;
            margin-top: 6px;
        }
        .rate-card .rate-detail {
            font-size: 12px;
            color: #7a4c40;
            margin-top: 4px;
        }
        .chart-card canvas {
            width: 100% !important;
            height: 220px !important;
        }
        .chart-card.wide canvas {
            height: 240px !important;
        }
        .chart-card.tall canvas {
            height: 260px !important;
        }
        .insight-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .insight-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #fff7f2;
            border: 1px dashed #f2c9b8;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            color: #4a0c0c;
        }
        .insight-pill {
            font-size: 12px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 999px;
            background: #ffe3d7;
            color: #b30000;
            white-space: nowrap;
        }
        .insight-empty {
            font-size: 13px;
            color: #7a4c40;
            padding: 6px 0;
        }
        .metric-split {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 6px;
            font-size: 12px;
            color: #7a4c40;
        }
        .metric-split span {
            background: #fff3ec;
            padding: 4px 8px;
            border-radius: 999px;
            border: 1px solid #f2d7c9;
        }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    <div class="main">
        <header>
            <h2>Wmsu Attendance Tracking</h2>
            <div class="admin-info"> 
                <div class="notif-wrapper" style="position:relative;display:inline-block;margin-right:12px;">
                                        <button id="notifBtn" aria-label="Notifications" title="Notifications" style="background:transparent;border:none;color:#fff;font-size:18px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;position:relative;">
                                            <span class="bell-wrap" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:transparent;padding:0;box-shadow:none;">
                                                <!-- bell icon: use solid white fill for strong contrast on red header -->
                                                <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
                                                    <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
                                                </svg>
                                            </span>
                                            <span id="notifCount" style="background:#ff4d4d;color:#fff;border-radius:999px;padding:2px 6px;font-size:12px;position:absolute;right:-6px;top:-6px;border:2px solid #b30000;"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </button>
                    <div id="notifDropdown" style="display:none;position:absolute;right:0;top:34px;min-width:320px;background:#fff;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,0.12);overflow:hidden;z-index:2000">
                        <div style="padding:10px 12px;border-bottom:1px solid #eee;font-weight:700">Notifications</div>
                                                <div style="max-height:260px;overflow:auto">
                                                        <?php if (empty($notifItems)): ?>
                                                            <div style="padding:12px;color:#666">No notifications yet.</div>
                                                        <?php else: ?>
                                                            <?php foreach ($notifItems as $n): ?>
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
                                                                <a href="notifications.php" class="notif-item" style="display:block;padding:10px 12px;border-bottom:1px solid #fafafa;color:#333;text-decoration:none">
                                                                    <div style="font-weight:700"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
                                                                    <div style="font-size:13px;color:#666"><?php echo htmlspecialchars($detail, ENT_QUOTES, 'UTF-8'); ?></div>
                                                                    <div style="font-size:12px;color:#999"><?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></div>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                </div>
                        <div style="padding:8px;text-align:center;background:#f7f7f7"><a href="notifications.php" style="color:#b30000;text-decoration:none">View all</a></div>
                    </div>
                </div>
                <div class="admin-icon" style="display:inline-flex;align-items:center;gap:8px;color:#fff;font-weight:700">
                    <!-- user icon inline SVG fallback: solid white fill for stronger contrast -->
                    <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="12" cy="8" r="3" fill="#ffffff" />
                        <path d="M4 20c0-4 4-6 8-6s8 2 8 6" fill="#ffffff" />
                    </svg>
                    <span>Admin</span>
                </div>
            </div>
        </header>
        <section class="overview">
            <div class="welcome-row">
                <h3 class="welcome-text">Welcome, <?php echo htmlspecialchars($displayRole . ' ' . $userName, ENT_QUOTES, 'UTF-8'); ?></h3>
                <span id="dateTime" class="datetime"></span>
            </div>
            <div class="cards">
                <div class="card">
                    <div class="card-label">Total Year Level</div>
                    <span><?php echo htmlspecialchars($total_year_levels, ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="students.php" class="more-info">More info →</a>
                </div>
                <div class="card">
                    <div class="card-label">Present <?php echo htmlspecialchars($metricsDateLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                    <span><?php echo htmlspecialchars($presentToday, ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="attendance_report.php?filter=day&amp;date=<?php echo urlencode($metricsDate); ?>&amp;status=Present" class="more-info">More info →</a>
                </div>
                <div class="card">
                    <div class="card-label">Absent <?php echo htmlspecialchars($metricsDateLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                    <span><?php echo htmlspecialchars($absentToday, ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="attendance_report.php?filter=day&amp;date=<?php echo urlencode($metricsDate); ?>&amp;status=Absent" class="more-info">More info →</a>
                </div>
                <div class="card">
                    <div class="card-label">Late <?php echo htmlspecialchars($metricsDateLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                    <span><?php echo htmlspecialchars($lateToday, ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="attendance_report.php?filter=day&amp;date=<?php echo urlencode($metricsDate); ?>&amp;status=Late" class="more-info">More info →</a>
                </div>
            </div>
        </section>
        <section class="attendance">
            <div class="attendance-header">
                <h3>Students by Section</h3>
            </div>

            <div class="sections-grid">
                <!-- Example section cards; replace with dynamic data as needed -->
                <div class="section-card" data-section="Ruby" data-year="7">
                    <div class="section-title">Grade - 7 <span class="section-datetime" aria-hidden="true"></span></div>
                    <ul class="section-list">
                        <li>Ruby <a class="view-section" href="studentattendance.php?section=Ruby" title="View Ruby">View</a></li>
                        <li>Amethyst <a class="view-section" href="studentattendance.php?section=Amethyst" title="View Amethyst">View</a></li>
                        <li>Garnet <a class="view-section" href="studentattendance.php?section=Garnet" title="View Garnet">View</a></li>
                        <li>Sapphire <a class="view-section" href="studentattendance.php?section=Sapphire" title="View Sapphire">View</a></li>
                    </ul>
                </div>
                <div class="section-card" data-section="Emerald" data-year="8">
                    <div class="section-title">Grade - 8 <span class="section-datetime" aria-hidden="true"></span></div>
                    <ul class="section-list">
                        <li>Emerald <a class="view-section" href="studentattendance.php?section=Emerald">View</a></li>
                        <li>Peridot <a class="view-section" href="studentattendance.php?section=Peridot">View</a></li>
                    </ul>
                </div>
            </div>

            <!-- Section Attendance Modal -->
            <div id="sectionModal" class="modal" aria-hidden="true">
                <div class="modal-content">
                    <button class="close-btn" type="button" aria-label="Close section dialog">&times;</button>
                    <h3 id="sectionModalTitle">Section Attendance</h3>
                    <div id="sectionAttendanceList">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </section>
        <section class="insights" aria-label="Attendance insights">
            <div class="insights-header">
                <div>
                    <h3 class="insights-title">Attendance Insights</h3>
                    <div class="insights-sub">Snapshot for <?php echo htmlspecialchars($metricsDateLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="insights-badge">Last 7 days</div>
            </div>
            <div class="insights-grid">
                <div class="insight-card rate-card">
                    <div class="rate-label">Attendance rate</div>
                    <div class="rate-value"><?php echo htmlspecialchars(number_format($attendanceRateToday, 1), ENT_QUOTES, 'UTF-8'); ?>%</div>
                    <div class="rate-detail">Present + Late: <?php echo htmlspecialchars($presentToday + $lateToday, ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars($totalStudents, ENT_QUOTES, 'UTF-8'); ?> students</div>
                    <div class="metric-split">
                        <span>On-time: <?php echo htmlspecialchars(number_format($onTimeRate, 1), ENT_QUOTES, 'UTF-8'); ?>%</span>
                        <span>Late: <?php echo htmlspecialchars(number_format(100 - $onTimeRate, 1), ENT_QUOTES, 'UTF-8'); ?>%</span>
                    </div>
                </div>
                <div class="insight-card chart-card">
                    <h4>Present vs Late vs Absent</h4>
                    <canvas id="statusDonut" aria-label="Attendance status chart" role="img"></canvas>
                </div>
                <div class="insight-card chart-card wide">
                    <h4>Attendance rate trend</h4>
                    <canvas id="trendLine" aria-label="Attendance trend chart" role="img"></canvas>
                </div>
                <div class="insight-card chart-card tall">
                    <h4>Weekly status volume</h4>
                    <canvas id="trendBars" aria-label="Weekly status volume chart" role="img"></canvas>
                </div>
                <div class="insight-card">
                    <h4>Top 5 always present students</h4>
                    <?php if (empty($alwaysPresentLeaders)): ?>
                        <div class="insight-empty">No present records in this range.</div>
                    <?php else: ?>
                        <ul class="insight-list">
                            <?php foreach ($alwaysPresentLeaders as $row): ?>
                                <?php
                                    $name = !empty($row['full_name']) ? $row['full_name'] : 'Unknown Student';
                                    $code = !empty($row['student_code']) ? $row['student_code'] : '';
                                    $displayName = $code !== '' ? $name . ' (' . $code . ')' : $name;
                                ?>
                                <li>
                                    <span><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="insight-pill"><?php echo htmlspecialchars($row['present_count'], ENT_QUOTES, 'UTF-8'); ?> present</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="insight-card">
                    <h4>Most scheduled subjects</h4>
                    <?php if (empty($topSubjects)): ?>
                        <div class="insight-empty">No subjects found.</div>
                    <?php else: ?>
                        <ul class="insight-list">
                            <?php foreach ($topSubjects as $row): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="insight-pill"><?php echo htmlspecialchars($row['cnt'], ENT_QUOTES, 'UTF-8'); ?> schedules</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="insight-card">
                    <h4>Sections with most students</h4>
                    <?php if (empty($sectionLeaders)): ?>
                        <div class="insight-empty">No sections found.</div>
                    <?php else: ?>
                        <ul class="insight-list">
                            <?php foreach ($sectionLeaders as $row): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($row['section'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="insight-pill"><?php echo htmlspecialchars($row['cnt'], ENT_QUOTES, 'UTF-8'); ?> students</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Extra styles for section cards (injected here for convenience)
            const style = document.createElement('style');
            style.textContent = `
                    /* Layout tweaks to prevent page scrolling and reduce vertical space */
                    html,body{height:100%;margin:0;padding:0}
                    /* Match camera.php: make .main flexible with consistent padding */
                    .main{flex: 1; padding: 20px; box-sizing: border-box; min-height:100%; overflow:auto}
                    .welcome-row{display:flex;justify-content:space-between;align-items:center}
                    /* Stat cards: evenly fill the row so first/last align to container edges */
                    .cards{display:flex;gap:20px;margin-top:20px;align-items:stretch}
                    .card{flex:1 1 0;padding:16px 18px;min-height:120px;border-radius:8px;display:flex;flex-direction:column;justify-content:space-between}
                    .card .card-label{font-size:0.95rem}
                    .card span{font-size:36px;font-weight:700}

                    .sections-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-top:14px}
                    .section-card{background:#fff;border-radius:8px;border:1px solid #e8e8e8;padding:10px;box-shadow:0 4px 10px rgba(0,0,0,0.04)}
                    .section-title{background:#b30000;color:#fff;padding:8px;border-radius:6px;font-weight:700;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center}
                    .section-title .section-datetime{font-weight:400;font-size:0.82em;opacity:0.95;margin-left:10px}
                    .section-list{list-style:none;padding:0;margin:0}
                    .section-list li{display:flex;justify-content:space-between;align-items:center;padding:8px 6px;border-bottom:1px solid #f2f2f2}
                    /* View link styled as a prominent filled red pill */
                    .section-list li a.view-section{background:#b30000;color:#fff;padding:8px 14px;border-radius:999px;text-decoration:none;display:inline-flex;gap:8px;align-items:center;border:1px solid rgba(179,0,0,0.08);transition:transform .12s ease,box-shadow .12s ease;box-shadow:0 8px 22px rgba(179,0,0,0.08);font-weight:500}
                    .section-list li a.view-section svg{color:#fff;fill:currentColor}
                    .section-list li a.view-section:hover{transform:translateY(-3px);box-shadow:0 18px 48px rgba(179,0,0,0.14)}
                    .section-list li a.view-section:focus{outline:3px solid rgba(255,255,255,0.12);outline-offset:3px}
                    /* notification bell wrapper and badge adjustments */
                    .bell-wrap{width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center}
                    #notifCount{font-weight:700}
                    .section-list li button{background:#b30000;color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer}

                    /* Constrain attendance area so page itself doesn't scroll; internal scroll if needed */
                    .attendance{max-height:calc(100vh - 220px);overflow:auto;padding-right:6px}

                    /* Modal layout: center with flex and limit content height so page doesn't scroll */
                    #sectionModal{display:none;position:fixed;inset:0;z-index:1000;background-color:rgba(0,0,0,0.4);align-items:center;justify-content:center}
                    #sectionModal .modal-content{background-color:#fefefe;padding:20px;border-radius:8px;box-shadow:0 5px 15px rgba(0,0,0,0.3);width:90%;max-width:700px;max-height:calc(100vh - 120px);overflow-y:auto;position:relative}
                    #sectionModal .close-btn{position:absolute;right:14px;top:10px;z-index:10001;pointer-events:auto;cursor:pointer;background:transparent;border:none;font-size:22px}
            `;
            document.head.appendChild(style);

            // Section modal logic
            const sectionModal = document.getElementById('sectionModal');
            const sectionModalTitle = document.getElementById('sectionModalTitle');
            const sectionAttendanceList = document.getElementById('sectionAttendanceList');
            // student-modal may be absent on this page; obtain safely
            const studentModal = document.getElementById('student-modal');
            const modalTitle = studentModal ? studentModal.querySelector('#modal-title') : null;
            const studentForm = studentModal ? studentModal.querySelector('#student-form') : null;
            const submitBtn = studentModal ? studentModal.querySelector('#submit-btn') : null;
            const studentCloseBtn = studentModal ? studentModal.querySelector('.close-btn') : null;

            // Provide a safe alias `modal` for legacy usage in the script.
            const modal = studentModal || null;

            const addBtn = document.querySelector('.add');
            const editBtn = document.querySelector('.edit');
            const tableBody = document.querySelector('tbody');

            // View section links are anchors now; do not intercept clicks so browser navigates normally.

            function openSectionModal(sectionName){
                sectionModalTitle.textContent = `Attendance — ${sectionName}`;
                // sample content — replace with real data from server if desired
                sectionAttendanceList.innerHTML = `<table style="width:100%"><thead><tr><th>Name</th><th>Status</th><th>Time</th></tr></thead><tbody><tr><td>Juan Dela Cruz</td><td class='present'>Present</td><td>8:00 AM</td></tr><tr><td>Jane Smith</td><td class='present'>Present</td><td>8:15 AM</td></tr></tbody></table>`;
                // show modal as flex so it centers and the page itself doesn't scroll
                sectionModal.style.display = 'flex';
            }

            // Real-time date/time updater for header and each section
            function updateDateTime() {
                const now = new Date();
                // Format options
                const opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
                const datePart = now.toLocaleDateString(undefined, opts);
                const timePart = now.toLocaleTimeString();
                const header = document.getElementById('dateTime');
                if (header) header.textContent = `${datePart} ${timePart}`;

                // Update each section's datetime
                document.querySelectorAll('.section-datetime').forEach(span => {
                    span.textContent = `${datePart} ${timePart}`;
                });
            }

            // Start updater
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Notification dropdown toggle
            const notifBtn = document.getElementById('notifBtn');
            const notifDropdown = document.getElementById('notifDropdown');
            if (notifBtn && notifDropdown) {
                notifBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    notifDropdown.style.display = notifDropdown.style.display === 'block' ? 'none' : 'block';
                });
                document.addEventListener('click', (ev) => { if (!notifDropdown.contains(ev.target) && ev.target !== notifBtn) notifDropdown.style.display = 'none'; });
            }

            if (addBtn) {
                addBtn.addEventListener('click', () => {
                    // ensure the modal and related nodes exist before using them
                    if (!modal) return console.warn('student modal not present');
                    modal.style.display = 'block';
                    if (modalTitle) modalTitle.textContent = 'Add New Student';
                    if (submitBtn) submitBtn.textContent = 'Add Student';
                    if (studentForm) studentForm.reset();
                });
            }

            if (editBtn) {
                editBtn.addEventListener('click', () => {
                    if (!tableBody) return console.warn('table body not present');
                    const firstRow = tableBody.querySelector('tr');
                    if (firstRow) {
                        const data = firstRow.dataset;
                        const f = document.getElementById('firstName'); if (f) f.value = data.firstname || '';
                        const m = document.getElementById('middleName'); if (m) m.value = data.middlename || '';
                        const l = document.getElementById('lastName'); if (l) l.value = data.lastname || '';
                        const y = document.getElementById('year'); if (y) y.value = data.year || '';
                        const s = document.getElementById('section'); if (s) s.value = data.section || '';
                    }
                    if (!modal) return console.warn('student modal not present');
                    modal.style.display = 'block';
                    if (modalTitle) modalTitle.textContent = 'Edit Student';
                    if (submitBtn) submitBtn.textContent = 'Save Changes';
                });
            }

            // Event delegation to handle all table button clicks (guarded)
            if (tableBody) {
                tableBody.addEventListener('click', (event) => {
                    const target = event.target;
                    if (target.classList && target.classList.contains('edit-row')) {
                        const row = target.closest('tr');
                        const data = row ? row.dataset : {};

                        const f = document.getElementById('firstName'); if (f) f.value = data.firstname || '';
                        const m = document.getElementById('middleName'); if (m) m.value = data.middlename || '';
                        const l = document.getElementById('lastName'); if (l) l.value = data.lastname || '';
                        const y = document.getElementById('year'); if (y) y.value = data.year || '';
                        const s = document.getElementById('section'); if (s) s.value = data.section || '';
                        if (modal) modal.style.display = 'block';
                        if (modalTitle) modalTitle.textContent = 'Edit Student';
                        if (submitBtn) submitBtn.textContent = 'Save Changes';
                    }

                    if (target.classList && target.classList.contains('delete-row')) {
                        // This will confirm before deleting the row.
                        if (confirm("Are you sure you want to delete this student's record?")) {
                            const row = target.closest('tr');
                            if (row) row.remove(); // Removes the entire table row from the DOM
                            console.log("Deleted row for:", row ? row.dataset.firstname : 'unknown');
                        }
                    }
                });
            }

            if (studentCloseBtn && studentModal) {
                studentCloseBtn.addEventListener('click', () => { studentModal.style.display = 'none'; });
                window.addEventListener('click', (event) => {
                    if (event.target == studentModal) studentModal.style.display = 'none';
                });
            }

            if (studentForm) {
                studentForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const formData = new FormData(studentForm);
                    const data = Object.fromEntries(formData.entries());
                    console.log('Form Submitted:', data);
                    if (modal) modal.style.display = 'none';
                });
            }

            // Section modal close handler (delegated)
            if (sectionModal) {
                // delegated handler (keeps working for dynamic buttons)
                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('.close-btn');
                    if (btn && sectionModal.contains(btn)) {
                        console.log('delegated close-btn click');
                        sectionModal.style.display = 'none';
                        sectionModal.setAttribute('aria-hidden', 'true');
                    }
                });
                // Also attach direct handler to the close button for reliability (defensive)
                const directClose = sectionModal.querySelector('.close-btn');
                if (directClose) {
                    // ensure the button is clickable even if CSS overlay layers changed
                    directClose.style.pointerEvents = 'auto';
                    directClose.style.zIndex = '10001';
                    directClose.addEventListener('click', (ev) => {
                        try {
                            ev.preventDefault(); ev.stopPropagation();
                            console.log('sectionModal direct close clicked');
                            sectionModal.style.display = 'none';
                            sectionModal.setAttribute('aria-hidden', 'true');
                        } catch (err) {
                            console.error('close click handler error', err);
                            // fallback: try to hide via document query
                            const sm = document.getElementById('sectionModal'); if (sm) sm.style.display = 'none';
                        }
                    });
                }
                sectionModal.addEventListener('click', (e)=>{ if (e.target === sectionModal) { sectionModal.style.display = 'none'; sectionModal.setAttribute('aria-hidden', 'true'); } });
                // close on Escape
                document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') { sectionModal.style.display = 'none'; sectionModal.setAttribute('aria-hidden', 'true'); } });
            }

            // Insights charts
            const trendLabels = <?php echo json_encode($trendLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            const trendRates = <?php echo json_encode($trendRates, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            const trendPresent = <?php echo json_encode($trendPresent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            const trendLate = <?php echo json_encode($trendLate, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            const trendAbsent = <?php echo json_encode($trendAbsent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            const statusCounts = {
                present: <?php echo (int)$presentToday; ?>,
                late: <?php echo (int)$lateToday; ?>,
                absent: <?php echo (int)$absentToday; ?>
            };

            const donutEl = document.getElementById('statusDonut');
            if (donutEl && window.Chart) {
                new Chart(donutEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Present', 'Late', 'Absent'],
                        datasets: [{
                            data: [statusCounts.present, statusCounts.late, statusCounts.absent],
                            backgroundColor: ['#2e7d32', '#f9a825', '#c62828'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12 } },
                            tooltip: { backgroundColor: '#2b2b2b' }
                        },
                        cutout: '64%'
                    }
                });
            }

            const trendEl = document.getElementById('trendLine');
            if (trendEl && window.Chart) {
                new Chart(trendEl, {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'Attendance rate (%)',
                            data: trendRates,
                            borderColor: '#b30000',
                            backgroundColor: 'rgba(179,0,0,0.12)',
                            pointRadius: 3,
                            tension: 0.35,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, max: 100, ticks: { callback: (v) => `${v}%` } }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: '#2b2b2b' }
                        }
                    }
                });
            }

            const trendBarsEl = document.getElementById('trendBars');
            if (trendBarsEl && window.Chart) {
                new Chart(trendBarsEl, {
                    type: 'bar',
                    data: {
                        labels: trendLabels,
                        datasets: [
                            {
                                label: 'Present',
                                data: trendPresent,
                                backgroundColor: '#2e7d32'
                            },
                            {
                                label: 'Late',
                                data: trendLate,
                                backgroundColor: '#f9a825'
                            },
                            {
                                label: 'Absent',
                                data: trendAbsent,
                                backgroundColor: '#c62828'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: { stacked: true },
                            y: { stacked: true, beginAtZero: true }
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12 } },
                            tooltip: { backgroundColor: '#2b2b2b' }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>