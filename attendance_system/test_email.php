<?php
/**
 * Email OTP Test Script
 * 
 * This script helps you test the email OTP functionality
 * Run this script to verify your email configuration is working
 */

require_once 'includes/config.php';

// Test configuration
$test_email = 'test@example.com'; // Change to your test email
$test_student_name = 'Test Student';
$test_otp = generateOTP();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email OTP Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header'>
                        <h3>Email OTP Test Results</h3>
                    </div>
                    <div class='card-body'>";

// Display current configuration
echo "<h5>Current Email Configuration:</h5>
      <ul>
          <li><strong>SMTP Host:</strong> " . SMTP_HOST . "</li>
          <li><strong>SMTP Port:</strong> " . SMTP_PORT . "</li>
          <li><strong>From Email:</strong> " . FROM_EMAIL . "</li>
          <li><strong>From Name:</strong> " . FROM_NAME . "</li>
      </ul>";

// Test email sending
echo "<h5>Testing Email Delivery:</h5>";
echo "<p><strong>Test Email:</strong> $test_email</p>";
echo "<p><strong>Generated OTP:</strong> $test_otp</p>";

// Attempt to send email
$start_time = microtime(true);
$result = sendOTP($test_email, $test_otp, $test_student_name);
$end_time = microtime(true);
$execution_time = round(($end_time - $start_time) * 1000, 2);

if ($result) {
    echo "<div class='alert alert-success'>
            <h6>✅ Email sent successfully!</h6>
            <p>Execution time: {$execution_time}ms</p>
            <p>Check your email inbox (and spam folder) for the OTP.</p>
          </div>";
} else {
    echo "<div class='alert alert-danger'>
            <h6>❌ Email delivery failed!</h6>
            <p>Execution time: {$execution_time}ms</p>
            <p>Please check your email configuration and server logs.</p>
          </div>";
}

// Display troubleshooting information
echo "<h5>Troubleshooting:</h5>
      <div class='alert alert-info'>
          <h6>Common Issues:</h6>
          <ul>
              <li>Check SMTP credentials in <code>includes/config.php</code></li>
              <li>Verify firewall doesn't block SMTP ports</li>
              <li>Ensure PHP mail() function is enabled</li>
              <li>Check server error logs for detailed error messages</li>
              <li>For Gmail: Use App Password instead of regular password</li>
              <li>For production: Consider using PHPMailer</li>
          </ul>
      </div>";

// Display server information
echo "<h5>Server Information:</h5>
      <ul>
          <li><strong>PHP Version:</strong> " . phpversion() . "</li>
          <li><strong>Mail Function:</strong> " . (function_exists('mail') ? 'Available' : 'Not Available') . "</li>
          <li><strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? 'Available' : 'Not Available') . "</li>
          <li><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</li>
      </ul>";

// Email template preview
echo "<h5>Email Template Preview:</h5>
      <div class='border p-3' style='max-height: 400px; overflow-y: auto;'>
          <iframe srcdoc='" . htmlspecialchars(getOTPEmailTemplate($test_otp, $test_student_name)) . "' 
                  style='width: 100%; height: 300px; border: none;'></iframe>
      </div>";

echo "      </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>";

// Function to get email template (fallback if not using PHPMailer)
function getOTPEmailTemplate($otp, $student_name) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background-color: #007bff; color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
            .content { padding: 20px; }
            .otp-code { font-size: 28px; font-weight: bold; color: #007bff; text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 5px; margin: 20px 0; letter-spacing: 5px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎓 College Attendance System</h1>
                <p>OTP Verification Code</p>
            </div>
            <div class='content'>
                <p>Dear $student_name,</p>
                <p>Your OTP code is:</p>
                <div class='otp-code'>$otp</div>
                <p>This code is valid for 5 minutes only.</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " College Attendance System</p>
            </div>
        </div>
    </body>
    </html>";
}
?>