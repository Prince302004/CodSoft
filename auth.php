<?php
require_once 'config.php';
require_once 'otp.php';

class AuthManager {
    private $pdo;
    private $otpManager;
    
    public function __construct($pdo, $otpManager) {
        $this->pdo = $pdo;
        $this->otpManager = $otpManager;
    }
    
    /**
     * Authenticate user with username and password
     */
    public function authenticate($username, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, password, email, phone, role, is_active 
                FROM users 
                WHERE username = ? OR email = ?
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if (!$user['is_active']) {
                    return ['success' => false, 'message' => 'Account is deactivated'];
                }
                
                return ['success' => true, 'user' => $user];
            }
            
            return ['success' => false, 'message' => 'Invalid username or password'];
        } catch (Exception $e) {
            error_log("Authentication Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication failed'];
        }
    }
    
    /**
     * Login user with OTP verification
     */
    public function loginWithOTP($username, $password, $otp_code) {
        $authResult = $this->authenticate($username, $password);
        
        if (!$authResult['success']) {
            return $authResult;
        }
        
        $user = $authResult['user'];
        
        // Verify OTP
        if (!$this->otpManager->verifyOTP($user['id'], $otp_code, 'login')) {
            return ['success' => false, 'message' => 'Invalid or expired OTP'];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Regular login without OTP
     */
    public function login($username, $password) {
        $authResult = $this->authenticate($username, $password);
        
        if (!$authResult['success']) {
            return $authResult;
        }
        
        $user = $authResult['user'];
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Check if session is expired
     */
    public function checkSessionExpiry() {
        if (isset($_SESSION['last_activity'])) {
            $inactive_time = time() - $_SESSION['last_activity'];
            $session_timeout = SESSION_TIMEOUT * 60; // Convert to seconds
            
            if ($inactive_time > $session_timeout) {
                $this->logout();
                return false;
            }
            
            $_SESSION['last_activity'] = time();
        }
        
        return true;
    }
    
    /**
     * Create new user
     */
    public function createUser($username, $password, $email, $phone, $role = 'student') {
        try {
            // Check if user already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, email, phone, role) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$username, $hashed_password, $email, $phone, $role]);
            
            if ($result) {
                $user_id = $this->pdo->lastInsertId();
                return ['success' => true, 'user_id' => $user_id];
            }
            
            return ['success' => false, 'message' => 'Failed to create user'];
        } catch (Exception $e) {
            error_log("Create User Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create user'];
        }
    }
    
    /**
     * Update user password
     */
    public function updatePassword($user_id, $old_password, $new_password) {
        try {
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($old_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashed_password, $user_id]);
            
            return ['success' => $result, 'message' => $result ? 'Password updated successfully' : 'Failed to update password'];
        } catch (Exception $e) {
            error_log("Update Password Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update password'];
        }
    }
    
    /**
     * Reset password with OTP
     */
    public function resetPasswordWithOTP($email, $otp_code, $new_password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify OTP
            if (!$this->otpManager->verifyOTP($user['id'], $otp_code, 'password_reset')) {
                return ['success' => false, 'message' => 'Invalid or expired OTP'];
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashed_password, $user['id']]);
            
            return ['success' => $result, 'message' => $result ? 'Password reset successfully' : 'Failed to reset password'];
        } catch (Exception $e) {
            error_log("Reset Password Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
    
    /**
     * Get user profile
     */
    public function getUserProfile($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, 
                       CASE 
                           WHEN u.role = 'teacher' THEN t.first_name
                           WHEN u.role = 'student' THEN s.first_name
                           ELSE NULL
                       END as first_name,
                       CASE 
                           WHEN u.role = 'teacher' THEN t.last_name
                           WHEN u.role = 'student' THEN s.last_name
                           ELSE NULL
                       END as last_name
                FROM users u
                LEFT JOIN teachers t ON u.id = t.user_id
                LEFT JOIN students s ON u.id = s.user_id
                WHERE u.id = ?
            ");
            
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get User Profile Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateUserProfile($user_id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$data['email'], $data['phone'], $user_id]);
            
            return ['success' => $result, 'message' => $result ? 'Profile updated successfully' : 'Failed to update profile'];
        } catch (Exception $e) {
            error_log("Update Profile Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($user_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("Update Last Login Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get User By ID Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Activate/Deactivate user
     */
    public function toggleUserStatus($user_id, $is_active) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $result = $stmt->execute([$is_active, $user_id]);
            
            return ['success' => $result, 'message' => $result ? 'User status updated' : 'Failed to update status'];
        } catch (Exception $e) {
            error_log("Toggle User Status Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }
}

// Create global auth manager instance
$authManager = new AuthManager($pdo, $otpManager);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'login':
            if (isset($_POST['username']) && isset($_POST['password'])) {
                $username = sanitize_input($_POST['username']);
                $password = $_POST['password'];
                
                $result = $authManager->login($username, $password);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing username or password']);
            }
            break;
            
        case 'login_otp':
            if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['otp_code'])) {
                $username = sanitize_input($_POST['username']);
                $password = $_POST['password'];
                $otp_code = sanitize_input($_POST['otp_code']);
                
                $result = $authManager->loginWithOTP($username, $password, $otp_code);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            }
            break;
            
        case 'logout':
            $result = $authManager->logout();
            echo json_encode($result);
            break;
            
        case 'create_user':
            if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['email'])) {
                $username = sanitize_input($_POST['username']);
                $password = $_POST['password'];
                $email = sanitize_input($_POST['email']);
                $phone = sanitize_input($_POST['phone'] ?? '');
                $role = sanitize_input($_POST['role'] ?? 'student');
                
                $result = $authManager->createUser($username, $password, $email, $phone, $role);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            }
            break;
            
        case 'update_password':
            if (isset($_POST['user_id']) && isset($_POST['old_password']) && isset($_POST['new_password'])) {
                $user_id = $_POST['user_id'];
                $old_password = $_POST['old_password'];
                $new_password = $_POST['new_password'];
                
                $result = $authManager->updatePassword($user_id, $old_password, $new_password);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            }
            break;
            
        case 'reset_password_otp':
            if (isset($_POST['email']) && isset($_POST['otp_code']) && isset($_POST['new_password'])) {
                $email = sanitize_input($_POST['email']);
                $otp_code = sanitize_input($_POST['otp_code']);
                $new_password = $_POST['new_password'];
                
                $result = $authManager->resetPasswordWithOTP($email, $otp_code, $new_password);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}

// Check session expiry on each request
if (is_logged_in()) {
    $authManager->checkSessionExpiry();
}
?>