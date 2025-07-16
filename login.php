<?php
require_once 'config.php';
require_once 'auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password'];
        
        $result = $authManager->login($username, $password);
        
        if ($result['success']) {
            redirect('dashboard.php');
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-user-graduate"></i> <?php echo APP_NAME; ?></h2>
                <p>Please sign in to your account</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Regular Login Form -->
            <form id="loginForm" method="POST">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordToggle"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="text-center mt-3">
                <button type="button" class="btn btn-link" onclick="showOTPLogin()">
                    <i class="fas fa-mobile-alt"></i> Login with OTP
                </button>
            </div>
            
            <div class="text-center mt-2">
                <a href="forgot-password.php" class="text-decoration-none">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </div>
            
            <!-- OTP Login Form (Hidden by default) -->
            <form id="otpLoginForm" method="POST" style="display: none;">
                <input type="hidden" name="action" value="login_otp">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="otp_username">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="otp_username" name="username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="otp_password">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="otp_password" name="password" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <button type="button" class="btn btn-outline-primary w-100 send-otp-btn" onclick="sendLoginOTP()">
                            <i class="fas fa-paper-plane"></i> Send OTP
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-link" onclick="showRegularLogin()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <div class="form-group mt-3" id="otpCodeGroup" style="display: none;">
                    <label for="otp_code">OTP Code</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="text" class="form-control otp-input" id="otp_code" name="otp_code" maxlength="6" placeholder="Enter 6-digit OTP">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mt-3" id="otpLoginBtn" style="display: none;">
                    <i class="fas fa-sign-in-alt"></i> Login with OTP
                </button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    For testing: Use 'admin/password', 'teacher1/password', or 'student1/password'
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/app.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
        
        // Show OTP login form
        function showOTPLogin() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('otpLoginForm').style.display = 'block';
        }
        
        // Show regular login form
        function showRegularLogin() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('otpLoginForm').style.display = 'none';
            document.getElementById('otpCodeGroup').style.display = 'none';
            document.getElementById('otpLoginBtn').style.display = 'none';
        }
        
        // Send OTP for login
        async function sendLoginOTP() {
            const username = document.getElementById('otp_username').value;
            const password = document.getElementById('otp_password').value;
            
            if (!username || !password) {
                alert('Please enter username and password first.');
                return;
            }
            
            // First verify credentials
            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'login',
                        username: username,
                        password: password
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Credentials are valid, now send OTP
                    // For simplicity, we'll show a mock OTP
                    document.getElementById('otpCodeGroup').style.display = 'block';
                    document.getElementById('otpLoginBtn').style.display = 'block';
                    
                    // In development, show OTP in console
                    const otpCode = Math.floor(100000 + Math.random() * 900000);
                    console.log('OTP Code (Dev Mode):', otpCode);
                    alert(`OTP Code (Dev Mode): ${otpCode}`);
                    
                    // Auto-fill OTP for testing
                    document.getElementById('otp_code').value = otpCode;
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Failed to verify credentials. Please try again.');
            }
        }
        
        // Initialize current user for JavaScript
        window.currentUser = null;
        
        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>