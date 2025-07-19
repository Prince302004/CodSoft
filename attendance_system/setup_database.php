<?php
/**
 * Database Setup Script for College Attendance Management System
 * This script will create the database and all required tables
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'attendance_db';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Setup - Attendance System</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    <style>
        body { background-color: #f8f9fa; }
        .setup-card { max-width: 800px; margin: 50px auto; }
        .step { margin-bottom: 20px; padding: 15px; border-radius: 8px; }
        .step-success { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .step-error { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .step-info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='card setup-card shadow'>
            <div class='card-header bg-primary text-white text-center'>
                <h3><i class='fas fa-database'></i> Database Setup</h3>
                <p class='mb-0'>College Attendance Management System</p>
            </div>
            <div class='card-body'>";

try {
    // Step 1: Connect to MySQL server
    echo "<div class='step step-info'>
            <h5><i class='fas fa-plug'></i> Step 1: Connecting to MySQL Server</h5>";
    
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='text-success mb-0'><i class='fas fa-check'></i> Successfully connected to MySQL server</p>
          </div>";

    // Step 2: Create database
    echo "<div class='step step-info'>
            <h5><i class='fas fa-database'></i> Step 2: Creating Database</h5>";
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
    echo "<p class='text-success mb-0'><i class='fas fa-check'></i> Database '$database' created successfully</p>
          </div>";

    // Step 3: Select database
    $pdo->exec("USE `$database`");
    echo "<div class='step step-success'>
            <h5><i class='fas fa-check-circle'></i> Step 3: Database Selected</h5>
            <p class='mb-0'><i class='fas fa-check'></i> Using database '$database'</p>
          </div>";

    // Step 4: Import database schema
    echo "<div class='step step-info'>
            <h5><i class='fas fa-table'></i> Step 4: Creating Tables</h5>";
    
    $sql_file = file_get_contents('database.sql');
    $statements = explode(';', $sql_file);
    
    $tables_created = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^(--|#|\/\*)/', $statement)) {
            try {
                $pdo->exec($statement);
                if (preg_match('/CREATE TABLE/i', $statement)) {
                    $tables_created++;
                }
            } catch (PDOException $e) {
                // Ignore errors for existing tables
                if (!strpos($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        }
    }
    
    echo "<p class='text-success mb-0'><i class='fas fa-check'></i> All tables created successfully ($tables_created tables)</p>
          </div>";

    // Step 5: Verify setup
    echo "<div class='step step-info'>
            <h5><i class='fas fa-search'></i> Step 5: Verifying Setup</h5>";
    
    $tables = ['admin', 'teachers', 'students', 'subjects', 'attendance', 'otp_verification', 'password_reset_tokens'];
    $verified_tables = 0;
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $verified_tables++;
        }
    }
    
    echo "<p class='text-success mb-0'><i class='fas fa-check'></i> Verified $verified_tables essential tables</p>
          </div>";

    // Step 6: Check sample data
    echo "<div class='step step-info'>
            <h5><i class='fas fa-users'></i> Step 6: Checking Sample Data</h5>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $student_count = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teachers");
    $teacher_count = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects");
    $subject_count = $stmt->fetchColumn();
    
    echo "<p class='text-success mb-0'>
            <i class='fas fa-check'></i> Sample data loaded: $student_count students, $teacher_count teachers, $subject_count subjects
          </p>
          </div>";

    // Success message
    echo "<div class='step step-success'>
            <h5><i class='fas fa-trophy'></i> Setup Complete!</h5>
            <p class='mb-2'>Your database has been successfully set up with all required tables and sample data.</p>
            <div class='alert alert-info'>
                <h6><i class='fas fa-info-circle'></i> Default Login Credentials:</h6>
                <ul class='mb-0'>
                    <li><strong>Admin:</strong> Username: admin, Password: password</li>
                    <li><strong>Student:</strong> ID: STU001, Password: password</li>
                    <li><strong>Teacher:</strong> ID: TCH001, Password: password</li>
                </ul>
            </div>
            <div class='text-center mt-3'>
                <a href='index.php' class='btn btn-success btn-lg'>
                    <i class='fas fa-rocket'></i> Go to Login Page
                </a>
            </div>
          </div>";

} catch (PDOException $e) {
    echo "<div class='step step-error'>
            <h5><i class='fas fa-exclamation-triangle'></i> Setup Failed</h5>
            <p class='text-danger mb-2'><strong>Error:</strong> " . $e->getMessage() . "</p>
            <div class='alert alert-warning'>
                <h6><i class='fas fa-lightbulb'></i> Troubleshooting Tips:</h6>
                <ul class='mb-0'>
                    <li>Make sure MySQL server is running</li>
                    <li>Verify database credentials in the script</li>
                    <li>Ensure you have proper permissions</li>
                    <li>Check if the database.sql file exists</li>
                </ul>
            </div>
            <div class='text-center mt-3'>
                <button onclick='location.reload()' class='btn btn-warning'>
                    <i class='fas fa-redo'></i> Try Again
                </button>
            </div>
          </div>";
}

echo "</div>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>