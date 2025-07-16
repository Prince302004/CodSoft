<?php
require_once '../includes/config.php';

// Check if teacher is logged in
if (!isTeacher()) {
    redirect('../index.php');
}

$teacher_id = $_SESSION['teacher_id'];

// Get teacher information
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

// Get teacher's subjects
$stmt = $pdo->prepare("SELECT s.*, ay.year_name, sem.semester_name 
                      FROM subjects s 
                      JOIN academic_years ay ON s.academic_year_id = ay.id 
                      JOIN semesters sem ON s.semester_id = sem.id 
                      WHERE s.teacher_id = ? 
                      ORDER BY ay.year_number, sem.semester_number");
$stmt->execute([$teacher_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent attendance for teacher's subjects
$stmt = $pdo->prepare("SELECT a.*, s.first_name, s.last_name, s.student_id, sub.subject_name 
                      FROM attendance a 
                      JOIN students s ON a.student_id = s.student_id 
                      JOIN subjects sub ON a.subject_code = sub.subject_code 
                      WHERE sub.teacher_id = ? 
                      ORDER BY a.attendance_date DESC, a.attendance_time DESC 
                      LIMIT 10");
$stmt->execute([$teacher_id]);
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [];
foreach ($subjects as $subject) {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) as enrolled_students FROM student_subjects WHERE subject_code = ?");
    $stmt->execute([$subject['subject_code']]);
    $enrolled = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_attendance FROM attendance WHERE subject_code = ? AND attendance_date = CURDATE()");
    $stmt->execute([$subject['subject_code']]);
    $today_attendance = $stmt->fetchColumn();
    
    $stats[$subject['subject_code']] = [
        'enrolled' => $enrolled,
        'today_attendance' => $today_attendance,
        'attendance_percentage' => $enrolled > 0 ? round(($today_attendance / $enrolled) * 100, 2) : 0
    ];
}

// Handle manual attendance marking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_attendance') {
    $student_id = sanitizeInput($_POST['student_id']);
    $subject_code = sanitizeInput($_POST['subject_code']);
    $attendance_status = sanitizeInput($_POST['attendance_status']);
    $notes = sanitizeInput($_POST['notes']);
    
    try {
        // Check if attendance already exists for today
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND subject_code = ? AND attendance_date = CURDATE()");
        $stmt->execute([$student_id, $subject_code]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing attendance
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, marked_by = 'teacher', notes = ? WHERE student_id = ? AND subject_code = ? AND attendance_date = CURDATE()");
            $stmt->execute([$attendance_status, $notes, $student_id, $subject_code]);
        } else {
            // Insert new attendance record
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, subject_code, attendance_date, attendance_time, latitude, longitude, location_verified, status, marked_by, notes) VALUES (?, ?, CURDATE(), NOW(), 0, 0, FALSE, ?, 'teacher', ?)");
            $stmt->execute([$student_id, $subject_code, $attendance_status, $notes]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle getting students for a subject
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_students') {
    $subject_code = sanitizeInput($_GET['subject_code']);
    
    try {
        $stmt = $pdo->prepare("SELECT s.student_id, s.first_name, s.last_name, s.roll_number,
                              a.status as attendance_status, a.attendance_time, a.notes
                              FROM students s 
                              JOIN student_subjects ss ON s.student_id = ss.student_id 
                              LEFT JOIN attendance a ON s.student_id = a.student_id AND a.subject_code = ? AND a.attendance_date = CURDATE()
                              WHERE ss.subject_code = ? AND s.status = 'active'
                              ORDER BY s.roll_number");
        $stmt->execute([$subject_code, $subject_code]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'students' => $students]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - College Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> College Attendance System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="fas fa-user-circle"></i> Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="fas fa-chalkboard-teacher"></i> Welcome, <?php echo htmlspecialchars($teacher['first_name']); ?>!
                </h2>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Subjects</h5>
                                <h3><?php echo count($subjects); ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-book fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Today's Classes</h5>
                                <h3><?php echo count($subjects); ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calendar-day fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Department</h5>
                                <h6><?php echo htmlspecialchars($teacher['department']); ?></h6>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-building fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Qualification</h5>
                                <h6><?php echo htmlspecialchars($teacher['qualification']); ?></h6>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-graduation-cap fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Subjects List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-book"></i> My Subjects</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($subjects)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No subjects assigned yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Subject Code</th>
                                            <th>Subject Name</th>
                                            <th>Year/Semester</th>
                                            <th>Enrolled Students</th>
                                            <th>Today's Attendance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subjects as $subject): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['year_name'] . ' - ' . $subject['semester_name']); ?></td>
                                                <td><?php echo $stats[$subject['subject_code']]['enrolled']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $stats[$subject['subject_code']]['attendance_percentage'] > 75 ? 'success' : 'warning'; ?>">
                                                        <?php echo $stats[$subject['subject_code']]['today_attendance']; ?>/<?php echo $stats[$subject['subject_code']]['enrolled']; ?>
                                                        (<?php echo $stats[$subject['subject_code']]['attendance_percentage']; ?>%)
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="manageAttendance('<?php echo $subject['subject_code']; ?>', '<?php echo htmlspecialchars($subject['subject_name']); ?>')">
                                                        <i class="fas fa-users"></i> Manage Attendance
                                                    </button>
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
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock"></i> Recent Attendance</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_attendance)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No recent attendance records.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recent_attendance as $attendance): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></h6>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($attendance['attendance_date'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($attendance['subject_name']); ?></p>
                                        <small class="text-muted">
                                            Status: <span class="badge bg-<?php echo $attendance['status'] == 'present' ? 'success' : ($attendance['status'] == 'late' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($attendance['status']); ?>
                                            </span>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Management Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Attendance - <span id="modalSubjectName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="attendanceContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Teacher Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-4"><strong>Teacher ID:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['teacher_id']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Name:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Email:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['email']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Phone:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['phone']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Department:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['department']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Qualification:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['qualification']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentSubjectCode = '';

        function manageAttendance(subjectCode, subjectName) {
            currentSubjectCode = subjectCode;
            document.getElementById('modalSubjectName').textContent = subjectName;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
            modal.show();
            
            // Load students
            fetch(`?action=get_students&subject_code=${subjectCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayStudents(data.students);
                    } else {
                        document.getElementById('attendanceContent').innerHTML = 
                            '<div class="alert alert-danger">Error loading students: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('attendanceContent').innerHTML = 
                        '<div class="alert alert-danger">Error loading students: ' + error.message + '</div>';
                });
        }

        function displayStudents(students) {
            let html = '<div class="table-responsive"><table class="table table-striped">';
            html += '<thead><tr><th>Roll Number</th><th>Student Name</th><th>Status</th><th>Time</th><th>Actions</th></tr></thead><tbody>';
            
            students.forEach(student => {
                const statusBadge = student.attendance_status ? 
                    `<span class="badge bg-${student.attendance_status === 'present' ? 'success' : (student.attendance_status === 'late' ? 'warning' : 'danger')}">${student.attendance_status}</span>` : 
                    '<span class="badge bg-secondary">Not marked</span>';
                
                const time = student.attendance_time ? new Date(student.attendance_time).toLocaleTimeString() : '-';
                
                html += `<tr>
                    <td>${student.roll_number}</td>
                    <td>${student.first_name} ${student.last_name}</td>
                    <td>${statusBadge}</td>
                    <td>${time}</td>
                    <td>
                        <select class="form-select form-select-sm" onchange="markAttendance('${student.student_id}', this.value)">
                            <option value="">Select Status</option>
                            <option value="present" ${student.attendance_status === 'present' ? 'selected' : ''}>Present</option>
                            <option value="absent" ${student.attendance_status === 'absent' ? 'selected' : ''}>Absent</option>
                            <option value="late" ${student.attendance_status === 'late' ? 'selected' : ''}>Late</option>
                        </select>
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            document.getElementById('attendanceContent').innerHTML = html;
        }

        function markAttendance(studentId, status) {
            if (!status) return;
            
            const formData = new FormData();
            formData.append('action', 'mark_attendance');
            formData.append('student_id', studentId);
            formData.append('subject_code', currentSubjectCode);
            formData.append('attendance_status', status);
            formData.append('notes', 'Marked by teacher');
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the students list
                    manageAttendance(currentSubjectCode, document.getElementById('modalSubjectName').textContent);
                    // Show success message
                    showAlert('success', 'Attendance marked successfully');
                } else {
                    showAlert('danger', 'Error: ' + data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Error: ' + error.message);
            });
        }

        function showAlert(type, message) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.querySelector('.container').insertBefore(alert, document.querySelector('.container').firstChild);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    </script>
</body>
</html>