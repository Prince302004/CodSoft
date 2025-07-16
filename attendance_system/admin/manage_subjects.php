<?php
require_once '../includes/config.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../index.php');
}

$error = '';
$success = '';

// Get data for dropdowns
$academic_years = [];
$semesters = [];
$teachers = [];

try {
    // Get academic years
    $stmt = $pdo->prepare("SELECT * FROM academic_years ORDER BY year_number ASC");
    $stmt->execute();
    $academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get semesters
    $stmt = $pdo->prepare("SELECT s.*, ay.year_name FROM semesters s 
                          JOIN academic_years ay ON s.academic_year_id = ay.id 
                          WHERE s.is_active = TRUE ORDER BY s.academic_year_id ASC, s.semester_number ASC");
    $stmt->execute();
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get teachers
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE status = 'active' ORDER BY first_name ASC");
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error loading data: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_subject'])) {
            $subject_code = sanitizeInput($_POST['subject_code']);
            $subject_name = sanitizeInput($_POST['subject_name']);
            $academic_year_id = (int)$_POST['academic_year_id'];
            $semester_id = (int)$_POST['semester_id'];
            $teacher_id = sanitizeInput($_POST['teacher_id']);
            $credits = (int)$_POST['credits'];
            $description = sanitizeInput($_POST['description']);
            
            // Check if subject code already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ?");
            $stmt->execute([$subject_code]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Subject code already exists!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, academic_year_id, semester_id, teacher_id, credits, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$subject_code, $subject_name, $academic_year_id, $semester_id, $teacher_id, $credits, $description]);
                $success = "Subject added successfully!";
            }
        }
        
        if (isset($_POST['edit_subject'])) {
            $subject_id = (int)$_POST['subject_id'];
            $subject_code = sanitizeInput($_POST['subject_code']);
            $subject_name = sanitizeInput($_POST['subject_name']);
            $academic_year_id = (int)$_POST['academic_year_id'];
            $semester_id = (int)$_POST['semester_id'];
            $teacher_id = sanitizeInput($_POST['teacher_id']);
            $credits = (int)$_POST['credits'];
            $description = sanitizeInput($_POST['description']);
            
            $stmt = $pdo->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, academic_year_id = ?, semester_id = ?, teacher_id = ?, credits = ?, description = ? WHERE id = ?");
            $stmt->execute([$subject_code, $subject_name, $academic_year_id, $semester_id, $teacher_id, $credits, $description, $subject_id]);
            $success = "Subject updated successfully!";
        }
        
        if (isset($_POST['delete_subject'])) {
            $subject_id = (int)$_POST['subject_id'];
            
            // Check if there are any attendance records for this subject
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE subject_code = (SELECT subject_code FROM subjects WHERE id = ?)");
            $stmt->execute([$subject_id]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Cannot delete subject with existing attendance records!";
            } else {
                // Delete student enrollments first
                $stmt = $pdo->prepare("DELETE FROM student_subjects WHERE subject_code = (SELECT subject_code FROM subjects WHERE id = ?)");
                $stmt->execute([$subject_id]);
                
                // Delete the subject
                $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
                $stmt->execute([$subject_id]);
                $success = "Subject deleted successfully!";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all subjects with related information
$stmt = $pdo->prepare("SELECT s.*, ay.year_name, sem.semester_name, t.first_name, t.last_name, t.teacher_id
                      FROM subjects s 
                      JOIN academic_years ay ON s.academic_year_id = ay.id 
                      JOIN semesters sem ON s.semester_id = sem.id 
                      JOIN teachers t ON s.teacher_id = t.teacher_id 
                      ORDER BY ay.year_number ASC, sem.semester_number ASC, s.subject_name ASC");
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - Admin Panel</title>
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
                        <a class="nav-link active" href="manage_subjects.php">Manage Subjects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_teachers.php">Manage Teachers</a>
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
                <h2><i class="fas fa-book"></i> Subject Management</h2>
                
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
                
                <!-- Add Subject Button -->
                <div class="mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus"></i> Add New Subject
                    </button>
                </div>
                
                <!-- Subjects Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> All Subjects</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Year/Semester</th>
                                        <th>Teacher</th>
                                        <th>Credits</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($subjects)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No subjects found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($subjects as $subject): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['year_name'] . ' - ' . $subject['semester_name']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['first_name'] . ' ' . $subject['last_name']); ?></td>
                                                <td><?php echo $subject['credits']; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editSubject(<?php echo $subject['id']; ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name']); ?>')">
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

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="subject_code" name="subject_code" required maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="credits" class="form-label">Credits <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="credits" name="credits" required min="1" max="10" value="3">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" required maxlength="100">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                    <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                                        <option value="">Select Academic Year</option>
                                        <?php foreach ($academic_years as $year): ?>
                                            <option value="<?php echo $year['id']; ?>"><?php echo htmlspecialchars($year['year_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="semester_id" class="form-label">Semester <span class="text-danger">*</span></label>
                                    <select class="form-select" id="semester_id" name="semester_id" required>
                                        <option value="">Select Semester</option>
                                        <?php foreach ($semesters as $semester): ?>
                                            <option value="<?php echo $semester['id']; ?>" data-year="<?php echo $semester['academic_year_id']; ?>">
                                                <?php echo htmlspecialchars($semester['semester_name'] . ' (' . $semester['year_name'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Assign Teacher <span class="text-danger">*</span></label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['teacher_id']; ?>">
                                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['department'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="subject_id" id="edit_subject_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_subject_code" name="subject_code" required maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_credits" class="form-label">Credits <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="edit_credits" name="credits" required min="1" max="10">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_subject_name" name="subject_name" required maxlength="100">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_academic_year_id" name="academic_year_id" required>
                                        <option value="">Select Academic Year</option>
                                        <?php foreach ($academic_years as $year): ?>
                                            <option value="<?php echo $year['id']; ?>"><?php echo htmlspecialchars($year['year_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_semester_id" class="form-label">Semester <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_semester_id" name="semester_id" required>
                                        <option value="">Select Semester</option>
                                        <?php foreach ($semesters as $semester): ?>
                                            <option value="<?php echo $semester['id']; ?>" data-year="<?php echo $semester['academic_year_id']; ?>">
                                                <?php echo htmlspecialchars($semester['semester_name'] . ' (' . $semester['year_name'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_teacher_id" class="form-label">Assign Teacher <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_teacher_id" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['teacher_id']; ?>">
                                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['department'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_subject" class="btn btn-warning">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Subject Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="subject_id" id="delete_subject_id">
                        <p>Are you sure you want to delete the subject "<span id="delete_subject_name"></span>"?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> This action cannot be undone. The subject will be permanently deleted.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_subject" class="btn btn-danger">Delete Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Subject data for editing
        const subjectsData = <?php echo json_encode($subjects); ?>;
        
        // Filter semesters based on selected academic year
        function filterSemesters(academicYearSelect, semesterSelect) {
            const selectedYear = academicYearSelect.value;
            const semesterOptions = semesterSelect.querySelectorAll('option');
            
            semesterOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    const yearId = option.dataset.year;
                    option.style.display = (yearId === selectedYear) ? 'block' : 'none';
                }
            });
            
            semesterSelect.value = '';
        }
        
        // Add subject modal semester filtering
        document.getElementById('academic_year_id').addEventListener('change', function() {
            filterSemesters(this, document.getElementById('semester_id'));
        });
        
        // Edit subject modal semester filtering
        document.getElementById('edit_academic_year_id').addEventListener('change', function() {
            filterSemesters(this, document.getElementById('edit_semester_id'));
        });
        
        function editSubject(subjectId) {
            const subject = subjectsData.find(s => s.id == subjectId);
            if (subject) {
                document.getElementById('edit_subject_id').value = subject.id;
                document.getElementById('edit_subject_code').value = subject.subject_code;
                document.getElementById('edit_subject_name').value = subject.subject_name;
                document.getElementById('edit_credits').value = subject.credits;
                document.getElementById('edit_academic_year_id').value = subject.academic_year_id;
                document.getElementById('edit_description').value = subject.description || '';
                
                // Filter and set semester
                filterSemesters(document.getElementById('edit_academic_year_id'), document.getElementById('edit_semester_id'));
                setTimeout(() => {
                    document.getElementById('edit_semester_id').value = subject.semester_id;
                }, 100);
                
                document.getElementById('edit_teacher_id').value = subject.teacher_id;
                
                const editModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
                editModal.show();
            }
        }
        
        function deleteSubject(subjectId, subjectName) {
            document.getElementById('delete_subject_id').value = subjectId;
            document.getElementById('delete_subject_name').textContent = subjectName;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteSubjectModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>