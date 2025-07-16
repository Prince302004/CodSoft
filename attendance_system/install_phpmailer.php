<?php
/**
 * PHPMailer Quick Installation Script
 * 
 * This script downloads and installs PHPMailer manually for users
 * who don't have Composer installed
 */

// Configuration
$phpmailer_version = '6.8.0';
$phpmailer_url = "https://github.com/PHPMailer/PHPMailer/archive/v{$phpmailer_version}.zip";
$install_dir = __DIR__ . '/includes/phpmailer';
$temp_dir = sys_get_temp_dir();
$zip_file = $temp_dir . '/phpmailer.zip';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPMailer Installation - College Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .install-header {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
            padding: 2rem 0;
        }
        .step {
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .step-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .step-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .step-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }
        .step-info {
            background-color: #cce7ff;
            border-color: #b8daff;
        }
    </style>
</head>
<body>
    <div class="install-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="fas fa-download"></i> PHPMailer Installation</h1>
                    <p class="lead">Quick installation for users without Composer</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-cogs"></i> Installation Process</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
                            echo "<h5>Installation Progress:</h5>";
                            
                            // Step 1: Check requirements
                            echo "<div class='step step-info'>";
                            echo "<strong>Step 1:</strong> Checking requirements...";
                            
                            $requirements_met = true;
                            
                            if (!extension_loaded('zip')) {
                                echo "<br><span class='text-danger'>❌ ZIP extension not available</span>";
                                $requirements_met = false;
                            } else {
                                echo "<br><span class='text-success'>✅ ZIP extension available</span>";
                            }
                            
                            if (!extension_loaded('curl') && !ini_get('allow_url_fopen')) {
                                echo "<br><span class='text-danger'>❌ Neither cURL nor allow_url_fopen is available</span>";
                                $requirements_met = false;
                            } else {
                                echo "<br><span class='text-success'>✅ Network access available</span>";
                            }
                            
                            if (!is_writable(__DIR__ . '/includes')) {
                                echo "<br><span class='text-danger'>❌ includes/ directory not writable</span>";
                                $requirements_met = false;
                            } else {
                                echo "<br><span class='text-success'>✅ includes/ directory writable</span>";
                            }
                            
                            echo "</div>";
                            
                            if (!$requirements_met) {
                                echo "<div class='step step-error'>";
                                echo "<strong>Installation Failed:</strong> Requirements not met. Please fix the issues above.";
                                echo "</div>";
                            } else {
                                // Step 2: Download PHPMailer
                                echo "<div class='step step-info'>";
                                echo "<strong>Step 2:</strong> Downloading PHPMailer...";
                                
                                $download_success = false;
                                
                                if (extension_loaded('curl')) {
                                    $ch = curl_init();
                                    curl_setopt($ch, CURLOPT_URL, $phpmailer_url);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                                    
                                    $zip_data = curl_exec($ch);
                                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    curl_close($ch);
                                    
                                    if ($http_code === 200 && $zip_data !== false) {
                                        file_put_contents($zip_file, $zip_data);
                                        $download_success = true;
                                    }
                                } elseif (ini_get('allow_url_fopen')) {
                                    $zip_data = file_get_contents($phpmailer_url);
                                    if ($zip_data !== false) {
                                        file_put_contents($zip_file, $zip_data);
                                        $download_success = true;
                                    }
                                }
                                
                                if ($download_success) {
                                    echo "<br><span class='text-success'>✅ Downloaded PHPMailer v{$phpmailer_version}</span>";
                                } else {
                                    echo "<br><span class='text-danger'>❌ Download failed</span>";
                                }
                                
                                echo "</div>";
                                
                                if ($download_success) {
                                    // Step 3: Extract files
                                    echo "<div class='step step-info'>";
                                    echo "<strong>Step 3:</strong> Extracting files...";
                                    
                                    $zip = new ZipArchive();
                                    if ($zip->open($zip_file) === TRUE) {
                                        // Create installation directory
                                        if (!is_dir($install_dir)) {
                                            mkdir($install_dir, 0755, true);
                                        }
                                        
                                        // Extract specific files
                                        $files_to_extract = [
                                            "PHPMailer-{$phpmailer_version}/src/PHPMailer.php",
                                            "PHPMailer-{$phpmailer_version}/src/SMTP.php",
                                            "PHPMailer-{$phpmailer_version}/src/Exception.php",
                                            "PHPMailer-{$phpmailer_version}/src/OAuth.php",
                                            "PHPMailer-{$phpmailer_version}/src/POP3.php",
                                            "PHPMailer-{$phpmailer_version}/src/DSNConfigurator.php"
                                        ];
                                        
                                        $extracted_count = 0;
                                        foreach ($files_to_extract as $file) {
                                            if ($zip->locateName($file) !== false) {
                                                $content = $zip->getFromName($file);
                                                $filename = basename($file);
                                                file_put_contents($install_dir . '/' . $filename, $content);
                                                $extracted_count++;
                                            }
                                        }
                                        
                                        $zip->close();
                                        
                                        echo "<br><span class='text-success'>✅ Extracted {$extracted_count} files</span>";
                                        
                                        // Step 4: Verify installation
                                        echo "</div>";
                                        echo "<div class='step step-success'>";
                                        echo "<strong>Step 4:</strong> Verifying installation...";
                                        
                                        $required_files = ['PHPMailer.php', 'SMTP.php', 'Exception.php'];
                                        $all_files_exist = true;
                                        
                                        foreach ($required_files as $file) {
                                            if (file_exists($install_dir . '/' . $file)) {
                                                echo "<br><span class='text-success'>✅ {$file} installed</span>";
                                            } else {
                                                echo "<br><span class='text-danger'>❌ {$file} missing</span>";
                                                $all_files_exist = false;
                                            }
                                        }
                                        
                                        if ($all_files_exist) {
                                            echo "<br><br><strong class='text-success'>🎉 Installation completed successfully!</strong>";
                                        } else {
                                            echo "<br><br><strong class='text-danger'>❌ Installation incomplete</strong>";
                                        }
                                        
                                    } else {
                                        echo "<br><span class='text-danger'>❌ Failed to extract ZIP file</span>";
                                    }
                                    
                                    echo "</div>";
                                    
                                    // Cleanup
                                    if (file_exists($zip_file)) {
                                        unlink($zip_file);
                                    }
                                }
                            }
                        } else {
                            // Show installation form
                            ?>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Before Installation</h5>
                                <p>This script will download and install PHPMailer v<?php echo $phpmailer_version; ?> from GitHub.</p>
                                <ul>
                                    <li>Files will be installed to: <code><?php echo $install_dir; ?></code></li>
                                    <li>Requires ZIP extension and network access</li>
                                    <li>Will overwrite existing PHPMailer files</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle"></i> Alternative Methods</h5>
                                <p>If you prefer other installation methods:</p>
                                <ul>
                                    <li><strong>Composer:</strong> Run <code>composer install</code> in project directory</li>
                                    <li><strong>Manual:</strong> Download from <a href="https://github.com/PHPMailer/PHPMailer" target="_blank">GitHub</a> and extract to <code>includes/phpmailer/</code></li>
                                </ul>
                            </div>
                            
                            <form method="POST">
                                <div class="text-center">
                                    <button type="submit" name="install" class="btn btn-primary btn-lg">
                                        <i class="fas fa-download"></i> Install PHPMailer
                                    </button>
                                </div>
                            </form>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Next Steps -->
        <div class="row mt-4">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-arrow-right"></i> Next Steps</h4>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li><strong>Configure PHPMailer:</strong> Edit <code>includes/phpmailer_setup.php</code></li>
                            <li><strong>Update Email Settings:</strong> Set your SMTP credentials (Gmail App Password recommended)</li>
                            <li><strong>Test Configuration:</strong> Use <code>test_email.php</code> to verify setup</li>
                            <li><strong>Read Documentation:</strong> Check <code>PHPMAILER_SETUP.md</code> for detailed instructions</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="text-center mt-4 mb-4">
            <a href="xampp_quick_setup.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to XAMPP Setup
            </a>
            <a href="test_email.php" class="btn btn-primary">
                <i class="fas fa-envelope"></i> Test Email
            </a>
            <a href="PHPMAILER_SETUP.md" class="btn btn-info" target="_blank">
                <i class="fas fa-book"></i> Setup Guide
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>