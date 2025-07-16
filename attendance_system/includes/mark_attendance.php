<?php
require_once 'config.php';
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
    $course_code = sanitizeInput($_POST['course_code']);
    $latitude = (float)$_POST['latitude'];
    $longitude = (float)$_POST['longitude'];
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Validate inputs
    if (empty($course_code)) {
        echo json_encode(['success' => false, 'message' => 'Please select a course.']);
        exit;
    }
    
    if (empty($latitude) || empty($longitude)) {
        echo json_encode(['success' => false, 'message' => 'Location data is required.']);
        exit;
    }
    
    // Verify course exists
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = ?");
    $stmt->execute([$course_code]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Invalid course selected.']);
        exit;
    }
    
    // Check if attendance already marked for today
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND course_code = ? AND attendance_date = ?");
    $stmt->execute([$student_id, $course_code, $today]);
    $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_attendance) {
        echo json_encode(['success' => false, 'message' => 'Attendance already marked for this course today.']);
        exit;
    }
    
    // Verify location (check if student is on campus)
    $location_verified = verifyLocation($latitude, $longitude);
    
    if (!$location_verified) {
        echo json_encode(['success' => false, 'message' => 'You must be on campus to mark attendance.']);
        exit;
    }
    
    // Check if it's within reasonable time for the course
    $course_time = strtotime($course['schedule_time']);
    $current_timestamp = strtotime($current_time);
    $time_diff = abs($current_timestamp - $course_time);
    
    // Allow attendance 30 minutes before and 60 minutes after class time
    $before_class = 30 * 60; // 30 minutes
    $after_class = 60 * 60;  // 60 minutes
    
    $status = 'present';
    if ($time_diff > $before_class && $current_timestamp < $course_time) {
        // Too early
        echo json_encode(['success' => false, 'message' => 'Attendance can only be marked 30 minutes before class.']);
        exit;
    } elseif ($time_diff > $after_class && $current_timestamp > $course_time) {
        // Too late
        echo json_encode(['success' => false, 'message' => 'Attendance window has closed for this class.']);
        exit;
    } elseif ($current_timestamp > $course_time && $time_diff <= $after_class) {
        // Late but within window
        if ($time_diff > 15 * 60) { // 15 minutes late
            $status = 'late';
        }
    }
    
    // Mark attendance
    $stmt = $pdo->prepare("INSERT INTO attendance (student_id, course_code, attendance_date, attendance_time, latitude, longitude, location_verified, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$student_id, $course_code, $today, $current_time, $latitude, $longitude, $location_verified, $status]);
    
    if ($result) {
        $status_message = $status === 'late' ? 'Attendance marked as late.' : 'Attendance marked successfully.';
        echo json_encode([
            'success' => true, 
            'message' => $status_message,
            'status' => $status,
            'time' => date('h:i A', strtotime($current_time)),
            'course' => $course['course_name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance. Please try again.']);
    }
    
} catch (Exception $e) {
    error_log("Attendance marking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while marking attendance.']);
}
?>