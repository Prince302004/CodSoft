<?php
require_once '../includes/config.php';

// Check if student is logged in
if (!isStudent()) {
    redirect('../index.php');
}

// Get student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get student's enrolled subjects with attendance data
$stmt = $pdo->prepare("SELECT ss.subject_code, s.subject_name, s.credits, t.first_name as teacher_first_name, t.last_name as teacher_last_name 
                       FROM student_subjects ss 
                       JOIN subjects s ON ss.subject_code = s.subject_code 
                       JOIN teachers t ON s.teacher_id = t.teacher_id 
                       WHERE ss.student_id = ? AND ss.status = 'enrolled' 
                       ORDER BY s.subject_name");
$stmt->execute([$_SESSION['student_id']]);
$enrolled_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate attendance statistics for each subject
$subject_stats = [];
$total_classes = 0;
$total_attended = 0;

foreach ($enrolled_subjects as $subject) {
    // Get total classes (assuming 5 days per week for 16 weeks = 80 classes per semester)
    $total_classes_per_subject = 80;
    
    // Get attended classes
    $stmt = $pdo->prepare("SELECT COUNT(*) as attended, 
                          SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
                          FROM attendance 
                          WHERE student_id = ? AND subject_code = ?");
    $stmt->execute([$_SESSION['student_id'], $subject['subject_code']]);
    $attendance_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $attended = $attendance_data['attended'];
    $late_count = $attendance_data['late_count'];
    $percentage = $total_classes_per_subject > 0 ? round(($attended / $total_classes_per_subject) * 100, 2) : 0;
    
    $subject_stats[$subject['subject_code']] = [
        'subject_name' => $subject['subject_name'],
        'teacher_name' => $subject['teacher_first_name'] . ' ' . $subject['teacher_last_name'],
        'credits' => $subject['credits'],
        'total_classes' => $total_classes_per_subject,
        'attended' => $attended,
        'late_count' => $late_count,
        'absent' => $total_classes_per_subject - $attended,
        'percentage' => $percentage,
        'status' => $percentage >= 75 ? 'Good' : ($percentage >= 60 ? 'Average' : 'Poor')
    ];
    
    $total_classes += $total_classes_per_subject;
    $total_attended += $attended;
}

// Overall statistics
$overall_percentage = $total_classes > 0 ? round(($total_attended / $total_classes) * 100, 2) : 0;

// Get monthly attendance data for chart
$stmt = $pdo->prepare("SELECT DATE_FORMAT(attendance_date, '%Y-%m') as month, COUNT(*) as attendance_count
                       FROM attendance 
                       WHERE student_id = ? 
                       GROUP BY DATE_FORMAT(attendance_date, '%Y-%m')
                       ORDER BY month DESC 
                       LIMIT 6");
$stmt->execute([$_SESSION['student_id']]);
$monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get weekly attendance data
$stmt = $pdo->prepare("SELECT WEEK(attendance_date) as week, COUNT(*) as attendance_count
                       FROM attendance 
                       WHERE student_id = ? AND YEAR(attendance_date) = YEAR(CURDATE())
                       GROUP BY WEEK(attendance_date)
                       ORDER BY week DESC 
                       LIMIT 8");
$stmt->execute([$_SESSION['student_id']]);
$weekly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent attendance history
$stmt = $pdo->prepare("SELECT a.*, s.subject_name 
                       FROM attendance a 
                       JOIN subjects s ON a.subject_code = s.subject_code 
                       WHERE a.student_id = ? 
                       ORDER BY a.attendance_date DESC, a.attendance_time DESC 
                       LIMIT 20");
$stmt->execute([$_SESSION['student_id']]);
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Analytics - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .analytics-card:hover {
            transform: translateY(-5px);
        }
        .percentage-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: white;
            margin: 0 auto;
        }
        .status-good { background: linear-gradient(45deg, #28a745, #20c997); }
        .status-average { background: linear-gradient(45deg, #ffc107, #fd7e14); }
        .status-poor { background: linear-gradient(45deg, #dc3545, #e83e8c); }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .progress-custom {
            height: 25px;
            border-radius: 15px;
            background: #f8f9fa;
        }
        .progress-custom .progress-bar {
            border-radius: 15px;
            font-weight: 600;
        }
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line"></i> Performance Analytics
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
                        <a class="nav-link" href="mobile_attendance.php">
                            <i class="fas fa-mobile-alt"></i> Mobile
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

    <div class="container-fluid mt-4">
        <!-- Overall Performance Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="metric-card text-center">
                    <div class="metric-value"><?php echo $overall_percentage; ?>%</div>
                    <div class="metric-label">Overall Attendance</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card text-center">
                    <div class="metric-value"><?php echo $total_attended; ?></div>
                    <div class="metric-label">Classes Attended</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card text-center">
                    <div class="metric-value"><?php echo $total_classes; ?></div>
                    <div class="metric-label">Total Classes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card text-center">
                    <div class="metric-value"><?php echo count($enrolled_subjects); ?></div>
                    <div class="metric-label">Enrolled Subjects</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Subject-wise Performance -->
            <div class="col-md-8">
                <div class="analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-book"></i> Subject-wise Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($subject_stats)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No subjects enrolled yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                            <th>Attendance</th>
                                            <th>Percentage</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subject_stats as $subject_code => $stats): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($stats['subject_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo $stats['credits']; ?> credits</small>
                                                </td>
                                                <td><?php echo htmlspecialchars($stats['teacher_name']); ?></td>
                                                <td>
                                                    <?php echo $stats['attended']; ?>/<?php echo $stats['total_classes']; ?>
                                                    <?php if ($stats['late_count'] > 0): ?>
                                                        <br><small class="text-warning"><?php echo $stats['late_count']; ?> late</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="progress progress-custom">
                                                        <div class="progress-bar bg-<?php echo $stats['percentage'] >= 75 ? 'success' : ($stats['percentage'] >= 60 ? 'warning' : 'danger'); ?>" 
                                                             style="width: <?php echo $stats['percentage']; ?>%">
                                                            <?php echo $stats['percentage']; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $stats['status'] === 'Good' ? 'success' : ($stats['status'] === 'Average' ? 'warning' : 'danger'); ?>">
                                                        <?php echo $stats['status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Overall Performance Circle -->
            <div class="col-md-4">
                <div class="analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie"></i> Overall Performance
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="percentage-circle status-<?php echo $overall_percentage >= 75 ? 'good' : ($overall_percentage >= 60 ? 'average' : 'poor'); ?>">
                            <?php echo $overall_percentage; ?>%
                        </div>
                        <h6 class="mt-3">Overall Attendance Rate</h6>
                        <p class="text-muted">
                            <?php if ($overall_percentage >= 75): ?>
                                <i class="fas fa-thumbs-up text-success"></i> Excellent attendance!
                            <?php elseif ($overall_percentage >= 60): ?>
                                <i class="fas fa-exclamation-triangle text-warning"></i> Good, but can improve.
                            <?php else: ?>
                                <i class="fas fa-exclamation-circle text-danger"></i> Needs improvement.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Monthly Attendance Chart -->
            <div class="col-md-6">
                <div class="analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt"></i> Monthly Attendance Trend
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Attendance Chart -->
            <div class="col-md-6">
                <div class="analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-week"></i> Weekly Attendance Pattern
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="weeklyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Attendance History -->
        <div class="row">
            <div class="col-12">
                <div class="analytics-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history"></i> Recent Attendance History
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_attendance)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No attendance records found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Subject</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Location</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_attendance as $attendance): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($attendance['attendance_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($attendance['subject_name']); ?></td>
                                                <td><?php echo date('h:i A', strtotime($attendance['attendance_time'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $attendance['status'] === 'late' ? 'warning' : 'success'; ?>">
                                                        <?php echo ucfirst($attendance['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($attendance['location_verified']): ?>
                                                        <i class="fas fa-map-marker-alt text-success" title="Location Verified"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-question-circle text-muted" title="Location Not Verified"></i>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="mobile_attendance.php" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-mobile-alt"></i> Mobile Attendance
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Attendance Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($monthly_data), 'month')); ?>,
                datasets: [{
                    label: 'Attendance Count',
                    data: <?php echo json_encode(array_column(array_reverse($monthly_data), 'attendance_count')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Weekly Attendance Chart
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyChart = new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($weekly_data), 'week')); ?>,
                datasets: [{
                    label: 'Attendance Count',
                    data: <?php echo json_encode(array_column(array_reverse($weekly_data), 'attendance_count')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>