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
$token = '';
$valid_token = false;

// Check if token is provided and valid
if (isset($_GET['token'])) {
    $token = sanitizeInput($_GET['token']);
    
    // Verify token
    $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() AND is_used = FALSE");
    $stmt->execute([$token]);
    $reset_token = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reset_token) {
        $valid_token = true;
    } else {
        $error = "Invalid or expired reset token!";
    }
} else {
    $error = "No reset token provided!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        try {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in appropriate table
            switch ($reset_token['user_type']) {
                case 'student':
                    $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                    $stmt->execute([$hashed_password, $reset_token['user_id']]);
                    break;
                case 'teacher':
                    $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE teacher_id = ?");
                    $stmt->execute([$hashed_password, $reset_token['user_id']]);
                    break;
                case 'admin':
                    $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE username = ?");
                    $stmt->execute([$hashed_password, $reset_token['user_id']]);
                    break;
            }
            
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE password_reset_tokens SET is_used = TRUE WHERE token = ?");
            $stmt->execute([$token]);
            
            $success = "Password reset successful! You can now login with your new password.";
            
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
    <title>Reset Password - College Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5 shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h4><i class="fas fa-lock"></i> Reset Password</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                            <div class="text-center">
                                <a href="forgot_password.php" class="btn btn-warning">
                                    <i class="fas fa-redo"></i> Request New Reset Link
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                                <br><br>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Go to Login
                                </a>
                            </div>
                        <?php elseif ($valid_token): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle"></i> Please enter your new password below.
                            </div>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    <div class="form-text">Password must be at least 6 characters long.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-check"></i> Reset Password
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <p>Remember your password? <a href="index.php">Login here</a></p>
                        </div>
                    </div>
                </div>
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
    </script>
</body>
</html>