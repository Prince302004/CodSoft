<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Attendance Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/attendance_system/');

// Security Configuration
define('SECRET_KEY', 'your-secret-key-here-change-this');
define('OTP_EXPIRY_MINUTES', 5);
define('SESSION_TIMEOUT', 30); // minutes

// Email Configuration for OTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');

// Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Timezone
date_default_timezone_set('UTC');

// Helper Functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function check_role($required_role) {
    if (!is_logged_in()) {
        redirect('login.php');
    }
    
    if ($_SESSION['role'] !== $required_role && $_SESSION['role'] !== 'admin') {
        redirect('dashboard.php');
    }
}

function get_user_info() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>