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

// Get available courses
$stmt = $pdo->prepare("SELECT * FROM courses ORDER BY course_name");
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's attendance
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT a.*, c.course_name FROM attendance a 
                       JOIN courses c ON a.course_code = c.course_code 
                       WHERE a.student_id = ? AND a.attendance_date = ? 
                       ORDER BY a.attendance_time DESC");
$stmt->execute([$_SESSION['student_id'], $today]);
$todayAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get attendance statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_attendance FROM attendance WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$totalAttendance = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as this_month FROM attendance WHERE student_id = ? AND DATE_FORMAT(attendance_date, '%Y-%m') = ?");
$stmt->execute([$_SESSION['student_id'], date('Y-m')]);
$thisMonthAttendance = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as this_week FROM attendance WHERE student_id = ? AND WEEK(attendance_date) = WEEK(NOW())");
$stmt->execute([$_SESSION['student_id']]);
$thisWeekAttendance = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> Student Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="attendance_history.php"><i class="fas fa-history"></i> Attendance History</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Location Status -->
    <div id="location-status" class="location-status badge bg-warning">
        <i class="fas fa-map-marker-alt"></i> Detecting location...
    </div>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Alert Container -->
        <div id="alert-container"></div>

        <!-- Welcome Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>Welcome back, <?php echo htmlspecialchars($student['first_name']); ?>!</h3>
                            <p class="mb-0">Student ID: <?php echo htmlspecialchars($student['student_id']); ?> | Course: <?php echo htmlspecialchars($student['course']); ?></p>
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
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h4><?php echo $totalAttendance; ?></h4>
                    <p>Total Attendance</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon text-success">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <h4><?php echo $thisWeekAttendance; ?></h4>
                    <p>This Week</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon text-info">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h4><?php echo $thisMonthAttendance; ?></h4>
                    <p>This Month</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4><?php echo count($todayAttendance); ?></h4>
                    <p>Today's Classes</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Attendance Form -->
            <div class="col-md-6">
                <div class="attendance-form">
                    <h4 class="mb-3">
                        <i class="fas fa-clipboard-check"></i> Mark Attendance
                    </h4>
                    <form id="attendance-form">
                        <div class="mb-3">
                            <label for="course-select" class="form-label">Select Course</label>
                            <select class="form-control" id="course-select" required>
                                <option value="">Choose a course...</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course['course_code']); ?>">
                                        <?php echo htmlspecialchars($course['course_name']); ?> (<?php echo htmlspecialchars($course['course_code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div id="location-display" class="mb-2">
                                <span class="badge bg-warning">
                                    <i class="fas fa-map-marker-alt"></i> Detecting location...
                                </span>
                            </div>
                            <small class="text-muted">You must be on campus to mark attendance</small>
                        </div>
                        
                        <button type="submit" id="attendance-submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-map-marker-alt"></i> Mark Attendance
                        </button>
                    </form>
                </div>
            </div>

            <!-- Today's Attendance -->
            <div class="col-md-6">
                <div class="attendance-table-container">
                    <h4 class="mb-3">
                        <i class="fas fa-calendar-day"></i> Today's Attendance
                    </h4>
                    
                    <?php if (empty($todayAttendance)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No attendance marked for today</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todayAttendance as $attendance): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($attendance['course_name']); ?></td>
                                            <td><?php echo date('h:i A', strtotime($attendance['attendance_time'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $attendance['location_verified'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $attendance['location_verified'] ? 'Verified' : 'Pending'; ?>
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

        <!-- Recent Attendance -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history"></i> Recent Attendance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="attendance-table-container">
                            <!-- Content will be loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script>
        // Initialize location display updates
        setInterval(function() {
            if (window.attendanceSystem && window.attendanceSystem.currentLocation()) {
                updateLocationDisplay();
            }
        }, 5000);

        // Load recent attendance on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentAttendance();
        });

        function loadRecentAttendance() {
            fetch('../includes/get_recent_attendance.php')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('attendance-table-container').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading recent attendance:', error);
                });
        }

        function updateLocationDisplay() {
            const locationDisplay = document.getElementById('location-display');
            const currentLocation = window.attendanceSystem.currentLocation();
            
            if (!currentLocation) {
                locationDisplay.innerHTML = `
                    <span class="badge bg-danger">
                        <i class="fas fa-exclamation-triangle"></i> Location Required
                    </span>
                `;
                return;
            }

            const isOnCampus = window.attendanceSystem.verifyCampusLocation();
            const statusClass = isOnCampus ? 'success' : 'danger';
            const statusText = isOnCampus ? 'On Campus' : 'Off Campus';
            
            locationDisplay.innerHTML = `
                <span class="badge bg-${statusClass}">
                    <i class="fas fa-map-marker-alt"></i> ${statusText}
                </span>
                <small class="text-muted d-block mt-1">
                    Lat: ${currentLocation.latitude.toFixed(6)}, 
                    Lon: ${currentLocation.longitude.toFixed(6)}
                </small>
            `;
        }
    </script>
</body>
</html>