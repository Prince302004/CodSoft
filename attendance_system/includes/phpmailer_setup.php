<?php
/**
 * PHPMailer Setup and Configuration
 * 
 * This file handles PHPMailer library inclusion and SMTP configuration
 * For both Composer and manual installation methods
 */

// Check if PHPMailer is available via Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $phpmailer_available = true;
} elseif (file_exists(__DIR__ . '/phpmailer/PHPMailer.php')) {
    // Manual installation method
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
    require_once __DIR__ . '/phpmailer/Exception.php';
    $phpmailer_available = true;
} else {
    $phpmailer_available = false;
}

// SMTP Configuration Constants
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'your_email@gmail.com'); // Update with your email
define('SMTP_PASSWORD', 'your_app_password'); // Update with your app password
define('FROM_EMAIL', 'your_email@gmail.com'); // Update with your email
define('FROM_NAME', 'College Attendance System');

/**
 * Create and configure PHPMailer instance
 */
function createMailer() {
    global $phpmailer_available;
    
    if (!$phpmailer_available) {
        throw new Exception('PHPMailer library is not available. Please install it via Composer or manually.');
    }
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
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
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        
        return $mail;
        
    } catch (Exception $e) {
        throw new Exception("PHPMailer configuration failed: " . $e->getMessage());
    }
}

/**
 * Send OTP email using PHPMailer
 */
function sendOtpEmail($email, $otp, $user_name = 'User') {
    try {
        $mail = createMailer();
        
        $mail->addAddress($email, $user_name);
        $mail->Subject = 'Your OTP for College Attendance System';
        
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
        
        // Log for debugging (remove in production)
        error_log("OTP sent to email $email: $otp");
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send OTP email to $email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send password reset email using PHPMailer
 */
function sendPasswordResetEmail($email, $reset_link, $user_name = 'User', $user_type = 'student') {
    try {
        $mail = createMailer();
        
        $mail->addAddress($email, $user_name);
        $mail->Subject = 'Password Reset Request - College Attendance System';
        
        $user_type_label = ucfirst($user_type);
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background-color: #ffc107; color: #212529; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                .content { padding: 20px; }
                .reset-button { display: inline-block; padding: 15px 30px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
                .warning { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 College Attendance System</h1>
                    <p>Password Reset Request</p>
                </div>
                <div class='content'>
                    <p>Dear $user_name,</p>
                    <p>You have requested to reset your password for the College Attendance Management System ($user_type_label Account).</p>
                    
                    <p>Click the button below to reset your password:</p>
                    
                    <div style='text-align: center;'>
                        <a href='$reset_link' class='reset-button'>Reset Password</a>
                    </div>
                    
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #007bff;'>$reset_link</p>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Notice:</strong>
                        <ul>
                            <li>This link is valid for 1 hour only</li>
                            <li>If you didn't request this reset, please ignore this email</li>
                            <li>For security reasons, please don't share this link with anyone</li>
                        </ul>
                    </div>
                    
                    <p>If you're having trouble resetting your password, please contact your system administrator.</p>
                    
                    <p>Best regards,<br>College Attendance System</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>© " . date('Y') . " College Attendance Management System</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "Password Reset Request\n\nDear $user_name,\n\nYou have requested to reset your password for the College Attendance Management System ($user_type_label Account).\n\nClick this link to reset your password:\n$reset_link\n\nThis link is valid for 1 hour only.\n\nIf you didn't request this reset, please ignore this email.\n\nBest regards,\nCollege Attendance System";
        
        $mail->send();
        
        // Log for debugging (remove in production)
        error_log("Password reset email sent to: $email");
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send password reset email to $email: " . $e->getMessage());
        return false;
    }
}

/**
 * Test email configuration
 */
function testEmailConfiguration($test_email) {
    try {
        $mail = createMailer();
        
        $mail->addAddress($test_email);
        $mail->Subject = 'Test Email - College Attendance System';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background-color: #28a745; color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                .content { padding: 20px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Email Configuration Test</h1>
                    <p>College Attendance System</p>
                </div>
                <div class='content'>
                    <p>Congratulations!</p>
                    <p>Your email configuration is working correctly.</p>
                    
                    <p><strong>Configuration Details:</strong></p>
                    <ul>
                        <li>SMTP Host: " . SMTP_HOST . "</li>
                        <li>SMTP Port: " . SMTP_PORT . "</li>
                        <li>SMTP Security: " . SMTP_SECURE . "</li>
                        <li>From Email: " . FROM_EMAIL . "</li>
                        <li>From Name: " . FROM_NAME . "</li>
                    </ul>
                    
                    <p>The system is now ready to send OTP and password reset emails.</p>
                    
                    <p>Test completed on: " . date('Y-m-d H:i:s') . "</p>
                </div>
                <div class='footer'>
                    <p>This is a test email from College Attendance Management System</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "Email Configuration Test\n\nCongratulations! Your email configuration is working correctly.\n\nConfiguration Details:\n- SMTP Host: " . SMTP_HOST . "\n- SMTP Port: " . SMTP_PORT . "\n- SMTP Security: " . SMTP_SECURE . "\n- From Email: " . FROM_EMAIL . "\n- From Name: " . FROM_NAME . "\n\nThe system is now ready to send OTP and password reset emails.\n\nTest completed on: " . date('Y-m-d H:i:s');
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email test failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if PHPMailer is properly configured
 */
function isEmailConfigured() {
    global $phpmailer_available;
    
    if (!$phpmailer_available) {
        return false;
    }
    
    // Check if basic configuration is set
    if (SMTP_USERNAME === 'your_email@gmail.com' || 
        SMTP_PASSWORD === 'your_app_password' || 
        FROM_EMAIL === 'your_email@gmail.com') {
        return false;
    }
    
    return true;
}

?>