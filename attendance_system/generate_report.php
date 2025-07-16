<?php
require_once 'includes/config.php';
require_once 'includes/pdf_generator.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$report_type = sanitizeInput($_GET['type'] ?? 'student');

try {
    $pdf_generator = new AttendancePDFGenerator($pdo);
    
    if ($report_type === 'student') {
        // Student report
        if (!isStudent()) {
            throw new Exception("Access denied: Student access required");
        }
        
        $student_id = $_SESSION['student_id'];
        $start_date = sanitizeInput($_GET['start_date'] ?? null);
        $end_date = sanitizeInput($_GET['end_date'] ?? null);
        
        $pdf_generator->generateStudentReport($student_id, $start_date, $end_date);
        
    } elseif ($report_type === 'admin') {
        // Admin report
        if (!isAdmin()) {
            throw new Exception("Access denied: Admin access required");
        }
        
        $subject_code = sanitizeInput($_GET['subject_code'] ?? null);
        $start_date = sanitizeInput($_GET['start_date'] ?? null);
        $end_date = sanitizeInput($_GET['end_date'] ?? null);
        
        $pdf_generator->generateAdminReport($subject_code, $start_date, $end_date);
        
    } else {
        throw new Exception("Invalid report type");
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// If there's an error, redirect back with error message
if ($error) {
    $redirect_url = isAdmin() ? 'admin/dashboard.php' : 'student/dashboard.php';
    redirect($redirect_url . '?error=' . urlencode($error));
}
?>