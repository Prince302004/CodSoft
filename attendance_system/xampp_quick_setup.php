<?php
/**
 * XAMPP Quick Setup Script
 * 
 * This script helps automate the initial setup for XAMPP environment
 * Run this script after placing files in htdocs folder
 */

// Check if running on XAMPP
$isXAMPP = (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) && 
           (strpos($_SERVER['DOCUMENT_ROOT'], 'xampp') !== false || 
            strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAMPP Quick Setup - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .setup-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 2rem 0;
        }
        .status-check {
            margin: 1rem 0;
        }
        .status-ok {
            color: #28a745;
        }
        .status-error {
            color: #dc3545;
        }
        .status-warning {
            color: #ffc107;
        }
        .setup-card {
            margin: 1rem 0;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="setup-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="fas fa-cog"></i> XAMPP Quick Setup</h1>
                    <p class="lead">College Attendance Management System</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Environment Check -->
        <div class="card setup-card">
            <div class="card-header">
                <h4><i class="fas fa-server"></i> Environment Check</h4>
            </div>
            <div class="card-body">
                <?php
                echo "<div class='status-check'>";
                echo "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
                echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
                echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
                echo "<strong>Current Directory:</strong> " . __DIR__ . "<br>";
                
                if ($isXAMPP) {
                    echo "<div class='alert alert-success mt-3'><i class='fas fa-check-circle'></i> XAMPP environment detected!</div>";
                } else {
                    echo "<div class='alert alert-warning mt-3'><i class='fas fa-exclamation-triangle'></i> XAMPP environment not clearly detected. Please verify you're running on XAMPP.</div>";
                }
                ?>
            </div>
        </div>

        <!-- Database Connection Check -->
        <div class="card setup-card">
            <div class="card-header">
                <h4><i class="fas fa-database"></i> Database Connection</h4>
            </div>
            <div class="card-body">
                <?php
                // Database connection test
                try {
                    $pdo = new PDO("mysql:host=localhost", "root", "");
                    echo "<div class='status-check status-ok'><i class='fas fa-check'></i> MySQL connection successful</div>";
                    
                    // Check if database exists
                    $stmt = $pdo->query("SHOW DATABASES LIKE 'attendance_db'");
                    if ($stmt->rowCount() > 0) {
                        echo "<div class='status-check status-ok'><i class='fas fa-check'></i> Database 'attendance_db' exists</div>";
                        
                        // Check tables
                        $pdo->exec("USE attendance_db");
                        $tables = ['admin', 'students', 'courses', 'attendance', 'otp_verification', 'campus_location'];
                        $existing_tables = [];
                        
                        foreach ($tables as $table) {
                            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                            if ($stmt->rowCount() > 0) {
                                $existing_tables[] = $table;
                            }
                        }
                        
                        if (count($existing_tables) === count($tables)) {
                            echo "<div class='status-check status-ok'><i class='fas fa-check'></i> All required tables exist</div>";
                        } else {
                            echo "<div class='status-check status-warning'><i class='fas fa-exclamation-triangle'></i> Missing tables: " . implode(', ', array_diff($tables, $existing_tables)) . "</div>";
                            echo "<div class='alert alert-warning mt-2'>Please import the database.sql file via phpMyAdmin</div>";
                        }
                    } else {
                        echo "<div class='status-check status-error'><i class='fas fa-times'></i> Database 'attendance_db' not found</div>";
                        echo "<div class='alert alert-danger mt-2'>Please create the database and import database.sql</div>";
                    }
                } catch (PDOException $e) {
                    echo "<div class='status-check status-error'><i class='fas fa-times'></i> Database connection failed: " . $e->getMessage() . "</div>";
                    echo "<div class='alert alert-danger mt-2'>Please make sure MySQL is running in XAMPP Control Panel</div>";
                }
                ?>
            </div>
        </div>

        <!-- File Permissions Check -->
        <div class="card setup-card">
            <div class="card-header">
                <h4><i class="fas fa-folder-open"></i> File System Check</h4>
            </div>
            <div class="card-body">
                <?php
                $required_files = [
                    'includes/config.php',
                    'index.php',
                    'database.sql',
                    'student/dashboard.php',
                    'admin/dashboard.php'
                ];
                
                foreach ($required_files as $file) {
                    if (file_exists($file)) {
                        echo "<div class='status-check status-ok'><i class='fas fa-check'></i> $file exists</div>";
                    } else {
                        echo "<div class='status-check status-error'><i class='fas fa-times'></i> $file missing</div>";
                    }
                }
                
                // Check write permissions
                $writable_dirs = ['includes'];
                foreach ($writable_dirs as $dir) {
                    if (is_writable($dir)) {
                        echo "<div class='status-check status-ok'><i class='fas fa-check'></i> $dir is writable</div>";
                    } else {
                        echo "<div class='status-check status-warning'><i class='fas fa-exclamation-triangle'></i> $dir may not be writable</div>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- PHP Extensions Check -->
        <div class="card setup-card">
            <div class="card-header">
                <h4><i class="fas fa-puzzle-piece"></i> PHP Extensions</h4>
            </div>
            <div class="card-body">
                <?php
                $required_extensions = [
                    'pdo' => 'PDO',
                    'pdo_mysql' => 'PDO MySQL',
                    'openssl' => 'OpenSSL (for email)',
                    'mbstring' => 'Multibyte String',
                    'curl' => 'cURL (optional)'
                ];
                
                foreach ($required_extensions as $ext => $name) {
                    if (extension_loaded($ext)) {
                        echo "<div class='status-check status-ok'><i class='fas fa-check'></i> $name loaded</div>";
                    } else {
                        echo "<div class='status-check status-error'><i class='fas fa-times'></i> $name not loaded</div>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- Email Configuration -->
        <div class="card setup-card">
            <div class="card-header">
                <h4><i class="fas fa-envelope"></i> Email Configuration</h4>
            </div>
            <div class="card-body">
                <?php
                if (file_exists('includes/config.php')) {
                    require_once 'includes/config.php';
                    
                    echo "<div class='status-check'><strong>SMTP Host:</strong> " . (defined('SMTP_HOST') ? SMTP_HOST : 'Not configured') . "</div>";
                    echo "<div class='status-check'><strong>SMTP Port:</strong> " . (defined('SMTP_PORT') ? SMTP_PORT : 'Not configured') . "</div>";
                    echo "<div class='status-check'><strong>From Email:</strong> " . (defined('FROM_EMAIL') ? FROM_EMAIL : 'Not configured') . "</div>";
                    
                    if (defined('SMTP_USERNAME') && SMTP_USERNAME !== 'your_email@gmail.com') {
                        echo "<div class='status-check status-ok'><i class='fas fa-check'></i> Email configuration appears to be set up</div>";
                    } else {
                        echo "<div class='status-check status-warning'><i class='fas fa-exclamation-triangle'></i> Email configuration needs to be updated</div>";
                    }
                } else {
                    echo "<div class='status-check status-error'><i class='fas fa-times'></i> Config file not found</div>";
                }
                ?>
                
                <div class="mt-3">
                    <a href="test_email.php" class="btn btn-primary">Test Email Configuration</a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card setup-card">
            <div class="card-header">
                <h4><i class="fas fa-tools"></i> Quick Actions</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <a href="http://localhost/phpmyadmin" class="btn btn-info w-100 mb-2" target="_blank">
                            <i class="fas fa-database"></i> Open phpMyAdmin
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="index.php" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-sign-in-alt"></i> Open Login Page
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="test_email.php" class="btn btn-warning w-100 mb-2">
                            <i class="fas fa-envelope"></i> Test Email
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup Instructions -->
        <div class="card setup-card">
            <div class="card-header">
                <h4><i class="fas fa-list-ol"></i> Setup Instructions</h4>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Database Setup:</strong>
                        <ul>
                            <li>Open <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a></li>
                            <li>Create database named <code>attendance_db</code></li>
                            <li>Import the <code>database.sql</code> file</li>
                        </ul>
                    </li>
                    <li><strong>Email Configuration:</strong>
                        <ul>
                            <li>Edit <code>includes/config.php</code></li>
                            <li>Update email credentials (Gmail recommended)</li>
                            <li>Test email using the test script</li>
                        </ul>
                    </li>
                    <li><strong>Campus Location:</strong>
                        <ul>
                            <li>Update <code>CAMPUS_LATITUDE</code> and <code>CAMPUS_LONGITUDE</code> in config</li>
                            <li>Set appropriate <code>CAMPUS_RADIUS</code> in meters</li>
                        </ul>
                    </li>
                    <li><strong>Test the System:</strong>
                        <ul>
                            <li>Access <a href="index.php">login page</a></li>
                            <li>Default admin: username <code>admin</code>, password <code>password</code></li>
                            <li>Default student: ID <code>STU001</code>, password <code>password</code></li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 mb-4">
            <p class="text-muted">
                For detailed setup instructions, see <code>XAMPP_SETUP.md</code><br>
                For email configuration help, see <code>EMAIL_SETUP.md</code>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>