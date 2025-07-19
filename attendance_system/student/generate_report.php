<?php
require_once '../includes/config.php';
require_once '../includes/pdf_generator.php';

// Check if student is logged in
if (!isStudent()) {
    redirect('../index.php');
}

// Get student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle report generation
if (isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $subject_code = $_POST['subject_code'] ?? 'all';
    
    // Generate the report
    generateAttendanceReport($student, $report_type, $start_date, $end_date, $subject_code);
    exit;
}

// Get student's enrolled subjects
$stmt = $pdo->prepare("SELECT ss.subject_code, s.subject_name, s.credits 
                       FROM student_subjects ss 
                       JOIN subjects s ON ss.subject_code = s.subject_code 
                       WHERE ss.student_id = ? AND ss.status = 'enrolled' 
                       ORDER BY s.subject_name");
$stmt->execute([$_SESSION['student_id']]);
$enrolled_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get attendance summary for current semester
$stmt = $pdo->prepare("SELECT 
                        COUNT(*) as total_attendance,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN location_verified = 1 THEN 1 ELSE 0 END) as location_verified_count
                       FROM attendance 
                       WHERE student_id = ? AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)");
$stmt->execute([$_SESSION['student_id']]);
$attendance_summary = $stmt->fetch(PDO::FETCH_ASSOC);

function generateAttendanceReport($student, $report_type, $start_date, $end_date, $subject_code) {
    global $pdo;
    
    // Create PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'ATTENDANCE REPORT', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Student Information
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Student Information:', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Name: ' . $student['first_name'] . ' ' . $student['last_name'], 0, 1);
    $pdf->Cell(0, 6, 'Student ID: ' . $student['student_id'], 0, 1);
    $pdf->Cell(0, 6, 'Email: ' . $student['email'], 0, 1);
    $pdf->Cell(0, 6, 'Course: ' . $student['course'], 0, 1);
    $pdf->Ln(5);
    
    // Report Details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Report Details:', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Report Type: ' . ucfirst($report_type), 0, 1);
    $pdf->Cell(0, 6, 'Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)), 0, 1);
    if ($subject_code !== 'all') {
        $stmt = $pdo->prepare("SELECT subject_name FROM subjects WHERE subject_code = ?");
        $stmt->execute([$subject_code]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdf->Cell(0, 6, 'Subject: ' . $subject['subject_name'], 0, 1);
    }
    $pdf->Ln(5);
    
    // Get attendance data
    $where_conditions = ["student_id = ?"];
    $params = [$student['student_id']];
    
    if ($subject_code !== 'all') {
        $where_conditions[] = "subject_code = ?";
        $params[] = $subject_code;
    }
    
    $where_conditions[] = "attendance_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $stmt = $pdo->prepare("SELECT a.*, s.subject_name, t.first_name as teacher_first_name, t.last_name as teacher_last_name
                           FROM attendance a 
                           JOIN subjects s ON a.subject_code = s.subject_code 
                           JOIN teachers t ON s.teacher_id = t.teacher_id 
                           WHERE $where_clause 
                           ORDER BY a.attendance_date DESC, a.attendance_time DESC");
    $stmt->execute($params);
    $attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Attendance Summary
    $total_attendance = count($attendance_data);
    $late_count = 0;
    $location_verified_count = 0;
    
    foreach ($attendance_data as $attendance) {
        if ($attendance['status'] === 'late') $late_count++;
        if ($attendance['location_verified']) $location_verified_count++;
    }
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Attendance Summary:', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Total Attendance: ' . $total_attendance, 0, 1);
    $pdf->Cell(0, 6, 'Late Attendance: ' . $late_count, 0, 1);
    $pdf->Cell(0, 6, 'Location Verified: ' . $location_verified_count, 0, 1);
    $pdf->Ln(5);
    
    // Detailed Attendance Table
    if (!empty($attendance_data)) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(25, 8, 'Date', 1);
        $pdf->Cell(40, 8, 'Subject', 1);
        $pdf->Cell(25, 8, 'Time', 1);
        $pdf->Cell(20, 8, 'Status', 1);
        $pdf->Cell(20, 8, 'Location', 1);
        $pdf->Cell(50, 8, 'Teacher', 1);
        $pdf->Ln();
        
        $pdf->SetFont('Arial', '', 8);
        foreach ($attendance_data as $attendance) {
            $pdf->Cell(25, 6, date('M d, Y', strtotime($attendance['attendance_date'])), 1);
            $pdf->Cell(40, 6, substr($attendance['subject_name'], 0, 20), 1);
            $pdf->Cell(25, 6, date('h:i A', strtotime($attendance['attendance_time'])), 1);
            $pdf->Cell(20, 6, ucfirst($attendance['status']), 1);
            $pdf->Cell(20, 6, $attendance['location_verified'] ? 'Yes' : 'No', 1);
            $pdf->Cell(50, 6, $attendance['teacher_first_name'] . ' ' . $attendance['teacher_last_name'], 1);
            $pdf->Ln();
        }
    } else {
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 10, 'No attendance records found for the selected period.', 0, 1, 'C');
    }
    
    // Footer
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 6, 'Report generated on: ' . date('M d, Y H:i:s'), 0, 1);
    $pdf->Cell(0, 6, 'This is an official attendance report from the Student Attendance Management System.', 0, 1);
    
    // Output PDF
    $filename = 'attendance_report_' . $student['student_id'] . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output('D', $filename);
}

