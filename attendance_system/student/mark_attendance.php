<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

// Check if student is logged in
if (!isStudent()) {
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['mark_attendance'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

try {
    $student_id = $_SESSION['student_id'];
    $subject_code = sanitizeInput($_POST['subject_code']);
    $latitude = (float)$_POST['latitude'];
    $longitude = (float)$_POST['longitude'];
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    $current_day = date('l'); // Monday, Tuesday, etc.
    
    // Validate inputs
    if (empty($subject_code)) {
        echo json_encode(['success' => false, 'message' => 'Please select a subject.']);
        exit;
    }
    
    if (empty($latitude) || empty($longitude)) {
        echo json_encode(['success' => false, 'message' => 'Location data is required. Please enable location services.']);
        exit;
    }
    
    // Check if student is enrolled in this subject
    $stmt = $pdo->prepare("SELECT ss.*, s.subject_name, s.teacher_id, t.first_name as teacher_first_name, t.last_name as teacher_last_name 
                          FROM student_subjects ss 
                          JOIN subjects s ON ss.subject_code = s.subject_code 
                          JOIN teachers t ON s.teacher_id = t.teacher_id 
                          WHERE ss.student_id = ? AND ss.subject_code = ? AND ss.status = 'enrolled'");
    $stmt->execute([$student_id, $subject_code]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this subject.']);
        exit;
    }
    
    // Check if attendance already marked for today
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND subject_code = ? AND attendance_date = ?");
    $stmt->execute([$student_id, $subject_code, $today]);
    $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_attendance) {
        echo json_encode(['success' => false, 'message' => 'Attendance already marked for this subject today.']);
        exit;
    }
    
    // Verify location (check if student is on campus)
    $location_verified = verifyLocation($latitude, $longitude);
    
    if (!$location_verified) {
        echo json_encode([
            'success' => false, 
            'message' => 'You must be on campus to mark attendance. Please ensure you are within the campus boundaries.',
            'location_data' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'campus_lat' => CAMPUS_LATITUDE,
                'campus_lon' => CAMPUS_LONGITUDE,
                'distance' => calculateDistance(CAMPUS_LATITUDE, CAMPUS_LONGITUDE, $latitude, $longitude)
            ]
        ]);
        exit;
    }
    
    // Check if it's a valid time for attendance (between 8 AM and 6 PM)
    $current_hour = (int)date('H');
    if ($current_hour < 8 || $current_hour > 18) {
        echo json_encode(['success' => false, 'message' => 'Attendance can only be marked between 8:00 AM and 6:00 PM.']);
        exit;
    }
    
    // Determine attendance status based on time
    $status = 'present';
    $current_timestamp = strtotime($current_time);
    
    // If it's after 9:30 AM, mark as late
    if ($current_hour > 9 || ($current_hour == 9 && (int)date('i') > 30)) {
        $status = 'late';
    }
    
    // Mark attendance
    $stmt = $pdo->prepare("INSERT INTO attendance (student_id, subject_code, attendance_date, attendance_time, latitude, longitude, location_verified, status, marked_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student')");
    $result = $stmt->execute([$student_id, $subject_code, $today, $current_time, $latitude, $longitude, $location_verified, $status]);
    
    if ($result) {
        $status_message = $status === 'late' ? 'Attendance marked as late.' : 'Attendance marked successfully.';
        echo json_encode([
            'success' => true, 
            'message' => $status_message,
            'status' => $status,
            'time' => date('h:i A', strtotime($current_time)),
            'subject' => $enrollment['subject_name'],
            'teacher' => $enrollment['teacher_first_name'] . ' ' . $enrollment['teacher_last_name'],
            'location_verified' => $location_verified,
            'location_data' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance_from_campus' => round(calculateDistance(CAMPUS_LATITUDE, CAMPUS_LONGITUDE, $latitude, $longitude), 2)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance. Please try again.']);
    }
    
} catch (Exception $e) {
    error_log("Attendance marking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while marking attendance.']);
}
?>