<?php
// PHPMailer Configuration for Production Use
// Install PHPMailer via Composer: composer require phpmailer/phpmailer

require_once 'config.php';

// Uncomment the following lines when using PHPMailer
/*
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
*/

// Enhanced email sending function using PHPMailer
function sendOTPWithPHPMailer($email, $otp, $student_name = 'Student') {
    /*
    // Uncomment and configure when using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($email, $student_name);
        $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Attendance System OTP Code';
        $mail->Body    = getOTPEmailTemplate($otp, $student_name);
        $mail->AltBody = "Dear $student_name,\n\nYour OTP code is: $otp\n\nThis code is valid for 5 minutes only.\n\nBest regards,\nCollege Attendance System";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
    */
    
    // Fallback to regular mail function
    return sendOTP($email, $otp, $student_name);
}

// Email template function
function getOTPEmailTemplate($otp, $student_name) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>OTP Verification</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f8f9fa;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: white;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            .header {
                background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }
            .header p {
                margin: 5px 0 0 0;
                opacity: 0.9;
            }
            .content {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 18px;
                margin-bottom: 20px;
                color: #2c3e50;
            }
            .otp-section {
                text-align: center;
                margin: 30px 0;
            }
            .otp-label {
                font-size: 16px;
                color: #6c757d;
                margin-bottom: 10px;
            }
            .otp-code {
                font-size: 36px;
                font-weight: bold;
                color: #007bff;
                background-color: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                letter-spacing: 8px;
                border: 2px dashed #007bff;
                display: inline-block;
                margin: 10px 0;
            }
            .instructions {
                background-color: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 20px;
                border-radius: 5px;
                margin: 25px 0;
            }
            .instructions h3 {
                color: #856404;
                margin-top: 0;
                font-size: 16px;
            }
            .instructions ul {
                color: #856404;
                margin: 10px 0;
                padding-left: 20px;
            }
            .instructions li {
                margin: 8px 0;
            }
            .security-notice {
                background-color: #f8d7da;
                border-left: 4px solid #dc3545;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .security-notice p {
                color: #721c24;
                margin: 0;
                font-weight: 500;
            }
            .footer {
                background-color: #f8f9fa;
                text-align: center;
                padding: 25px;
                border-top: 1px solid #dee2e6;
            }
            .footer p {
                color: #6c757d;
                margin: 5px 0;
                font-size: 14px;
            }
            .footer .company {
                font-weight: 600;
                color: #495057;
            }
            @media (max-width: 600px) {
                .content {
                    padding: 30px 20px;
                }
                .otp-code {
                    font-size: 28px;
                    letter-spacing: 5px;
                }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎓 College Attendance System</h1>
                <p>Secure OTP Verification</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    Dear " . htmlspecialchars($student_name) . ",
                </div>
                
                <p>You have requested to access the College Attendance Management System. To complete your login, please use the verification code below:</p>
                
                <div class='otp-section'>
                    <div class='otp-label'>Your verification code is:</div>
                    <div class='otp-code'>$otp</div>
                </div>
                
                <div class='instructions'>
                    <h3>📋 Instructions:</h3>
                    <ul>
                        <li>Enter this code in the OTP verification field</li>
                        <li>This code is valid for <strong>5 minutes only</strong></li>
                        <li>Do not share this code with anyone</li>
                        <li>If you didn't request this login, please ignore this email</li>
                    </ul>
                </div>
                
                <div class='security-notice'>
                    <p>🔒 For your security, this email was sent from a secure server. If you're experiencing any issues, please contact your system administrator immediately.</p>
                </div>
                
                <p>Thank you for using our attendance management system!</p>
                
                <p style='margin-top: 30px;'>
                    Best regards,<br>
                    <strong>College Attendance System Team</strong>
                </p>
            </div>
            
            <div class='footer'>
                <p class='company'>College Attendance Management System</p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>© " . date('Y') . " All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
}

// Gmail SMTP Configuration Example
/*
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password'); // Use App Password for Gmail
define('FROM_EMAIL', 'your_email@gmail.com');
define('FROM_NAME', 'College Attendance System');
*/

// Office 365 SMTP Configuration Example
/*
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@yourdomain.com');
define('SMTP_PASSWORD', 'your_password');
define('FROM_EMAIL', 'your_email@yourdomain.com');
define('FROM_NAME', 'College Attendance System');
*/

// Custom SMTP Configuration Example
/*
define('SMTP_HOST', 'mail.yourdomain.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@yourdomain.com');
define('SMTP_PASSWORD', 'your_password');
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'College Attendance System');
*/
?>