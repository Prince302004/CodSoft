<?php
session_start();
date_default_timezone_set('America/New_York');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_db');

// Campus location settings (example coordinates - adjust for your campus)
define('CAMPUS_LATITUDE', 40.7128);
define('CAMPUS_LONGITUDE', -74.0060);
define('CAMPUS_RADIUS', 100); // meters

// OTP settings
define('OTP_EXPIRY_MINUTES', 5);
define('OTP_LENGTH', 6);

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to generate OTP
function generateOTP($length = OTP_LENGTH) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Function to calculate distance between two points
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters
    
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLatRad = deg2rad($lat2 - $lat1);
    $deltaLonRad = deg2rad($lon2 - $lon1);
    
    $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLonRad / 2) * sin($deltaLonRad / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

// Function to verify location
function verifyLocation($userLat, $userLon) {
    $distance = calculateDistance(CAMPUS_LATITUDE, CAMPUS_LONGITUDE, $userLat, $userLon);
    return $distance <= CAMPUS_RADIUS;
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Email configuration for OTP
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@college.edu');
define('SMTP_PASSWORD', 'your_email_password');
define('FROM_EMAIL', 'noreply@college.edu');
define('FROM_NAME', 'College Attendance System');

// Function to send OTP via email
function sendOTP($email, $otp, $student_name = 'Student') {
    $subject = "Your Attendance System OTP Code";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background-color: #007bff; color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
            .content { padding: 20px; }
            .otp-code { font-size: 28px; font-weight: bold; color: #007bff; text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 5px; margin: 20px 0; letter-spacing: 5px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
            .warning { background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
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
                <p>You have requested to login to the College Attendance Management System. Please use the following OTP code to complete your login:</p>
                
                <div class='otp-code'>$otp</div>
                
                <div class='warning'>
                    <strong>⚠️ Important:</strong>
                    <ul>
                        <li>This OTP is valid for 5 minutes only</li>
                        <li>Do not share this code with anyone</li>
                        <li>If you didn't request this code, please ignore this email</li>
                    </ul>
                </div>
                
                <p>If you're having trouble logging in, please contact your system administrator.</p>
                
                <p>Best regards,<br>College Attendance System</p>
            </div>
            <div class='footer'>
                <p>This is an automated email. Please do not reply to this message.</p>
                <p>© " . date('Y') . " College Attendance Management System</p>
            </div>
        </div>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // For production, use a proper email library like PHPMailer or SwiftMailer
    // For now, using PHP's built-in mail function
    $result = mail($email, $subject, $message, $headers);
    
    if (!$result) {
        error_log("Failed to send OTP email to: $email");
        return false;
    }
    
    // Log for debugging (remove in production)
    error_log("OTP sent to email $email: $otp");
    return true;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['student_id']) || isset($_SESSION['admin_id']);
}

// Check if admin is logged in
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// Check if student is logged in
function isStudent() {
    return isset($_SESSION['student_id']);
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}
?>