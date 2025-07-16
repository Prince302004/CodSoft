<?php
/**
 * Email Configuration Test Script
 * 
 * This script tests the PHPMailer configuration and sends a test email
 */

require_once 'includes/config.php';

$message = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_email = sanitizeInput($_POST['test_email']);
    
    if (empty($test_email)) {
        $error = "Please enter a test email address";
    } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Test email configuration
        if (testEmailConfiguration($test_email)) {
            $message = "Test email sent successfully to: $test_email";
        } else {
            $error = "Failed to send test email. Please check your configuration.";
        }
    }
}

// Get current configuration status
$config_status = isEmailConfigured();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - College Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem 0;
        }
        .config-check {
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 10px;
        }
        .config-ok {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .config-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .config-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="test-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="fas fa-envelope-open-text"></i> Email Configuration Test</h1>
                    <p class="lead">Test PHPMailer Email Configuration</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Configuration Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-cogs"></i> Current Configuration Status</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($config_status): ?>
                            <div class="config-check config-ok">
                                <i class="fas fa-check-circle"></i> 
                                <strong>Configuration Status:</strong> Ready
                                <p class="mb-0 mt-2">PHPMailer is configured and ready to send emails.</p>
                            </div>
                        <?php else: ?>
                            <div class="config-check config-error">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Configuration Status:</strong> Not Ready
                                <p class="mb-0 mt-2">PHPMailer configuration needs to be updated. Please check the following:</p>
                                <ul class="mt-2">
                                    <li>Update SMTP_USERNAME in phpmailer_setup.php</li>
                                    <li>Update SMTP_PASSWORD in phpmailer_setup.php</li>
                                    <li>Update FROM_EMAIL in phpmailer_setup.php</li>
                                    <li>Ensure PHPMailer library is installed</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Current Settings Display -->
                        <div class="mt-3">
                            <h6>Current Settings:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>SMTP Host:</strong> <?php echo defined('SMTP_HOST') ? SMTP_HOST : 'Not defined'; ?></p>
                                    <p><strong>SMTP Port:</strong> <?php echo defined('SMTP_PORT') ? SMTP_PORT : 'Not defined'; ?></p>
                                    <p><strong>SMTP Security:</strong> <?php echo defined('SMTP_SECURE') ? SMTP_SECURE : 'Not defined'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>From Email:</strong> <?php echo defined('FROM_EMAIL') ? FROM_EMAIL : 'Not defined'; ?></p>
                                    <p><strong>From Name:</strong> <?php echo defined('FROM_NAME') ? FROM_NAME : 'Not defined'; ?></p>
                                    <p><strong>PHPMailer Available:</strong> <?php echo isset($phpmailer_available) && $phpmailer_available ? 'Yes' : 'No'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Form -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-paper-plane"></i> Send Test Email</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="test_email" class="form-label">Test Email Address</label>
                                <input type="email" class="form-control" id="test_email" name="test_email" 
                                       placeholder="Enter email address to test" required
                                       value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>">
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> 
                                    A test email will be sent to this address to verify configuration.
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Test Email
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup Instructions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-question-circle"></i> Setup Instructions</h4>
                    </div>
                    <div class="card-body">
                        <h6>To configure PHPMailer for Gmail:</h6>
                        <ol>
                            <li><strong>Install PHPMailer:</strong>
                                <ul>
                                    <li>Via Composer: <code>composer install</code></li>
                                    <li>Or download PHPMailer files to <code>includes/phpmailer/</code></li>
                                </ul>
                            </li>
                            <li><strong>Enable 2-Factor Authentication</strong> on your Gmail account</li>
                            <li><strong>Generate App Password:</strong>
                                <ul>
                                    <li>Go to Google Account settings</li>
                                    <li>Security → App passwords</li>
                                    <li>Generate password for "Mail"</li>
                                </ul>
                            </li>
                            <li><strong>Update Configuration:</strong>
                                <p>Edit <code>includes/phpmailer_setup.php</code> and update:</p>
                                <ul>
                                    <li><code>SMTP_USERNAME</code> → Your Gmail address</li>
                                    <li><code>SMTP_PASSWORD</code> → Your App Password (not regular password)</li>
                                    <li><code>FROM_EMAIL</code> → Your Gmail address</li>
                                </ul>
                            </li>
                        </ol>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb"></i> 
                            <strong>Pro Tip:</strong> Use App Passwords instead of your regular Gmail password for better security.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="text-center mt-4 mb-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
            <a href="xampp_quick_setup.php" class="btn btn-info">
                <i class="fas fa-cogs"></i> XAMPP Setup
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>