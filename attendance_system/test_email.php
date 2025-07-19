<?php
require_once 'email_config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_email'])) {
    $test_email = sanitizeInput($_POST['test_email']);
    
    if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = 'danger';
    } else {
        $result = testEmailConfiguration($test_email);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Configuration - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .test-card { max-width: 600px; margin: 50px auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card test-card shadow">
            <div class="card-header bg-info text-white text-center">
                <h4><i class="fas fa-envelope"></i> Test Email Configuration</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i> 
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Email Configuration Status:</h6>
                    <ul class="mb-0">
                        <li><strong>SMTP Host:</strong> <?php echo SMTP_HOST; ?></li>
                        <li><strong>SMTP Port:</strong> <?php echo SMTP_PORT; ?></li>
                        <li><strong>SMTP Username:</strong> <?php echo SMTP_USERNAME; ?></li>
                        <li><strong>From Email:</strong> <?php echo FROM_EMAIL; ?></li>
                        <li><strong>Configuration:</strong> 
                            <?php if (isEmailConfigured()): ?>
                                <span class="text-success">✅ Configured</span>
                            <?php else: ?>
                                <span class="text-danger">❌ Not Configured</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
                
                <?php if (!isEmailConfigured()): ?>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Configuration Required</h6>
                        <p class="mb-2">Please update your email settings in <code>email_config.php</code>:</p>
                        <ol class="mb-0">
                            <li>Replace <code>your_email@gmail.com</code> with your actual Gmail address</li>
                            <li>Replace <code>your_app_password</code> with your Gmail app password</li>
                            <li>Make sure 2-Factor Authentication is enabled on your Gmail account</li>
                        </ol>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="test_email" class="form-label">Test Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="test_email" name="test_email" 
                                       placeholder="Enter email to send test message" required>
                            </div>
                            <small class="text-muted">Enter an email address where you want to receive the test message</small>
                        </div>
                        
                        <button type="submit" name="test_email" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                    </form>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                    <a href="otp_verification.php" class="btn btn-outline-primary">
                        <i class="fas fa-shield-alt"></i> OTP Verification
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>