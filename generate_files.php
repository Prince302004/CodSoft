<?php
/**
 * File Generator Script for Attendance Management System
 * This script creates all the necessary files and folder structure
 */

// Create directory structure
$directories = [
    'attendance-system',
    'attendance-system/assets',
    'attendance-system/assets/css',
    'attendance-system/assets/js'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

// Define all files and their contents
$files = [
    'attendance-system/database.sql' => '-- Attendance Management System Database Schema

CREATE DATABASE attendance_system;
USE attendance_system;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    role ENUM(\'admin\', \'teacher\', \'student\') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Teachers table
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    department VARCHAR(100),
    subject VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    class VARCHAR(50),
    section VARCHAR(10),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    section VARCHAR(10) NOT NULL,
    teacher_id INT NOT NULL,
    subject VARCHAR(100),
    schedule_time TIME,
    schedule_days VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- Attendance records table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    status ENUM(\'present\', \'absent\', \'late\', \'excused\') DEFAULT \'present\',
    teacher_location VARCHAR(255),
    teacher_latitude DECIMAL(10, 8),
    teacher_longitude DECIMAL(11, 8),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (class_id, student_id, date)
);

-- OTP verification table
CREATE TABLE otp_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM(\'login\', \'attendance\', \'password_reset\') NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Teacher locations table (for tracking teacher movement)
CREATE TABLE teacher_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    location_name VARCHAR(255),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO users (username, password, email, phone, role) VALUES
(\'admin\', \'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\', \'admin@school.com\', \'1234567890\', \'admin\'),
(\'teacher1\', \'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\', \'teacher1@school.com\', \'1234567891\', \'teacher\'),
(\'student1\', \'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\', \'student1@school.com\', \'1234567892\', \'student\');

INSERT INTO teachers (user_id, employee_id, first_name, last_name, department, subject) VALUES
(2, \'T001\', \'John\', \'Doe\', \'Computer Science\', \'Programming\');

INSERT INTO students (user_id, student_id, first_name, last_name, class, section) VALUES
(3, \'S001\', \'Jane\', \'Smith\', \'Grade 10\', \'A\');

INSERT INTO classes (class_name, section, teacher_id, subject, schedule_time, schedule_days) VALUES
(\'Grade 10\', \'A\', 1, \'Programming\', \'09:00:00\', \'Mon,Wed,Fri\');',

    'attendance-system/config.php' => '<?php
// Database Configuration
define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'attendance_system\');
define(\'DB_USER\', \'root\');
define(\'DB_PASS\', \'\');

// Application Configuration
define(\'APP_NAME\', \'Attendance Management System\');
define(\'APP_VERSION\', \'1.0.0\');
define(\'BASE_URL\', \'http://localhost/attendance_system/\');

// Security Configuration
define(\'SECRET_KEY\', \'your-secret-key-here-change-this\');
define(\'OTP_EXPIRY_MINUTES\', 5);
define(\'SESSION_TIMEOUT\', 30); // minutes

// Email Configuration for OTP
define(\'SMTP_HOST\', \'smtp.gmail.com\');
define(\'SMTP_PORT\', 587);
define(\'SMTP_USER\', \'your-email@gmail.com\');
define(\'SMTP_PASS\', \'your-app-password\');

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
date_default_timezone_set(\'UTC\');

// Helper Functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_csrf_token() {
    if (!isset($_SESSION[\'csrf_token\'])) {
        $_SESSION[\'csrf_token\'] = bin2hex(random_bytes(32));
    }
    return $_SESSION[\'csrf_token\'];
}

function verify_csrf_token($token) {
    return isset($_SESSION[\'csrf_token\']) && hash_equals($_SESSION[\'csrf_token\'], $token);
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION[\'user_id\']) && isset($_SESSION[\'role\']);
}

function check_role($required_role) {
    if (!is_logged_in()) {
        redirect(\'login.php\');
    }
    
    if ($_SESSION[\'role\'] !== $required_role && $_SESSION[\'role\'] !== \'admin\') {
        redirect(\'dashboard.php\');
    }
}

function get_user_info() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION[\'user_id\']]);
    return $stmt->fetch();
}
?>',

    'attendance-system/index.php' => '<?php
require_once \'config.php\';

// Check if user is logged in
if (is_logged_in()) {
    // Redirect to dashboard if logged in
    redirect(\'dashboard.php\');
} else {
    // Redirect to login if not logged in
    redirect(\'login.php\');
}
?>'
];

// Create files
foreach ($files as $filename => $content) {
    file_put_contents($filename, $content);
    echo "Created file: $filename\n";
}

echo "\n✅ All files created successfully!\n";
echo "📁 Project structure created in: attendance-system/\n";
echo "📝 Next steps:\n";
echo "   1. Import database.sql into MySQL\n";
echo "   2. Update config.php with your database credentials\n";
echo "   3. Access the system via web browser\n";
echo "   4. Login with: admin/password, teacher1/password, or student1/password\n";

// Create a simple ZIP creation script
echo "\n📦 To create ZIP file, run:\n";
echo "   zip -r attendance-system.zip attendance-system/\n";
echo "   or use your system's built-in ZIP utility\n";
?>