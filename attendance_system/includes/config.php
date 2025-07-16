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

// Function to send OTP (simulated - in production, integrate with SMS service)
function sendOTP($phone, $otp) {
    // In production, integrate with SMS service like Twilio
    // For now, we'll just log it (in real app, you'd send SMS)
    error_log("OTP for $phone: $otp");
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