<?php
require_once 'includes/config.php';

// If already logged in, redirect to appropriate dashboard
if (isStudent()) {
    redirect('student/dashboard.php');
} elseif (isAdmin()) {
    redirect('admin/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $student_id = sanitizeInput($_POST['student_id']);
        $password = $_POST['password'];
        
        // Check student credentials
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND status = 'active'");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student && password_verify($password, $student['password'])) {
            // Generate OTP
            $otp = generateOTP();
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
            
            // Store OTP in database
            $stmt = $pdo->prepare("INSERT INTO otp_verification (student_id, otp_code, phone, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $otp, $student['phone'], $expires_at]);
            
            // Send OTP (simulated)
            sendOTP($student['phone'], $otp);
            
            $_SESSION['temp_student_id'] = $student_id;
            $success = "OTP sent to your registered phone number.";
        } else {
            $error = "Invalid credentials!";
        }
    }
    
    if (isset($_POST['verify_otp'])) {
        $otp = sanitizeInput($_POST['otp']);
        $student_id = $_SESSION['temp_student_id'];
        
        // Verify OTP
        $stmt = $pdo->prepare("SELECT * FROM otp_verification WHERE student_id = ? AND otp_code = ? AND expires_at > NOW() AND is_verified = FALSE ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$student_id, $otp]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($otp_record) {
            // Mark OTP as verified
            $stmt = $pdo->prepare("UPDATE otp_verification SET is_verified = TRUE WHERE id = ?");
            $stmt->execute([$otp_record['id']]);
            
            // Set session
            $_SESSION['student_id'] = $student_id;
            unset($_SESSION['temp_student_id']);
            
            redirect('student/dashboard.php');
        } else {
            $error = "Invalid or expired OTP!";
        }
    }
    
    if (isset($_POST['admin_login'])) {
        $username = sanitizeInput($_POST['admin_username']);
        $password = $_POST['admin_password'];
        
        // Check admin credentials
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            redirect('admin/dashboard.php');
        } else {
            $error = "Invalid admin credentials!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Attendance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left side - Login Forms -->
            <div class="col-md-6 d-flex align-items-center justify-content-center bg-light">
                <div class="card shadow-lg border-0 rounded-4" style="width: 100%; max-width: 400px;">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary mb-1">Welcome Back</h2>
                            <p class="text-muted">Sign in to your account</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Student Login Form -->
                        <div id="student-login-form" <?php echo isset($_SESSION['temp_student_id']) ? 'style="display:none;"' : ''; ?>>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Student ID</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" name="student_id" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                </div>
                                
                                <button type="submit" name="login" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-sign-in-alt"></i> Sign In
                                </button>
                            </form>
                        </div>
                        
                        <!-- OTP Verification Form -->
                        <div id="otp-verification-form" <?php echo !isset($_SESSION['temp_student_id']) ? 'style="display:none;"' : ''; ?>>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Enter OTP</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="text" class="form-control" name="otp" maxlength="6" required>
                                    </div>
                                    <small class="text-muted">OTP expires in 5 minutes</small>
                                </div>
                                
                                <button type="submit" name="verify_otp" class="btn btn-success w-100 mb-3">
                                    <i class="fas fa-check"></i> Verify OTP
                                </button>
                            </form>
                        </div>
                        
                        <div class="text-center">
                            <button class="btn btn-outline-secondary" onclick="toggleAdminLogin()">
                                <i class="fas fa-cog"></i> Admin Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right side - Hero Section -->
            <div class="col-md-6 d-flex align-items-center justify-content-center bg-primary text-white">
                <div class="text-center">
                    <h1 class="display-4 fw-bold mb-4">
                        <i class="fas fa-graduation-cap"></i> College Attendance
                    </h1>
                    <p class="lead mb-4">Modern attendance management system with location verification and OTP authentication</p>
                    <div class="row text-center">
                        <div class="col-4">
                            <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                            <h5>Location Verified</h5>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                            <h5>OTP Secured</h5>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-clock fa-3x mb-3"></i>
                            <h5>Real-time</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Admin Login Modal -->
    <div class="modal fade" id="adminLoginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admin Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="admin_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="admin_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="admin_login" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>