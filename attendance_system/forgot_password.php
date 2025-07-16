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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $user_type = sanitizeInput($_POST['user_type']);
    
    if (empty($email) || empty($user_type)) {
        $error = "Please fill in all fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        try {
            // Check if email exists in the selected user type
            $user = null;
            switch ($user_type) {
                case 'student':
                    $stmt = $pdo->prepare("SELECT student_id as user_id, email, first_name, last_name FROM students WHERE email = ? AND status = 'active'");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    break;
                case 'teacher':
                    $stmt = $pdo->prepare("SELECT teacher_id as user_id, email, first_name, last_name FROM teachers WHERE email = ? AND status = 'active'");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    break;
                case 'admin':
                    $stmt = $pdo->prepare("SELECT username as user_id, email, username as first_name, '' as last_name FROM admin WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    break;
            }
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database
                $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, user_type, token, email, expires_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user['user_id'], $user_type, $token, $email, $expires_at]);
                
                // Send reset email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=" . $token;
                $user_name = $user['first_name'] . ' ' . $user['last_name'];
                
                sendPasswordResetEmail($email, $reset_link, $user_name, $user_type);
                
                $success = "Password reset instructions have been sent to your email address.";
            } else {
                $error = "Email address not found in our records!";
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
    <title>Forgot Password - College Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5 shadow">
                    <div class="card-header bg-warning text-dark text-center">
                        <h4><i class="fas fa-key"></i> Forgot Password</h4>
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
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Back to Login
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="user_type" class="form-label">I am a:</label>
                                    <select class="form-select" id="user_type" name="user_type" required>
                                        <option value="">Select User Type</option>
                                        <option value="student" <?php echo (($_POST['user_type'] ?? '') == 'student') ? 'selected' : ''; ?>>Student</option>
                                        <option value="teacher" <?php echo (($_POST['user_type'] ?? '') == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                        <option value="admin" <?php echo (($_POST['user_type'] ?? '') == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required maxlength="100" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" class="btn btn-warning btn-lg">
                                        <i class="fas fa-paper-plane"></i> Send Reset Instructions
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <p>Remember your password? <a href="index.php">Login here</a></p>
                            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>