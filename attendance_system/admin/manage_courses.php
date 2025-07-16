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
        if (isset($_POST['add_course'])) {
            $course_name = sanitizeInput($_POST['course_name']);
            $course_code = sanitizeInput($_POST['course_code']);
            $department = sanitizeInput($_POST['department']);
            $duration_years = (int)$_POST['duration_years'];
            $description = sanitizeInput($_POST['description']);
            
            // Validation
            if (empty($course_name) || empty($course_code) || empty($department) || empty($duration_years)) {
                $error = "All required fields must be filled!";
            } else {
                // Check if course code already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_programs WHERE course_code = ?");
                $stmt->execute([$course_code]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "Course code already exists!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO course_programs (course_name, course_code, department, duration_years, description) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$course_name, $course_code, $department, $duration_years, $description]);
                    $success = "Course added successfully!";
                }
            }
        }
        
        if (isset($_POST['edit_course'])) {
            $course_id = (int)$_POST['course_id'];
            $course_name = sanitizeInput($_POST['course_name']);
            $course_code = sanitizeInput($_POST['course_code']);
            $department = sanitizeInput($_POST['department']);
            $duration_years = (int)$_POST['duration_years'];
            $description = sanitizeInput($_POST['description']);
            $status = sanitizeInput($_POST['status']);
            
            // Validation
            if (empty($course_name) || empty($course_code) || empty($department) || empty($duration_years)) {
                $error = "All required fields must be filled!";
            } else {
                // Check if course code already exists for another course
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_programs WHERE course_code = ? AND id != ?");
                $stmt->execute([$course_code, $course_id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "Course code already exists for another course!";
                } else {
                    $stmt = $pdo->prepare("UPDATE course_programs SET course_name = ?, course_code = ?, department = ?, duration_years = ?, description = ?, status = ? WHERE id = ?");
                    $stmt->execute([$course_name, $course_code, $department, $duration_years, $description, $status, $course_id]);
                    $success = "Course updated successfully!";
                }
            }
        }
        
        if (isset($_POST['delete_course'])) {
            $course_id = (int)$_POST['course_id'];
            
            // Check if any students are enrolled in this course
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE course = (SELECT course_name FROM course_programs WHERE id = ?)");
            $stmt->execute([$course_id]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Cannot delete course with enrolled students!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM course_programs WHERE id = ?");
                $stmt->execute([$course_id]);
                $success = "Course deleted successfully!";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all courses
$stmt = $pdo->prepare("SELECT cp.*, COUNT(s.id) as student_count 
                      FROM course_programs cp 
                      LEFT JOIN students s ON cp.course_name = s.course AND s.status = 'active'
                      GROUP BY cp.id 
                      ORDER BY cp.course_name ASC");
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments for dropdown
$departments = ['Computer Science', 'Information Technology', 'Mathematics', 'English', 'Physics', 'Chemistry', 'Electronics', 'Mechanical Engineering', 'Civil Engineering', 'Business Administration'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin Panel</title>
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
                        <a class="nav-link" href="manage_teachers.php">Manage Teachers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_courses.php">Manage Courses</a>
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
                <h2><i class="fas fa-graduation-cap"></i> Course Management</h2>
                
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
                
                <!-- Add Course Button -->
                <div class="mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                        <i class="fas fa-plus"></i> Add New Course
                    </button>
                </div>
                
                <!-- Courses Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> All Courses</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Department</th>
                                        <th>Duration</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($courses)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No courses found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                <td><?php echo htmlspecialchars($course['department']); ?></td>
                                                <td><?php echo $course['duration_years']; ?> years</td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $course['student_count']; ?> students</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $course['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($course['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editCourse(<?php echo $course['id']; ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_name']); ?>')">
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

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="course_code" name="course_code" required maxlength="20" placeholder="e.g., BCA, MCA, BSC">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration_years" class="form-label">Duration (Years) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="duration_years" name="duration_years" required min="1" max="6" value="3">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="course_name" class="form-label">Course Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="course_name" name="course_name" required maxlength="100" placeholder="e.g., Bachelor of Computer Applications">
                        </div>
                        
                        <div class="mb-3">
                            <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="500" placeholder="Brief description of the course"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_course" class="btn btn-primary">Add Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div class="modal fade" id="editCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="course_id" id="edit_course_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_course_code" name="course_code" required maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_duration_years" class="form-label">Duration (Years) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="edit_duration_years" name="duration_years" required min="1" max="6">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_course_name" class="form-label">Course Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_course_name" name="course_name" required maxlength="100">
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
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_course" class="btn btn-warning">Update Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Course Modal -->
    <div class="modal fade" id="deleteCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="course_id" id="delete_course_id">
                        <p>Are you sure you want to delete the course "<span id="delete_course_name"></span>"?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> This action cannot be undone. The course will be permanently deleted.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_course" class="btn btn-danger">Delete Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Course data for editing
        const coursesData = <?php echo json_encode($courses); ?>;
        
        function editCourse(courseId) {
            const course = coursesData.find(c => c.id == courseId);
            if (course) {
                document.getElementById('edit_course_id').value = course.id;
                document.getElementById('edit_course_code').value = course.course_code;
                document.getElementById('edit_course_name').value = course.course_name;
                document.getElementById('edit_department').value = course.department;
                document.getElementById('edit_duration_years').value = course.duration_years;
                document.getElementById('edit_status').value = course.status;
                document.getElementById('edit_description').value = course.description || '';
                
                const editModal = new bootstrap.Modal(document.getElementById('editCourseModal'));
                editModal.show();
            }
        }
        
        function deleteCourse(courseId, courseName) {
            document.getElementById('delete_course_id').value = courseId;
            document.getElementById('delete_course_name').textContent = courseName;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteCourseModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>