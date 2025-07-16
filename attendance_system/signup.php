<?php
require_once 'includes/config.php';

// If already logged in, redirect to appropriate dashboard
if (isStudent()) {
    redirect('student/dashboard.php');
} elseif (isAdmin()) {
    redirect('admin/dashboard.php');
} elseif (isTeacher()) {
    redirect('teacher/dashboard.php');
}

$error = '';
$success = '';

// Get data for form dropdowns
$academic_years = [];
$semesters = [];

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
} catch (Exception $e) {
    $error = "Error loading form data: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = sanitizeInput($_POST['student_id']);
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $course = sanitizeInput($_POST['course']);
    $department = sanitizeInput($_POST['department']);
    $academic_year_id = (int)$_POST['academic_year_id'];
    $semester_id = (int)$_POST['semester_id'];
    $roll_number = sanitizeInput($_POST['roll_number']);
    $admission_date = sanitizeInput($_POST['admission_date']);
    $address = sanitizeInput($_POST['address']);
    $guardian_name = sanitizeInput($_POST['guardian_name']);
    $guardian_phone = sanitizeInput($_POST['guardian_phone']);
    
    // Validation
    if (empty($student_id) || empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password) || empty($course) || empty($department) || empty($roll_number) || empty($admission_date)) {
        $error = "All required fields must be filled!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        try {
            // Check if student ID or email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ? OR email = ?");
            $stmt->execute([$student_id, $email]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Student ID or email already exists!";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new student
                $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, email, phone, password, course, department, academic_year_id, semester_id, roll_number, admission_date, address, guardian_name, guardian_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$student_id, $first_name, $last_name, $email, $phone, $hashed_password, $course, $department, $academic_year_id, $semester_id, $roll_number, $admission_date, $address, $guardian_name, $guardian_phone])) {
                    // Get subjects for the selected year and semester
                    $stmt = $pdo->prepare("SELECT subject_code FROM subjects WHERE academic_year_id = ? AND semester_id = ?");
                    $stmt->execute([$academic_year_id, $semester_id]);
                    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Enroll student in subjects
                    foreach ($subjects as $subject) {
                        $stmt = $pdo->prepare("INSERT INTO student_subjects (student_id, subject_code, enrollment_date) VALUES (?, ?, ?)");
                        $stmt->execute([$student_id, $subject['subject_code'], $admission_date]);
                    }
                    
                    $success = "Registration successful! You can now login with your credentials.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - College Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mt-5 shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="fas fa-user-plus"></i> Student Registration</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                                <br><br>
                                <a href="index.php" class="btn btn-success">
                                    <i class="fas fa-sign-in-alt"></i> Go to Login
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" id="signupForm">
                                <div class="row">
                                    <!-- Basic Information -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="student_id" class="form-label">Student ID <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="student_id" name="student_id" required maxlength="20" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="roll_number" class="form-label">Roll Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="roll_number" name="roll_number" required maxlength="20" value="<?php echo htmlspecialchars($_POST['roll_number'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" required maxlength="50" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" required maxlength="50" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" required maxlength="100" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="phone" name="phone" required maxlength="15" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Academic Information -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="course" class="form-label">Course <span class="text-danger">*</span></label>
                                            <select class="form-select" id="course" name="course" required>
                                                <option value="">Select Course</option>
                                                <option value="Bachelor of Computer Applications" <?php echo (($_POST['course'] ?? '') == 'Bachelor of Computer Applications') ? 'selected' : ''; ?>>Bachelor of Computer Applications (BCA)</option>
                                                <option value="Bachelor of Science in Computer Science" <?php echo (($_POST['course'] ?? '') == 'Bachelor of Science in Computer Science') ? 'selected' : ''; ?>>Bachelor of Science in Computer Science</option>
                                                <option value="Bachelor of Technology" <?php echo (($_POST['course'] ?? '') == 'Bachelor of Technology') ? 'selected' : ''; ?>>Bachelor of Technology (B.Tech)</option>
                                                <option value="Master of Computer Applications" <?php echo (($_POST['course'] ?? '') == 'Master of Computer Applications') ? 'selected' : ''; ?>>Master of Computer Applications (MCA)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                            <select class="form-select" id="department" name="department" required>
                                                <option value="">Select Department</option>
                                                <option value="Computer Science" <?php echo (($_POST['department'] ?? '') == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                                <option value="Information Technology" <?php echo (($_POST['department'] ?? '') == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                                <option value="Mathematics" <?php echo (($_POST['department'] ?? '') == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                                                <option value="English" <?php echo (($_POST['department'] ?? '') == 'English') ? 'selected' : ''; ?>>English</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                            <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                                                <option value="">Select Academic Year</option>
                                                <?php foreach ($academic_years as $year): ?>
                                                    <option value="<?php echo $year['id']; ?>" <?php echo (($_POST['academic_year_id'] ?? '') == $year['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($year['year_name']); ?>
                                                    </option>
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
                                                    <option value="<?php echo $semester['id']; ?>" data-year="<?php echo $semester['academic_year_id']; ?>" <?php echo (($_POST['semester_id'] ?? '') == $semester['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($semester['semester_name'] . ' (' . $semester['year_name'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="admission_date" class="form-label">Admission Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="admission_date" name="admission_date" required value="<?php echo htmlspecialchars($_POST['admission_date'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Address Information -->
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="3" maxlength="500"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Guardian Information -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="guardian_name" class="form-label">Guardian Name</label>
                                            <input type="text" class="form-control" id="guardian_name" name="guardian_name" maxlength="100" value="<?php echo htmlspecialchars($_POST['guardian_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="guardian_phone" class="form-label">Guardian Phone</label>
                                            <input type="tel" class="form-control" id="guardian_phone" name="guardian_phone" maxlength="15" value="<?php echo htmlspecialchars($_POST['guardian_phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Password -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus"></i> Register
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="index.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter semesters based on selected academic year
        document.getElementById('academic_year_id').addEventListener('change', function() {
            const selectedYear = this.value;
            const semesterSelect = document.getElementById('semester_id');
            const semesterOptions = semesterSelect.querySelectorAll('option');
            
            semesterOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    const yearId = option.dataset.year;
                    option.style.display = (yearId === selectedYear) ? 'block' : 'none';
                }
            });
            
            // Reset semester selection
            semesterSelect.value = '';
        });
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>