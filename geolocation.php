<?php
require_once 'config.php';

class GeolocationManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Save teacher's current location
     */
    public function saveTeacherLocation($teacher_id, $latitude, $longitude, $location_name = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO teacher_locations (teacher_id, latitude, longitude, location_name) 
                VALUES (?, ?, ?, ?)
            ");
            
            return $stmt->execute([$teacher_id, $latitude, $longitude, $location_name]);
        } catch (Exception $e) {
            error_log("Save Location Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get teacher's location history
     */
    public function getTeacherLocationHistory($teacher_id, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM teacher_locations 
                WHERE teacher_id = ? 
                ORDER BY recorded_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$teacher_id, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get Location History Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get teacher's current location
     */
    public function getTeacherCurrentLocation($teacher_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM teacher_locations 
                WHERE teacher_id = ? 
                ORDER BY recorded_at DESC 
                LIMIT 1
            ");
            
            $stmt->execute([$teacher_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get Current Location Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);
        
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;
        
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) + 
             cos($lat1Rad) * cos($lat2Rad) * 
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c; // Distance in kilometers
    }
    
    /**
     * Check if teacher is within allowed radius of school
     */
    public function isWithinSchoolRadius($teacher_id, $latitude, $longitude, $school_lat = null, $school_lon = null, $max_distance = 1) {
        // Default school coordinates (replace with actual school location)
        if ($school_lat === null) $school_lat = 40.7128; // Example: New York
        if ($school_lon === null) $school_lon = -74.0060;
        
        $distance = $this->calculateDistance($latitude, $longitude, $school_lat, $school_lon);
        
        return [
            'within_radius' => $distance <= $max_distance,
            'distance' => $distance,
            'max_distance' => $max_distance
        ];
    }
    
    /**
     * Get location name from coordinates (Mock implementation)
     */
    public function getLocationName($latitude, $longitude) {
        // Mock implementation - in production, use Google Maps API or similar
        return "Location: {$latitude}, {$longitude}";
    }
    
    /**
     * Validate coordinates
     */
    public function validateCoordinates($latitude, $longitude) {
        return is_numeric($latitude) && is_numeric($longitude) &&
               $latitude >= -90 && $latitude <= 90 &&
               $longitude >= -180 && $longitude <= 180;
    }
    
    /**
     * Get attendance locations for a specific date range
     */
    public function getAttendanceLocations($teacher_id, $start_date, $end_date) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, t.first_name, t.last_name, c.class_name, c.section
                FROM attendance a
                JOIN teachers t ON a.teacher_id = t.id
                JOIN classes c ON a.class_id = c.id
                WHERE a.teacher_id = ? AND a.date BETWEEN ? AND ?
                AND a.teacher_latitude IS NOT NULL AND a.teacher_longitude IS NOT NULL
                ORDER BY a.date DESC, a.time_in DESC
            ");
            
            $stmt->execute([$teacher_id, $start_date, $end_date]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get Attendance Locations Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update attendance with location data
     */
    public function updateAttendanceLocation($attendance_id, $latitude, $longitude, $location_name = null) {
        try {
            if ($location_name === null) {
                $location_name = $this->getLocationName($latitude, $longitude);
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE attendance 
                SET teacher_latitude = ?, teacher_longitude = ?, teacher_location = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([$latitude, $longitude, $location_name, $attendance_id]);
        } catch (Exception $e) {
            error_log("Update Attendance Location Error: " . $e->getMessage());
            return false;
        }
    }
}

// Create global geolocation manager instance
$geoManager = new GeolocationManager($pdo);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'save_location':
            if (isset($_POST['teacher_id']) && isset($_POST['latitude']) && isset($_POST['longitude'])) {
                $teacher_id = $_POST['teacher_id'];
                $latitude = $_POST['latitude'];
                $longitude = $_POST['longitude'];
                $location_name = $_POST['location_name'] ?? null;
                
                if ($geoManager->validateCoordinates($latitude, $longitude)) {
                    $result = $geoManager->saveTeacherLocation($teacher_id, $latitude, $longitude, $location_name);
                    echo json_encode(['success' => $result]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            }
            break;
            
        case 'check_radius':
            if (isset($_POST['teacher_id']) && isset($_POST['latitude']) && isset($_POST['longitude'])) {
                $teacher_id = $_POST['teacher_id'];
                $latitude = $_POST['latitude'];
                $longitude = $_POST['longitude'];
                
                if ($geoManager->validateCoordinates($latitude, $longitude)) {
                    $result = $geoManager->isWithinSchoolRadius($teacher_id, $latitude, $longitude);
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            }
            break;
            
        case 'get_location_history':
            if (isset($_POST['teacher_id'])) {
                $teacher_id = $_POST['teacher_id'];
                $limit = $_POST['limit'] ?? 50;
                
                $result = $geoManager->getTeacherLocationHistory($teacher_id, $limit);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing teacher_id']);
            }
            break;
            
        case 'get_current_location':
            if (isset($_POST['teacher_id'])) {
                $teacher_id = $_POST['teacher_id'];
                
                $result = $geoManager->getTeacherCurrentLocation($teacher_id);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing teacher_id']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}
?>