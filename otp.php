<?php
require_once 'config.php';

class OTPManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate a 6-digit OTP code
     */
    public function generateOTP() {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Save OTP to database
     */
    public function saveOTP($user_id, $otp_code, $purpose = 'login') {
        try {
            // Delete any existing OTP for this user and purpose
            $this->cleanupOTP($user_id, $purpose);
            
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO otp_verification (user_id, otp_code, purpose, expires_at) 
                VALUES (?, ?, ?, ?)
            ");
            
            return $stmt->execute([$user_id, $otp_code, $purpose, $expires_at]);
        } catch (Exception $e) {
            error_log("OTP Save Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send OTP via SMS (Mock implementation)
     */
    public function sendOTPSMS($phone, $otp_code) {
        // Mock SMS sending - Replace with actual SMS API
        $message = "Your OTP for " . APP_NAME . " is: " . $otp_code . ". Valid for " . OTP_EXPIRY_MINUTES . " minutes.";
        
        // Log the OTP for development (remove in production)
        error_log("SMS OTP to {$phone}: {$otp_code}");
        
        // Simulate successful sending
        return true;
    }
    
    /**
     * Send OTP via Email (Mock implementation)
     */
    public function sendOTPEmail($email, $otp_code) {
        $subject = "OTP Verification - " . APP_NAME;
        $message = "
            <html>
            <head>
                <title>OTP Verification</title>
            </head>
            <body>
                <h2>OTP Verification</h2>
                <p>Your OTP code is: <strong>{$otp_code}</strong></p>
                <p>This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.</p>
                <p>If you didn't request this code, please ignore this email.</p>
            </body>
            </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_USER . "\r\n";
        
        // Log the OTP for development (remove in production)
        error_log("Email OTP to {$email}: {$otp_code}");
        
        // Use PHP mail function (configure sendmail or use phpmailer for production)
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Verify OTP code
     */
    public function verifyOTP($user_id, $otp_code, $purpose = 'login') {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM otp_verification 
                WHERE user_id = ? AND otp_code = ? AND purpose = ? 
                AND expires_at > NOW() AND is_used = FALSE
            ");
            
            $stmt->execute([$user_id, $otp_code, $purpose]);
            $result = $stmt->fetch();
            
            if ($result) {
                // Mark OTP as used
                $this->markOTPAsUsed($result['id']);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("OTP Verification Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark OTP as used
     */
    private function markOTPAsUsed($otp_id) {
        $stmt = $this->pdo->prepare("UPDATE otp_verification SET is_used = TRUE WHERE id = ?");
        return $stmt->execute([$otp_id]);
    }
    
    /**
     * Clean up old/expired OTP codes
     */
    public function cleanupOTP($user_id = null, $purpose = null) {
        $query = "DELETE FROM otp_verification WHERE expires_at < NOW() OR is_used = TRUE";
        $params = [];
        
        if ($user_id) {
            $query .= " OR user_id = ?";
            $params[] = $user_id;
            
            if ($purpose) {
                $query .= " AND purpose = ?";
                $params[] = $purpose;
            }
        }
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }
    
    /**
     * Generate and send OTP to user
     */
    public function generateAndSendOTP($user_id, $purpose = 'login') {
        try {
            // Get user details
            $stmt = $this->pdo->prepare("SELECT email, phone FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            $otp_code = $this->generateOTP();
            
            if (!$this->saveOTP($user_id, $otp_code, $purpose)) {
                return ['success' => false, 'message' => 'Failed to save OTP'];
            }
            
            // Send OTP via SMS and Email
            $sms_sent = $this->sendOTPSMS($user['phone'], $otp_code);
            $email_sent = $this->sendOTPEmail($user['email'], $otp_code);
            
            if ($sms_sent || $email_sent) {
                return [
                    'success' => true, 
                    'message' => 'OTP sent successfully',
                    'otp_code' => $otp_code // Remove in production
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to send OTP'];
            }
            
        } catch (Exception $e) {
            error_log("Generate and Send OTP Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
}

// Create global OTP manager instance
$otpManager = new OTPManager($pdo);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'send_otp':
            if (isset($_POST['user_id']) && isset($_POST['purpose'])) {
                $result = $otpManager->generateAndSendOTP($_POST['user_id'], $_POST['purpose']);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            }
            break;
            
        case 'verify_otp':
            if (isset($_POST['user_id']) && isset($_POST['otp_code']) && isset($_POST['purpose'])) {
                $result = $otpManager->verifyOTP($_POST['user_id'], $_POST['otp_code'], $_POST['purpose']);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}
?>