<?php
/**
 * Email Configuration for PHPMailer
 * Update these settings with your email credentials
 */

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);

// Your Gmail credentials
define('SMTP_USERNAME', 'your_email@gmail.com');  // Replace with your Gmail address
define('SMTP_PASSWORD', 'your_app_password');     // Replace with your Gmail app password
define('FROM_EMAIL', 'your_email@gmail.com');     // Replace with your Gmail address
define('FROM_NAME', 'College Attendance System');

/**
 * Instructions for Gmail App Password:
 * 
 * 1. Enable 2-Factor Authentication on your Gmail account
 * 2. Go to Google Account settings
 * 3. Navigate to Security > 2-Step Verification > App passwords
 * 4. Generate a new app password for "Mail"
 * 5. Use that 16-character password in SMTP_PASSWORD above
 * 
 * Note: Never use your regular Gmail password here!
 */

/**
 * Alternative Email Providers:
 * 
 * For Outlook/Hotmail:
 * SMTP_HOST = 'smtp-mail.outlook.com'
 * SMTP_PORT = 587
 * SMTP_SECURE = 'tls'
 * 
 * For Yahoo:
 * SMTP_HOST = 'smtp.mail.yahoo.com'
 * SMTP_PORT = 587
 * SMTP_SECURE = 'tls'
 * 
 * For Custom SMTP:
 * Update the host, port, and credentials according to your provider
 */

// Test email function
function testEmailConfiguration($test_email) {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    } elseif (file_exists(__DIR__ . '/includes/phpmailer/PHPMailer.php')) {
        require_once __DIR__ . '/includes/phpmailer/PHPMailer.php';
        require_once __DIR__ . '/includes/phpmailer/SMTP.php';
        require_once __DIR__ . '/includes/phpmailer/Exception.php';
    } else {
        return ['success' => false, 'message' => 'PHPMailer library not found'];
    }
    
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
        $mail->addAddress($test_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Test - College Attendance System';
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
                    <h1>✅ Email Test Successful</h1>
                    <p>College Attendance System</p>
                </div>
                <div class='content'>
                    <p>Hello!</p>
                    <p>This is a test email to verify that your email configuration is working correctly.</p>
                    <p>If you received this email, it means:</p>
                    <ul>
                        <li>Your SMTP settings are correct</li>
                        <li>PHPMailer is working properly</li>
                        <li>OTP emails will be sent successfully</li>
                    </ul>
                    <p>You can now use the attendance system with email OTP verification.</p>
                    <p>Best regards,<br>College Attendance System</p>
                </div>
                <div class='footer'>
                    <p>Test email sent on " . date('Y-m-d H:i:s') . "</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "Email test successful! Your email configuration is working correctly.";
        
        $mail->send();
        
        return ['success' => true, 'message' => 'Test email sent successfully!'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send test email: ' . $e->getMessage()];
    }
}

// Check if email is configured
function isEmailConfigured() {
    return SMTP_USERNAME !== 'your_email@gmail.com' && SMTP_PASSWORD !== 'your_app_password';
}
?>