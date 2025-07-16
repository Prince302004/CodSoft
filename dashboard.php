<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'attendance.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get current user information
$user_info = get_user_info();
$user_profile = $authManager->getUserProfile($_SESSION['user_id']);

// Get teacher ID if user is a teacher
$teacher_id = null;
if ($user_info['role'] === 'teacher') {
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher_result = $stmt->fetch();
    if ($teacher_result) {
        $teacher_id = $teacher_result['id'];
    }
}

// Get student ID if user is a student
$student_id = null;
if ($user_info['role'] === 'student') {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_result = $stmt->fetch();
    if ($student_result) {
        $student_id = $student_result['id'];
    }
}

// Get teacher classes if user is a teacher
$teacher_classes = [];
if ($teacher_id) {
    $teacher_classes = $attendanceManager->getTeacherClasses($teacher_id);
}

// Get student attendance if user is a student
$student_attendance = [];
if ($student_id) {
    $student_attendance = $attendanceManager->getStudentAttendance($student_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-user-graduate"></i> <?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if ($user_info['role'] === 'teacher'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showAttendanceSection()">
                                <i class="fas fa-clipboard-check"></i> Take Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showReportsSection()">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($user_info['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showUsersSection()">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showClassesSection()">
                                <i class="fas fa-chalkboard-teacher"></i> Classes
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $user_profile['first_name'] ?? $user_info['username']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="showProfileSection()">
                                <i class="fas fa-user-cog"></i> Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item logout-btn" href="#">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1>Welcome, <?php echo $user_profile['first_name'] ?? $user_info['username']; ?>!</h1>
            <p>Role: <?php echo ucfirst($user_info['role']); ?> | Last Login: <?php echo date('M j, Y g:i A', strtotime($user_info['updated_at'])); ?></p>
        </div>
        
        <!-- Location Information -->
        <div class="dashboard-card">
            <h5><i class="fas fa-map-marker-alt"></i> Location Status</h5>
            <div class="location-info">
                <div class="location-status warning">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Getting your location...</span>
                </div>
            </div>
            <button class="btn btn-outline-primary btn-sm refresh-location-btn">
                <i class="fas fa-sync"></i> Refresh Location
            </button>
        </div>
        
        <!-- Dashboard Content Based on Role -->
        <?php if ($user_info['role'] === 'teacher'): ?>
            <!-- Teacher Dashboard -->
            <div id="teacherDashboard">
                <!-- Quick Stats -->
                <div class="dashboard-card">
                    <h5><i class="fas fa-chart-line"></i> Quick Stats</h5>
                    <div id="attendanceStatsContainer">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-card present">
                                    <h3>0</h3>
                                    <p>Present Today</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card absent">
                                    <h3>0</h3>
                                    <p>Absent Today</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card late">
                                    <h3>0</h3>
                                    <p>Late Today</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card total">
                                    <h3><?php echo count($teacher_classes); ?></h3>
                                    <p>Your Classes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Management -->
                <div class="dashboard-card">
                    <h5><i class="fas fa-clipboard-check"></i> Take Attendance</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="classSelect">Select Class</label>
                            <select class="form-select" id="classSelect">
                                <option value="">Choose a class...</option>
                                <?php foreach ($teacher_classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo $class['class_name']; ?> - <?php echo $class['section']; ?> (<?php echo $class['subject']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="attendanceDate">Date</label>
                            <input type="date" class="form-control" id="attendanceDate" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div id="studentsContainer">
                        <p class="text-center text-muted">Please select a class to view students.</p>
                    </div>
                </div>
                
                <!-- Attendance Records -->
                <div class="dashboard-card">
                    <h5><i class="fas fa-list"></i> Today's Attendance Records</h5>
                    <div id="attendanceRecordsContainer">
                        <p class="text-center text-muted">Please select a class to view attendance records.</p>
                    </div>
                </div>
            </div>
            
        <?php elseif ($user_info['role'] === 'student'): ?>
            <!-- Student Dashboard -->
            <div id="studentDashboard">
                <!-- Student Stats -->
                <div class="dashboard-card">
                    <h5><i class="fas fa-chart-pie"></i> Your Attendance Summary</h5>
                    <div class="row">
                        <?php
                        $present_count = 0;
                        $absent_count = 0;
                        $late_count = 0;
                        $total_count = count($student_attendance);
                        
                        foreach ($student_attendance as $record) {
                            switch ($record['status']) {
                                case 'present':
                                    $present_count++;
                                    break;
                                case 'absent':
                                    $absent_count++;
                                    break;
                                case 'late':
                                    $late_count++;
                                    break;
                            }
                        }
                        
                        $attendance_percentage = $total_count > 0 ? round(($present_count / $total_count) * 100, 1) : 0;
                        ?>
                        <div class="col-md-3">
                            <div class="stat-card present">
                                <h3><?php echo $present_count; ?></h3>
                                <p>Present</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card absent">
                                <h3><?php echo $absent_count; ?></h3>
                                <p>Absent</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card late">
                                <h3><?php echo $late_count; ?></h3>
                                <p>Late</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card total">
                                <h3><?php echo $attendance_percentage; ?>%</h3>
                                <p>Attendance Rate</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Attendance -->
                <div class="dashboard-card">
                    <h5><i class="fas fa-history"></i> Recent Attendance</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                    <th>Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($student_attendance, 0, 10) as $record): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                        <td><?php echo $record['class_name']; ?> - <?php echo $record['section']; ?></td>
                                        <td><?php echo $record['subject']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $record['status'] === 'present' ? 'success' : ($record['status'] === 'absent' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $record['time_in'] ?? 'N/A'; ?></td>
                                        <td><?php echo $record['teacher_first_name']; ?> <?php echo $record['teacher_last_name']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        <?php elseif ($user_info['role'] === 'admin'): ?>
            <!-- Admin Dashboard -->
            <div id="adminDashboard">
                <!-- Admin Stats -->
                <div class="dashboard-card">
                    <h5><i class="fas fa-chart-bar"></i> System Overview</h5>
                    <?php
                    // Get system stats
                    $stats = [
                        'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                        'total_teachers' => $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
                        'total_students' => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
                        'total_classes' => $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn(),
                        'today_attendance' => $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE()")->fetchColumn()
                    ];
                    ?>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="stat-card total">
                                <h3><?php echo $stats['total_users']; ?></h3>
                                <p>Total Users</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card info">
                                <h3><?php echo $stats['total_teachers']; ?></h3>
                                <p>Teachers</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card present">
                                <h3><?php echo $stats['total_students']; ?></h3>
                                <p>Students</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card warning">
                                <h3><?php echo $stats['total_classes']; ?></h3>
                                <p>Classes</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card absent">
                                <h3><?php echo $stats['today_attendance']; ?></h3>
                                <p>Today's Records</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="dashboard-card">
                    <h5><i class="fas fa-activity"></i> Recent Activities</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_activities = $pdo->query("
                                    SELECT a.*, s.first_name as student_fname, s.last_name as student_lname,
                                           c.class_name, c.section, t.first_name as teacher_fname, t.last_name as teacher_lname
                                    FROM attendance a
                                    JOIN students s ON a.student_id = s.id
                                    JOIN classes c ON a.class_id = c.id
                                    JOIN teachers t ON a.teacher_id = t.id
                                    ORDER BY a.created_at DESC
                                    LIMIT 10
                                ")->fetchAll();
                                
                                foreach ($recent_activities as $activity):
                                ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($activity['date'])); ?></td>
                                        <td><?php echo $activity['student_fname']; ?> <?php echo $activity['student_lname']; ?></td>
                                        <td><?php echo $activity['class_name']; ?> - <?php echo $activity['section']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $activity['status'] === 'present' ? 'success' : ($activity['status'] === 'absent' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($activity['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $activity['teacher_fname']; ?> <?php echo $activity['teacher_lname']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- OTP Modal -->
    <div class="modal fade otp-modal" id="otpModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt"></i> OTP Verification
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p>Enter the OTP code sent to your phone/email:</p>
                    <input type="text" class="form-control otp-input" id="otpCode" maxlength="6" placeholder="Enter 6-digit OTP">
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary verify-otp-btn" data-user-id="<?php echo $_SESSION['user_id']; ?>" data-purpose="attendance">
                            <i class="fas fa-check"></i> Verify OTP
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/app.js"></script>
    
    <script>
        // Set current user for JavaScript
        window.currentUser = {
            id: <?php echo $_SESSION['user_id']; ?>,
            username: '<?php echo $user_info['username']; ?>',
            role: '<?php echo $user_info['role']; ?>'
        };
        
        // Initialize teacher ID if user is a teacher
        <?php if ($teacher_id): ?>
        window.currentUser.teacher_id = <?php echo $teacher_id; ?>;
        <?php endif; ?>
        
        // Initialize student ID if user is a student
        <?php if ($student_id): ?>
        window.currentUser.student_id = <?php echo $student_id; ?>;
        <?php endif; ?>
        
        // Dashboard section functions
        function showAttendanceSection() {
            // Already on dashboard, just scroll to attendance section
            document.getElementById('classSelect').scrollIntoView({ behavior: 'smooth' });
        }
        
        function showReportsSection() {
            // Implement reports section
            alert('Reports section - To be implemented');
        }
        
        function showUsersSection() {
            // Implement users management section
            alert('Users management section - To be implemented');
        }
        
        function showClassesSection() {
            // Implement classes management section
            alert('Classes management section - To be implemented');
        }
        
        function showProfileSection() {
            // Implement profile section
            alert('Profile section - To be implemented');
        }
        
        // Auto-focus OTP input when modal opens
        $('#otpModal').on('shown.bs.modal', function () {
            $('#otpCode').focus();
        });
        
        // Clear OTP input when modal closes
        $('#otpModal').on('hidden.bs.modal', function () {
            $('#otpCode').val('');
        });
    </script>
</body>
</html>