// Get current date for default values
$current_date = date('Y-m-d');
$six_months_ago = date('Y-m-d', strtotime('-6 months'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .report-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-generate {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-file-pdf"></i> Generate Report
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics.php">
                            <i class="fas fa-chart-line"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Attendance Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="summary-card text-center">
                    <div class="h3 mb-1"><?php echo $attendance_summary['total_attendance']; ?></div>
                    <div class="small">Total Attendance</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card text-center">
                    <div class="h3 mb-1"><?php echo $attendance_summary['late_count']; ?></div>
                    <div class="small">Late Attendance</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card text-center">
                    <div class="h3 mb-1"><?php echo $attendance_summary['location_verified_count']; ?></div>
                    <div class="small">Location Verified</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="report-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cog"></i> Report Configuration
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="report_type" class="form-label">Report Type</label>
                                    <select class="form-select" id="report_type" name="report_type" required>
                                        <option value="detailed">Detailed Report</option>
                                        <option value="summary">Summary Report</option>
                                        <option value="monthly">Monthly Report</option>
                                        <option value="semester">Semester Report</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject_code" class="form-label">Subject (Optional)</label>
                                    <select class="form-select" id="subject_code" name="subject_code">
                                        <option value="all">All Subjects</option>
                                        <?php foreach ($enrolled_subjects as $subject): ?>
                                            <option value="<?php echo $subject['subject_code']; ?>">
                                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $six_months_ago; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $current_date; ?>" required>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="generate_report" class="btn btn-generate text-white">
                                    <i class="fas fa-download"></i> Generate & Download Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="report-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Report Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><i class="fas fa-file-alt text-primary"></i> Detailed Report</h6>
                            <small class="text-muted">Complete attendance records with dates, times, and teacher information.</small>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-chart-bar text-success"></i> Summary Report</h6>
                            <small class="text-muted">Overview with attendance percentages and statistics.</small>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-calendar-alt text-warning"></i> Monthly Report</h6>
                            <small class="text-muted">Attendance breakdown by month with trends.</small>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-graduation-cap text-info"></i> Semester Report</h6>
                            <small class="text-muted">Complete semester overview with subject-wise performance.</small>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Tip:</strong> Reports are generated in PDF format and can be used for official purposes.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Report Options -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="report-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt"></i> Quick Reports
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="report_type" value="semester">
                                    <input type="hidden" name="start_date" value="<?php echo date('Y-m-d', strtotime('-6 months')); ?>">
                                    <input type="hidden" name="end_date" value="<?php echo $current_date; ?>">
                                    <input type="hidden" name="subject_code" value="all">
                                    <button type="submit" name="generate_report" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-graduation-cap"></i><br>
                                        Current Semester
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-3 mb-3">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="report_type" value="monthly">
                                    <input type="hidden" name="start_date" value="<?php echo date('Y-m-01'); ?>">
                                    <input type="hidden" name="end_date" value="<?php echo $current_date; ?>">
                                    <input type="hidden" name="subject_code" value="all">
                                    <button type="submit" name="generate_report" class="btn btn-outline-success w-100">
                                        <i class="fas fa-calendar-alt"></i><br>
                                        This Month
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-3 mb-3">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="report_type" value="summary">
                                    <input type="hidden" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                    <input type="hidden" name="end_date" value="<?php echo $current_date; ?>">
                                    <input type="hidden" name="subject_code" value="all">
                                    <button type="submit" name="generate_report" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-chart-bar"></i><br>
                                        Last 30 Days
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-3 mb-3">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="report_type" value="detailed">
                                    <input type="hidden" name="start_date" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                                    <input type="hidden" name="end_date" value="<?php echo $current_date; ?>">
                                    <input type="hidden" name="subject_code" value="all">
                                    <button type="submit" name="generate_report" class="btn btn-outline-info w-100">
                                        <i class="fas fa-file-alt"></i><br>
                                        This Week
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="analytics.php" class="btn btn-primary me-3">
                    <i class="fas fa-chart-line"></i> View Analytics
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set max date to today
        document.getElementById('end_date').max = new Date().toISOString().split('T')[0];
        
        // Validate date range
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.getElementById('end_date');
            
            if (startDate > endDateInput.value) {
                endDateInput.value = startDate;
            }
        });
        
        document.getElementById('end_date').addEventListener('change', function() {
            const endDate = this.value;
            const startDateInput = document.getElementById('start_date');
            
            if (endDate < startDateInput.value) {
                startDateInput.value = endDate;
            }
        });
    </script>
</body>
</html>