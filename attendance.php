<?php
require_once 'config.php';
require_once 'otp.php';
require_once 'geolocation.php';

class AttendanceManager {
    private $pdo;
    private $otpManager;
    private $geoManager;
    
    public function __construct($pdo, $otpManager, $geoManager) {
        $this->pdo = $pdo;
        $this->otpManager = $otpManager;
        $this->geoManager = $geoManager;
    }
    
    /**
     * Mark attendance for a student
     */
    public function markAttendance($class_id, $student_id, $teacher_id, $status = 'present', $latitude = null, $longitude = null, $notes = null) {
        try {
            $date = date('Y-m-d');
            $time_in = date('H:i:s');
            
            // Get location information if coordinates provided
            $location_name = null;
            if ($latitude && $longitude) {
                $location_name = $this->geoManager->getLocationName($latitude, $longitude);
            }
            
            // Check if attendance already exists for today
            $stmt = $this->pdo->prepare("
                SELECT id FROM attendance 
                WHERE class_id = ? AND student_id = ? AND date = ?
            ");
            $stmt->execute([$class_id, $student_id, $date]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing attendance
                $stmt = $this->pdo->prepare("
                    UPDATE attendance 
                    SET status = ?, teacher_latitude = ?, teacher_longitude = ?, 
                        teacher_location = ?, notes = ?, time_in = ?
                    WHERE id = ?
                ");
                $result = $stmt->execute([$status, $latitude, $longitude, $location_name, $notes, $time_in, $existing['id']]);
                return $result ? $existing['id'] : false;
            } else {
                // Insert new attendance record
                $stmt = $this->pdo->prepare("
                    INSERT INTO attendance (class_id, student_id, teacher_id, date, time_in, status, 
                                          teacher_latitude, teacher_longitude, teacher_location, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([$class_id, $student_id, $teacher_id, $date, $time_in, $status, 
                                        $latitude, $longitude, $location_name, $notes]);
                return $result ? $this->pdo->lastInsertId() : false;
            }
        } catch (Exception $e) {
            error_log("Mark Attendance Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark attendance with OTP verification
     */
    public function markAttendanceWithOTP($class_id, $student_id, $teacher_id, $otp_code, $status = 'present', $latitude = null, $longitude = null, $notes = null) {
        // Verify OTP first
        if (!$this->otpManager->verifyOTP($teacher_id, $otp_code, 'attendance')) {
            return ['success' => false, 'message' => 'Invalid or expired OTP'];
        }
        
        // Check if teacher is within school radius
        if ($latitude && $longitude) {
            $radiusCheck = $this->geoManager->isWithinSchoolRadius($teacher_id, $latitude, $longitude);
            if (!$radiusCheck['within_radius']) {
                return [
                    'success' => false, 
                    'message' => 'You are outside the allowed school radius',
                    'distance' => $radiusCheck['distance'],
                    'max_distance' => $radiusCheck['max_distance']
                ];
            }
        }
        
        $attendance_id = $this->markAttendance($class_id, $student_id, $teacher_id, $status, $latitude, $longitude, $notes);
        
        if ($attendance_id) {
            // Save teacher location
            if ($latitude && $longitude) {
                $this->geoManager->saveTeacherLocation($teacher_id, $latitude, $longitude);
            }
            
            return ['success' => true, 'attendance_id' => $attendance_id];
        } else {
            return ['success' => false, 'message' => 'Failed to mark attendance'];
        }
    }
    
    /**
     * Get attendance records for a class
     */
    public function getClassAttendance($class_id, $date = null) {
        try {
            $date = $date ?? date('Y-m-d');
            
            $stmt = $this->pdo->prepare("
                SELECT a.*, s.first_name, s.last_name, s.student_id, u.username
                FROM attendance a
                JOIN students s ON a.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE a.class_id = ? AND a.date = ?
                ORDER BY s.last_name, s.first_name
            ");
            
            $stmt->execute([$class_id, $date]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get Class Attendance Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get attendance report for a date range
     */
    public function getAttendanceReport($class_id, $start_date, $end_date) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, s.first_name, s.last_name, s.student_id, 
                       c.class_name, c.section, c.subject
                FROM attendance a
                JOIN students s ON a.student_id = s.id
                JOIN classes c ON a.class_id = c.id
                WHERE a.class_id = ? AND a.date BETWEEN ? AND ?
                ORDER BY a.date DESC, s.last_name, s.first_name
            ");
            
            $stmt->execute([$class_id, $start_date, $end_date]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get Attendance Report Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get student attendance history
     */
    public function getStudentAttendance($student_id, $start_date = null, $end_date = null) {
        try {
            $start_date = $start_date ?? date('Y-m-d', strtotime('-30 days'));
            $end_date = $end_date ?? date('Y-m-d');
            
            $stmt = $this->pdo->prepare("
                SELECT a.*, c.class_name, c.section, c.subject,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM attendance a
                JOIN classes c ON a.class_id = c.id
                JOIN teachers t ON a.teacher_id = t.id
                WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
                ORDER BY a.date DESC, a.time_in DESC
            ");
            
            $stmt->execute([$student_id, $start_date, $end_date]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get Student Attendance Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get teacher's classes
     */
    public function getTeacherClasses($teacher_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, COUNT(s.id) as student_count
                FROM classes c
                LEFT JOIN students s ON c.class_name = s.class AND c.section = s.section
                WHERE c.teacher_id = ?
                GROUP BY c.id
                ORDER BY c.class_name, c.section
            ");
            
            $stmt->execute([$teacher_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get Teacher Classes Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get students in a class
     */
    public function getClassStudents($class_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.username, u.email, u.phone
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class = c.class_name AND s.section = c.section
                WHERE c.id = ?
                ORDER BY s.last_name, s.first_name
            ");
            
            $stmt->execute([$class_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get Class Students Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get attendance statistics
     */
    public function getAttendanceStats($class_id, $start_date = null, $end_date = null) {
        try {
            $start_date = $start_date ?? date('Y-m-d', strtotime('-30 days'));
            $end_date = $end_date ?? date('Y-m-d');
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count
                FROM attendance
                WHERE class_id = ? AND date BETWEEN ? AND ?
            ");
            
            $stmt->execute([$class_id, $start_date, $end_date]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get Attendance Stats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update attendance status
     */
    public function updateAttendanceStatus($attendance_id, $status, $notes = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE attendance 
                SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            return $stmt->execute([$status, $notes, $attendance_id]);
        } catch (Exception $e) {
            error_log("Update Attendance Status Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete attendance record
     */
    public function deleteAttendance($attendance_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM attendance WHERE id = ?");
            return $stmt->execute([$attendance_id]);
        } catch (Exception $e) {
            error_log("Delete Attendance Error: " . $e->getMessage());
            return false;
        }
    }
}

// Create global attendance manager instance
$attendanceManager = new AttendanceManager($pdo, $otpManager, $geoManager);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'mark_attendance':
            if (isset($_POST['class_id']) && isset($_POST['student_id']) && isset($_POST['teacher_id'])) {
                $class_id = $_POST['class_id'];
                $student_id = $_POST['student_id'];
                $teacher_id = $_POST['teacher_id'];
                $status = $_POST['status'] ?? 'present';
                $latitude = $_POST['latitude'] ?? null;
                $longitude = $_POST['longitude'] ?? null;
                $notes = $_POST['notes'] ?? null;
                
                $result = $attendanceManager->markAttendance($class_id, $student_id, $teacher_id, $status, $latitude, $longitude, $notes);
                echo json_encode(['success' => $result !== false, 'attendance_id' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            }
            break;
            
        case 'mark_attendance_otp':
            if (isset($_POST['class_id']) && isset($_POST['student_id']) && isset($_POST['teacher_id']) && isset($_POST['otp_code'])) {
                $class_id = $_POST['class_id'];
                $student_id = $_POST['student_id'];
                $teacher_id = $_POST['teacher_id'];
                $otp_code = $_POST['otp_code'];
                $status = $_POST['status'] ?? 'present';
                $latitude = $_POST['latitude'] ?? null;
                $longitude = $_POST['longitude'] ?? null;
                $notes = $_POST['notes'] ?? null;
                
                $result = $attendanceManager->markAttendanceWithOTP($class_id, $student_id, $teacher_id, $otp_code, $status, $latitude, $longitude, $notes);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            }
            break;
            
        case 'get_class_attendance':
            if (isset($_POST['class_id'])) {
                $class_id = $_POST['class_id'];
                $date = $_POST['date'] ?? null;
                
                $result = $attendanceManager->getClassAttendance($class_id, $date);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing class_id']);
            }
            break;
            
        case 'get_teacher_classes':
            if (isset($_POST['teacher_id'])) {
                $teacher_id = $_POST['teacher_id'];
                
                $result = $attendanceManager->getTeacherClasses($teacher_id);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing teacher_id']);
            }
            break;
            
        case 'get_class_students':
            if (isset($_POST['class_id'])) {
                $class_id = $_POST['class_id'];
                
                $result = $attendanceManager->getClassStudents($class_id);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing class_id']);
            }
            break;
            
        case 'get_attendance_stats':
            if (isset($_POST['class_id'])) {
                $class_id = $_POST['class_id'];
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                
                $result = $attendanceManager->getAttendanceStats($class_id, $start_date, $end_date);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing class_id']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}
?>