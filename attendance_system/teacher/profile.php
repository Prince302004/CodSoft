<?php
require_once '../includes/config.php';

// Check if teacher is logged in
if (!isTeacher()) {
    redirect('../index.php');
}

$teacher_id = $_SESSION['teacher_id'];
$error = '';
$success = '';

// Get teacher information
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

// Get available subjects
$stmt = $pdo->prepare("SELECT s.*, ay.year_name, sem.semester_name 
                      FROM subjects s 
                      JOIN academic_years ay ON s.academic_year_id = ay.id 
                      JOIN semesters sem ON s.semester_id = sem.id 
                      ORDER BY ay.year_number ASC, sem.semester_number ASC, s.subject_name ASC");
$stmt->execute();
$all_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get teacher's current subjects
$stmt = $pdo->prepare("SELECT subject_code FROM subjects WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$current_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get departments for dropdown
$departments = ['Computer Science', 'Information Technology', 'Mathematics', 'English', 'Physics', 'Chemistry', 'Electronics', 'Mechanical Engineering', 'Civil Engineering', 'Business Administration'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            $first_name = sanitizeInput($_POST['first_name']);
            $last_name = sanitizeInput($_POST['last_name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            $department = sanitizeInput($_POST['department']);
            $qualification = sanitizeInput($_POST['qualification']);
            
            // Validation
            if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($department) || empty($qualification)) {
                $error = "All fields are required!";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address!";
            } else {
                // Check if email already exists for another teacher
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE email = ? AND teacher_id != ?");
                $stmt->execute([$email, $teacher_id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "Email already exists for another teacher!";
                } else {
                    $stmt = $pdo->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?, department = ?, qualification = ? WHERE teacher_id = ?");
                    $stmt->execute([$first_name, $last_name, $email, $phone, $department, $qualification, $teacher_id]);
                    
                    // Update teacher array for display
                    $teacher['first_name'] = $first_name;
                    $teacher['last_name'] = $last_name;
                    $teacher['email'] = $email;
                    $teacher['phone'] = $phone;
                    $teacher['department'] = $department;
                    $teacher['qualification'] = $qualification;
                    
                    $success = "Profile updated successfully!";
                }
            }
        }
        
        if (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validation
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = "All password fields are required!";
            } elseif ($new_password !== $confirm_password) {
                $error = "New passwords do not match!";
            } elseif (strlen($new_password) < 6) {
                $error = "New password must be at least 6 characters long!";
            } elseif (!password_verify($current_password, $teacher['password'])) {
                $error = "Current password is incorrect!";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE teacher_id = ?");
                $stmt->execute([$hashed_password, $teacher_id]);
                
                // Update teacher array for display
                $teacher['password'] = $hashed_password;
                
                $success = "Password changed successfully!";
            }
        }
        
        if (isset($_POST['request_subject'])) {
            $subject_code = sanitizeInput($_POST['subject_code']);
            $request_message = sanitizeInput($_POST['request_message']);
            
            // Check if subject exists and is not already assigned to this teacher
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_code = ?");
            $stmt->execute([$subject_code]);
            $subject = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$subject) {
                $error = "Subject not found!";
            } elseif ($subject['teacher_id'] === $teacher_id) {
                $error = "You are already assigned to this subject!";
            } else {
                // For now, we'll just update the subject directly
                // In a real system, you might want to create a request system
                $stmt = $pdo->prepare("UPDATE subjects SET teacher_id = ? WHERE subject_code = ?");
                $stmt->execute([$teacher_id, $subject_code]);
                
                $success = "Subject assignment updated successfully!";
                
                // Refresh current subjects
                $stmt = $pdo->prepare("SELECT subject_code FROM subjects WHERE teacher_id = ?");
                $stmt->execute([$teacher_id]);
                $current_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
        }
        
        if (isset($_POST['remove_subject'])) {
            $subject_code = sanitizeInput($_POST['subject_code']);
            
            // Check if there are any attendance records for this subject
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE subject_code = ?");
            $stmt->execute([$subject_code]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Cannot remove subject with existing attendance records!";
            } else {
                // Remove the subject assignment (assign to a default teacher or leave unassigned)
                // For now, we'll assign to TCH001 (default teacher)
                $stmt = $pdo->prepare("UPDATE subjects SET teacher_id = 'TCH001' WHERE subject_code = ? AND teacher_id = ?");
                $stmt->execute([$subject_code, $teacher_id]);
                
                $success = "Subject removed from your assignments!";
                
                // Refresh current subjects
                $stmt = $pdo->prepare("SELECT subject_code FROM subjects WHERE teacher_id = ?");
                $stmt->execute([$teacher_id]);
                $current_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get teacher's current subjects with details
$stmt = $pdo->prepare("SELECT s.*, ay.year_name, sem.semester_name 
                      FROM subjects s 
                      JOIN academic_years ay ON s.academic_year_id = ay.id 
                      JOIN semesters sem ON s.semester_id = sem.id 
                      WHERE s.teacher_id = ?
                      ORDER BY ay.year_number ASC, sem.semester_number ASC, s.subject_name ASC");
$stmt->execute([$teacher_id]);
$assigned_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available subjects (not assigned to this teacher)
$available_subjects = array_filter($all_subjects, function($subject) use ($teacher_id) {
    return $subject['teacher_id'] !== $teacher_id;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile - College Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> College Attendance System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-user-circle"></i> Teacher Profile</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-user"></i> Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="teacher_id" class="form-label">Teacher ID</label>
                                        <input type="text" class="form-control" id="teacher_id" value="<?php echo htmlspecialchars($teacher['teacher_id']); ?>" readonly>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" required maxlength="50" value="<?php echo htmlspecialchars($teacher['first_name']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" required maxlength="50" value="<?php echo htmlspecialchars($teacher['last_name']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required maxlength="100" value="<?php echo htmlspecialchars($teacher['email']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required maxlength="15" value="<?php echo htmlspecialchars($teacher['phone']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                        <select class="form-select" id="department" name="department" required>
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept; ?>" <?php echo ($teacher['department'] === $dept) ? 'selected' : ''; ?>>
                                                    <?php echo $dept; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="qualification" class="form-label">Qualification <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="qualification" name="qualification" required maxlength="200" value="<?php echo htmlspecialchars($teacher['qualification']); ?>">
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-lock"></i> Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Subject Management -->
                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-book"></i> My Assigned Subjects</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($assigned_subjects)): ?>
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
                                                    <th>Credits</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assigned_subjects as $subject): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($subject['year_name'] . ' - ' . $subject['semester_name']); ?></td>
                                                        <td><?php echo $subject['credits']; ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-danger" onclick="removeSubject('<?php echo $subject['subject_code']; ?>', '<?php echo htmlspecialchars($subject['subject_name']); ?>')">
                                                                <i class="fas fa-times"></i> Remove
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
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus"></i> Request Subject Assignment</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="subject_code" class="form-label">Available Subjects</label>
                                        <select class="form-select" id="subject_code" name="subject_code" required>
                                            <option value="">Select Subject</option>
                                            <?php foreach ($available_subjects as $subject): ?>
                                                <option value="<?php echo $subject['subject_code']; ?>">
                                                    <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['year_name'] . ' - ' . $subject['semester_name'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="request_message" class="form-label">Request Message</label>
                                        <textarea class="form-control" id="request_message" name="request_message" rows="3" placeholder="Why do you want to teach this subject?"></textarea>
                                    </div>
                                    
                                    <button type="submit" name="request_subject" class="btn btn-success">
                                        <i class="fas fa-paper-plane"></i> Request Assignment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove Subject Modal -->
    <div class="modal fade" id="removeSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Remove Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="subject_code" id="remove_subject_code">
                        <p>Are you sure you want to remove the subject "<span id="remove_subject_name"></span>" from your assignments?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> This will remove you as the teacher for this subject.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="remove_subject" class="btn btn-danger">Remove Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        function removeSubject(subjectCode, subjectName) {
            document.getElementById('remove_subject_code').value = subjectCode;
            document.getElementById('remove_subject_name').textContent = subjectName;
            
            const removeModal = new bootstrap.Modal(document.getElementById('removeSubjectModal'));
            removeModal.show();
        }
    </script>
</body>
</html>