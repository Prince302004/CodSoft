<?php
require_once '../includes/config.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../index.php');
}

// Get admin information
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Get statistics
$today = date('Y-m-d');

// Total students
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE status = 'active'");
$stmt->execute();
$total_students = $stmt->fetchColumn();

// Total courses
$stmt = $pdo->prepare("SELECT COUNT(*) FROM courses");
$stmt->execute();
$total_courses = $stmt->fetchColumn();

// Today's attendance
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = ?");
$stmt->execute([$today]);
$today_attendance = $stmt->fetchColumn();

// This month's attendance
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE DATE_FORMAT(attendance_date, '%Y-%m') = ?");
$stmt->execute([date('Y-m')]);
$month_attendance = $stmt->fetchColumn();

// Recent attendance with student and course info
$stmt = $pdo->prepare("
    SELECT a.*, s.first_name, s.last_name, c.course_name 
    FROM attendance a 
    JOIN students s ON a.student_id = s.student_id 
    JOIN courses c ON a.course_code = c.course_code 
    ORDER BY a.attendance_date DESC, a.attendance_time DESC 
    LIMIT 10
");
$stmt->execute();
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get attendance by course for today
$stmt = $pdo->prepare("
    SELECT c.course_name, c.course_code, COUNT(a.id) as attendance_count
    FROM courses c
    LEFT JOIN attendance a ON c.course_code = a.course_code AND a.attendance_date = ?
    GROUP BY c.course_code, c.course_name
    ORDER BY attendance_count DESC
");
$stmt->execute([$today]);
$course_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get students with low attendance (less than 5 attendances)
$stmt = $pdo->prepare("
    SELECT s.student_id, s.first_name, s.last_name, s.course, COUNT(a.id) as attendance_count
    FROM students s
    LEFT JOIN attendance a ON s.student_id = a.student_id
    WHERE s.status = 'active'
    GROUP BY s.student_id, s.first_name, s.last_name, s.course
    HAVING attendance_count < 5
    ORDER BY attendance_count ASC
");
$stmt->execute();
$low_attendance_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cog"></i> Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_subjects.php">
                            <i class="fas fa-book"></i> Subjects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_teachers.php">
                            <i class="fas fa-chalkboard-teacher"></i> Teachers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_courses.php">
                            <i class="fas fa-graduation-cap"></i> Courses
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($admin['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="students.php"><i class="fas fa-users"></i> Manage Students</a></li>
                            <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Welcome Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card bg-gradient-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>Admin Dashboard</h3>
                            <p class="mb-0">Welcome back, <?php echo htmlspecialchars($admin['username']); ?>!</p>
                        </div>
                        <div class="text-end">
                            <div class="h4 mb-0"><?php echo date('M d, Y'); ?></div>
                            <div class="h6"><?php echo date('l'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4><?php echo $total_students; ?></h4>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon text-success">
                        <i class="fas fa-book"></i>
                    </div>
                    <h4><?php echo $total_courses; ?></h4>
                    <p>Total Courses</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon text-info">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h4><?php echo $today_attendance; ?></h4>
                    <p>Today's Attendance</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon text-warning">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h4><?php echo $month_attendance; ?></h4>
                    <p>This Month</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Attendance -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock"></i> Recent Attendance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_attendance)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No attendance records found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_attendance as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['course_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($record['attendance_time'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $record['status'] === 'present' ? 'success' : ($record['status'] === 'late' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($record['status']); ?>
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

            <!-- Today's Course Attendance -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie"></i> Today's Course Attendance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($course_attendance)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No course data available</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($course_attendance as $course): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($course['course_code']); ?></small>
                                    </div>
                                    <span class="badge bg-primary"><?php echo $course['attendance_count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Attendance Students -->
        <?php if (!empty($low_attendance_students)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle"></i> Students with Low Attendance
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Course</th>
                                            <th>Attendance Count</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($low_attendance_students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['course']); ?></td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo $student['attendance_count']; ?></span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="sendReminder('<?php echo $student['student_id']; ?>')">
                                                        <i class="fas fa-bell"></i> Send Reminder
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to send reminder (placeholder)
        function sendReminder(studentId) {
            alert('Reminder functionality would be implemented here for student: ' + studentId);
        }

        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>