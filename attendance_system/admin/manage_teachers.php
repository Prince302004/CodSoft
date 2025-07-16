<?php
require_once '../includes/config.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../index.php');
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_teacher'])) {
            $teacher_id = sanitizeInput($_POST['teacher_id']);
            $first_name = sanitizeInput($_POST['first_name']);
            $last_name = sanitizeInput($_POST['last_name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            $password = $_POST['password'];
            $department = sanitizeInput($_POST['department']);
            $qualification = sanitizeInput($_POST['qualification']);
            
            // Validation
            if (empty($teacher_id) || empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password) || empty($department) || empty($qualification)) {
                $error = "All fields are required!";
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters long!";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address!";
            } else {
                // Check if teacher ID or email already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE teacher_id = ? OR email = ?");
                $stmt->execute([$teacher_id, $email]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "Teacher ID or email already exists!";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO teachers (teacher_id, first_name, last_name, email, phone, password, department, qualification) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$teacher_id, $first_name, $last_name, $email, $phone, $hashed_password, $department, $qualification]);
                    $success = "Teacher added successfully!";
                }
            }
        }
        
        if (isset($_POST['edit_teacher'])) {
            $teacher_id = sanitizeInput($_POST['teacher_id']);
            $first_name = sanitizeInput($_POST['first_name']);
            $last_name = sanitizeInput($_POST['last_name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            $department = sanitizeInput($_POST['department']);
            $qualification = sanitizeInput($_POST['qualification']);
            $status = sanitizeInput($_POST['status']);
            
            // Validation
            if (empty($teacher_id) || empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($department) || empty($qualification)) {
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
                    $stmt = $pdo->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?, department = ?, qualification = ?, status = ? WHERE teacher_id = ?");
                    $stmt->execute([$first_name, $last_name, $email, $phone, $department, $qualification, $status, $teacher_id]);
                    $success = "Teacher updated successfully!";
                }
            }
        }
        
        if (isset($_POST['delete_teacher'])) {
            $teacher_id = sanitizeInput($_POST['teacher_id']);
            
            // Check if teacher has any assigned subjects
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE teacher_id = ?");
            $stmt->execute([$teacher_id]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Cannot delete teacher with assigned subjects! Please reassign subjects first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM teachers WHERE teacher_id = ?");
                $stmt->execute([$teacher_id]);
                $success = "Teacher deleted successfully!";
            }
        }
        
        if (isset($_POST['reset_password'])) {
            $teacher_id = sanitizeInput($_POST['teacher_id']);
            $new_password = $_POST['new_password'];
            
            if (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters long!";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE teacher_id = ?");
                $stmt->execute([$hashed_password, $teacher_id]);
                $success = "Password reset successfully!";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all teachers
$stmt = $pdo->prepare("SELECT *, (SELECT COUNT(*) FROM subjects WHERE teacher_id = t.teacher_id) as subject_count FROM teachers t ORDER BY t.first_name ASC");
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments for dropdown
$departments = ['Computer Science', 'Information Technology', 'Mathematics', 'English', 'Physics', 'Chemistry', 'Electronics', 'Mechanical Engineering', 'Civil Engineering', 'Business Administration'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> Admin Panel
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
                        <a class="nav-link" href="manage_subjects.php">Manage Subjects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_teachers.php">Manage Teachers</a>
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
                <h2><i class="fas fa-chalkboard-teacher"></i> Teacher Management</h2>
                
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
                
                <!-- Add Teacher Button -->
                <div class="mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <i class="fas fa-plus"></i> Add New Teacher
                    </button>
                </div>
                
                <!-- Teachers Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> All Teachers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Teacher ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Department</th>
                                        <th>Qualification</th>
                                        <th>Subjects</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($teachers)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No teachers found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['qualification']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $teacher['subject_count']; ?> subjects</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $teacher['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($teacher['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editTeacher('<?php echo $teacher['teacher_id']; ?>')">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-info" onclick="resetPassword('<?php echo $teacher['teacher_id']; ?>')">
                                                        <i class="fas fa-key"></i> Reset Password
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteTeacher('<?php echo $teacher['teacher_id']; ?>', '<?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="teacher_id" class="form-label">Teacher ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="teacher_id" name="teacher_id" required maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required maxlength="50">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required maxlength="50">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required maxlength="15">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="qualification" class="form-label">Qualification <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qualification" name="qualification" required maxlength="200" placeholder="e.g., PhD in Computer Science, M.Tech in IT">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="teacher_id" id="edit_teacher_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_first_name" name="first_name" required maxlength="50">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_last_name" name="last_name" required maxlength="50">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="edit_email" name="email" required maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="edit_phone" name="phone" required maxlength="15">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_department" class="form-label">Department <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_department" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_qualification" class="form-label">Qualification <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_qualification" name="qualification" required maxlength="200">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_teacher" class="btn btn-warning">Update Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="teacher_id" id="reset_teacher_id">
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> The teacher will need to use this new password to login.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reset_password" class="btn btn-info">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Teacher Modal -->
    <div class="modal fade" id="deleteTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="teacher_id" id="delete_teacher_id">
                        <p>Are you sure you want to delete the teacher "<span id="delete_teacher_name"></span>"?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> This action cannot be undone. The teacher will be permanently deleted.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_teacher" class="btn btn-danger">Delete Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Teacher data for editing
        const teachersData = <?php echo json_encode($teachers); ?>;
        
        function editTeacher(teacherId) {
            const teacher = teachersData.find(t => t.teacher_id === teacherId);
            if (teacher) {
                document.getElementById('edit_teacher_id').value = teacher.teacher_id;
                document.getElementById('edit_first_name').value = teacher.first_name;
                document.getElementById('edit_last_name').value = teacher.last_name;
                document.getElementById('edit_email').value = teacher.email;
                document.getElementById('edit_phone').value = teacher.phone;
                document.getElementById('edit_department').value = teacher.department;
                document.getElementById('edit_qualification').value = teacher.qualification;
                document.getElementById('edit_status').value = teacher.status;
                
                const editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
                editModal.show();
            }
        }
        
        function resetPassword(teacherId) {
            document.getElementById('reset_teacher_id').value = teacherId;
            
            const resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            resetModal.show();
        }
        
        function deleteTeacher(teacherId, teacherName) {
            document.getElementById('delete_teacher_id').value = teacherId;
            document.getElementById('delete_teacher_name').textContent = teacherName;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteTeacherModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>