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
$show_otp_form = false;
$user_email = '';
$user_name = '';
$user_type = '';

// Include email configuration
require_once 'email_config.php';

// Check if PHPMailer is available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $phpmailer_available = true;
} elseif (file_exists(__DIR__ . '/includes/phpmailer/PHPMailer.php')) {
    require_once __DIR__ . '/includes/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/includes/phpmailer/SMTP.php';
    require_once __DIR__ . '/includes/phpmailer/Exception.php';
    $phpmailer_available = true;
} else {
    $phpmailer_available = false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_otp'])) {
        $user_id = sanitizeInput($_POST['user_id']);
        $password = $_POST['password'];
        $login_type = sanitizeInput($_POST['login_type']);
        
        $user = null;
        
        // Check credentials based on login type
        if ($login_type == 'student') {
            $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND status = 'active'");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($login_type == 'teacher') {
            $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ? AND status = 'active'");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($user && password_verify($password, $user['password'])) {
            if (!$phpmailer_available) {
                $error = "Email service is not available. Please contact administrator.";
            } else {
                // Generate OTP
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
                
                // Store OTP in database
                $stmt = $pdo->prepare("INSERT INTO otp_verification (user_id, user_type, otp_code, email, expires_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $login_type, $otp, $user['email'], $expires_at]);
                
                // Send OTP using PHPMailer directly
                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = SMTP_HOST;
                    $mail->SMTPAuth = SMTP_AUTH;
                    $mail->Username = SMTP_USERNAME;
                    $mail->Password = SMTP_PASSWORD;
                    $mail->SMTPSecure = SMTP_SECURE;
                    $mail->Port = SMTP_PORT;
                    
                    // Recipients
                    $mail->setFrom(FROM_EMAIL, FROM_NAME);
                    $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your OTP for College Attendance System';
                    
                    $user_name = $user['first_name'] . ' ' . $user['last_name'];
                    
                    $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                            .header { background-color: #007bff; color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                            .content { padding: 20px; }
                            .otp-code { font-size: 36px; font-weight: bold; color: #007bff; text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 10px; margin: 20px 0; letter-spacing: 5px; }
                            .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
                            .warning { background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>🎓 College Attendance System</h1>
                                <p>One-Time Password (OTP)</p>
                            </div>
                            <div class='content'>
                                <p>Dear $user_name,</p>
                                <p>Your OTP for accessing the College Attendance Management System is:</p>
                                
                                <div class='otp-code'>$otp</div>
                                
                                <div class='warning'>
                                    <strong>⚠️ Important:</strong>
                                    <ul>
                                        <li>This OTP is valid for " . OTP_EXPIRY_MINUTES . " minutes only</li>
                                        <li>Do not share this code with anyone</li>
                                        <li>Enter this code on the login page to access your account</li>
                                    </ul>
                                </div>
                                
                                <p>If you didn't request this OTP, please ignore this email and contact your system administrator.</p>
                                
                                <p>Best regards,<br>College Attendance System</p>
                            </div>
                            <div class='footer'>
                                <p>This is an automated email. Please do not reply to this message.</p>
                                <p>© " . date('Y') . " College Attendance Management System</p>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    $mail->AltBody = "Your OTP for College Attendance System is: $otp\n\nThis OTP is valid for " . OTP_EXPIRY_MINUTES . " minutes only.\nDo not share this code with anyone.";
                    
                    $mail->send();
                    
                    // Store user info for OTP form
                    $_SESSION['temp_user_id'] = $user_id;
                    $_SESSION['temp_user_type'] = $login_type;
                    $user_email = $user['email'];
                    $user_name = $user['first_name'] . ' ' . $user['last_name'];
                    $user_type = $login_type;
                    $show_otp_form = true;
                    $success = "OTP sent to your registered email address.";
                    
                } catch (Exception $e) {
                    $error = "Failed to send OTP email: " . $e->getMessage();
                }
            }
        } else {
            $error = "Invalid credentials!";
        }
    }
    
    if (isset($_POST['verify_otp'])) {
        $otp = sanitizeInput($_POST['otp']);
        $user_id = $_SESSION['temp_user_id'];
        $user_type = $_SESSION['temp_user_type'];
        
        // Verify OTP
        $stmt = $pdo->prepare("SELECT * FROM otp_verification WHERE user_id = ? AND user_type = ? AND otp_code = ? AND expires_at > NOW() AND is_verified = FALSE ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id, $user_type, $otp]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($otp_record) {
            // Mark OTP as verified
            $stmt = $pdo->prepare("UPDATE otp_verification SET is_verified = TRUE WHERE id = ?");
            $stmt->execute([$otp_record['id']]);
            
            // Set session based on user type
            if ($user_type == 'student') {
                $_SESSION['student_id'] = $user_id;
                $redirect_url = 'student/dashboard.php';
            } elseif ($user_type == 'teacher') {
                $_SESSION['teacher_id'] = $user_id;
                $redirect_url = 'teacher/dashboard.php';
            }
            
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_user_type']);
            
            redirect($redirect_url);
        } else {
            $error = "Invalid or expired OTP!";
            $show_otp_form = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - College Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5 shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="fas fa-shield-alt"></i> OTP Verification</h4>
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
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$show_otp_form): ?>
                            <!-- Login Form -->
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">I am a:</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="login_type" id="student" value="student" checked>
                                        <label class="btn btn-outline-primary" for="student">
                                            <i class="fas fa-user-graduate"></i> Student
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="login_type" id="teacher" value="teacher">
                                        <label class="btn btn-outline-primary" for="teacher">
                                            <i class="fas fa-chalkboard-teacher"></i> Teacher
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label" id="user-id-label">Student ID</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" name="user_id" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                </div>
                                
                                <button type="submit" name="send_otp" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-paper-plane"></i> Send OTP
                                </button>
                            </form>
                            
                            <div class="text-center">
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Login
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- OTP Verification Form -->
                            <div class="text-center mb-4">
                                <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                                <h5>OTP Sent Successfully!</h5>
                                <p class="text-muted">
                                    We've sent a 6-digit OTP to:<br>
                                    <strong><?php echo htmlspecialchars($user_email); ?></strong>
                                </p>
                            </div>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Enter 6-Digit OTP</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="text" class="form-control text-center" name="otp" maxlength="6" required 
                                               pattern="[0-9]{6}" placeholder="000000" style="font-size: 1.5rem; letter-spacing: 0.5rem;">
                                    </div>
                                    <small class="text-muted">OTP expires in <?php echo OTP_EXPIRY_MINUTES; ?> minutes</small>
                                </div>
                                
                                <button type="submit" name="verify_otp" class="btn btn-success w-100 mb-3">
                                    <i class="fas fa-check"></i> Verify OTP
                                </button>
                            </form>
                            
                            <div class="text-center">
                                <button class="btn btn-outline-secondary" onclick="location.reload()">
                                    <i class="fas fa-redo"></i> Try Different Account
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle user type selection
        document.querySelectorAll('input[name="login_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const userIdLabel = document.getElementById('user-id-label');
                if (this.value === 'student') {
                    userIdLabel.textContent = 'Student ID';
                } else if (this.value === 'teacher') {
                    userIdLabel.textContent = 'Teacher ID';
                }
            });
        });
        
        // OTP input formatting
        const otpInput = document.querySelector('input[name="otp"]');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        }
    </script>
</body>
</html>