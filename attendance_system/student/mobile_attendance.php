<?php
require_once '../includes/config.php';

// Check if student is logged in
if (!isStudent()) {
    redirect('../index.php');
}

// Get student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get student's enrolled subjects
$stmt = $pdo->prepare("SELECT ss.subject_code, s.subject_name, s.credits, t.first_name as teacher_first_name, t.last_name as teacher_last_name 
                       FROM student_subjects ss 
                       JOIN subjects s ON ss.subject_code = s.subject_code 
                       JOIN teachers t ON s.teacher_id = t.teacher_id 
                       WHERE ss.student_id = ? AND ss.status = 'enrolled' 
                       ORDER BY s.subject_name");
$stmt->execute([$_SESSION['student_id']]);
$enrolled_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's attendance
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT a.*, s.subject_name FROM attendance a 
                       JOIN subjects s ON a.subject_code = s.subject_code 
                       WHERE a.student_id = ? AND a.attendance_date = ? 
                       ORDER BY a.attendance_time DESC");
$stmt->execute([$_SESSION['student_id'], $today]);
$todayAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get attendance statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_attendance FROM attendance WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$totalAttendance = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as this_month FROM attendance WHERE student_id = ? AND DATE_FORMAT(attendance_date, '%Y-%m') = ?");
$stmt->execute([$_SESSION['student_id'], date('Y-m')]);
$thisMonthAttendance = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as this_week FROM attendance WHERE student_id = ? AND WEEK(attendance_date) = WEEK(NOW())");
$stmt->execute([$_SESSION['student_id']]);
$thisWeekAttendance = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Attendance - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .mobile-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .location-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .subject-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .subject-card:hover {
            transform: translateY(-5px);
        }
        .attendance-btn {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stats-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: none;
        }
        .swipe-area {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            margin: 20px 0;
        }
        .swipe-text {
            color: white;
            font-size: 18px;
            text-align: center;
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .success-animation {
            animation: successPulse 0.5s ease-in-out;
        }
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- Location Status Indicator -->
    <div id="location-indicator" class="location-indicator bg-warning">
        <i class="fas fa-map-marker-alt text-white"></i>
    </div>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="text-white mb-1">
                            <i class="fas fa-mobile-alt"></i> Mobile Attendance
                        </h4>
                        <p class="text-white-50 mb-0">Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</p>
                    </div>
                    <div class="text-end">
                        <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-desktop"></i> Desktop
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-4">
                <div class="stats-card text-center p-3">
                    <div class="h3 text-primary mb-1"><?php echo $totalAttendance; ?></div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
            <div class="col-4">
                <div class="stats-card text-center p-3">
                    <div class="h3 text-success mb-1"><?php echo $thisWeekAttendance; ?></div>
                    <small class="text-muted">This Week</small>
                </div>
            </div>
            <div class="col-4">
                <div class="stats-card text-center p-3">
                    <div class="h3 text-info mb-1"><?php echo $thisMonthAttendance; ?></div>
                    <small class="text-muted">This Month</small>
                </div>
            </div>
        </div>

        <!-- Location Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="mobile-card bg-white p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <i class="fas fa-crosshairs"></i> Location Status
                            </h6>
                            <div id="location-status" class="text-muted">
                                Detecting your location...
                            </div>
                        </div>
                        <div id="location-icon" class="text-warning">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Attendance -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="mobile-card bg-white p-4">
                    <h6 class="mb-3">
                        <i class="fas fa-bolt"></i> Quick Attendance
                    </h6>
                    
                    <?php if (empty($enrolled_subjects)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No subjects enrolled yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($enrolled_subjects as $subject): ?>
                                <?php 
                                $marked_today = false;
                                foreach ($todayAttendance as $attendance) {
                                    if ($attendance['subject_code'] === $subject['subject_code']) {
                                        $marked_today = true;
                                        break;
                                    }
                                }
                                ?>
                                <div class="col-6 mb-3">
                                    <div class="subject-card bg-light p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($subject['teacher_first_name'] . ' ' . $subject['teacher_last_name']); ?>
                                                </small>
                                            </div>
                                            <?php if ($marked_today): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!$marked_today): ?>
                                            <button class="btn btn-primary attendance-btn w-100" 
                                                    onclick="markAttendance('<?php echo $subject['subject_code']; ?>', '<?php echo htmlspecialchars($subject['subject_name']); ?>')"
                                                    data-subject="<?php echo $subject['subject_code']; ?>">
                                                <i class="fas fa-clipboard-check"></i> Mark
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success attendance-btn w-100" disabled>
                                                <i class="fas fa-check-circle"></i> Marked
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="mobile-card bg-white p-4">
                    <h6 class="mb-3">
                        <i class="fas fa-calendar-day"></i> Today's Attendance
                    </h6>
                    
                    <?php if (empty($todayAttendance)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No attendance marked for today</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($todayAttendance as $attendance): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($attendance['subject_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('h:i A', strtotime($attendance['attendance_time'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?php echo $attendance['status'] === 'late' ? 'warning' : 'success'; ?>">
                                                <?php echo ucfirst($attendance['status']); ?>
                                            </span>
                                            <?php if ($attendance['location_verified']): ?>
                                                <i class="fas fa-map-marker-alt text-success ms-1"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="row">
            <div class="col-12">
                <div class="d-grid gap-2">
                    <a href="analytics.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-chart-line"></i> View Analytics
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="success-animation mb-3">
                        <i class="fas fa-check-circle text-success fa-4x"></i>
                    </div>
                    <h5 class="mb-2">Attendance Marked Successfully!</h5>
                    <p class="text-muted mb-3" id="success-message"></p>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentLocation = null;
        let locationWatchId = null;

        // Initialize geolocation
        function initializeGeolocation() {
            if (navigator.geolocation) {
                // Get current position
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        currentLocation = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        };
                        updateLocationStatus(true);
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        updateLocationStatus(false, error.message);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );

                // Watch for location changes
                locationWatchId = navigator.geolocation.watchPosition(
                    function(position) {
                        currentLocation = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        };
                        updateLocationStatus(true);
                    },
                    function(error) {
                        console.error('Location watch error:', error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 30000
                    }
                );
            } else {
                updateLocationStatus(false, 'Geolocation not supported');
            }
        }

        // Update location status
        function updateLocationStatus(hasLocation, errorMessage = '') {
            const locationStatus = document.getElementById('location-status');
            const locationIcon = document.getElementById('location-icon');
            const locationIndicator = document.getElementById('location-indicator');

            if (hasLocation && currentLocation) {
                const isOnCampus = verifyCampusLocation();
                const statusClass = isOnCampus ? 'success' : 'danger';
                const statusText = isOnCampus ? 'On Campus' : 'Off Campus';
                
                locationStatus.innerHTML = `
                    <strong class="text-${statusClass}">${statusText}</strong><br>
                    <small>Lat: ${currentLocation.latitude.toFixed(6)}, Lon: ${currentLocation.longitude.toFixed(6)}</small>
                `;
                
                locationIcon.innerHTML = `<i class="fas fa-map-marker-alt fa-2x text-${statusClass}"></i>`;
                locationIndicator.className = `location-indicator bg-${statusClass}`;
                
                if (isOnCampus) {
                    locationIndicator.classList.add('pulse');
                } else {
                    locationIndicator.classList.remove('pulse');
                }
            } else {
                locationStatus.innerHTML = `
                    <strong class="text-danger">Location Required</strong><br>
                    <small>${errorMessage || 'Please enable location services'}</small>
                `;
                locationIcon.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x text-danger"></i>';
                locationIndicator.className = 'location-indicator bg-danger';
            }
        }

        // Verify campus location
        function verifyCampusLocation() {
            if (!currentLocation) return false;
            
            // Campus coordinates (update these for your campus)
            const campusLat = 40.7128;
            const campusLon = -74.0060;
            const campusRadius = 100; // meters
            
            const distance = calculateDistance(
                currentLocation.latitude,
                currentLocation.longitude,
                campusLat,
                campusLon
            );
            
            return distance <= campusRadius;
        }

        // Calculate distance between two points
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth's radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        // Mark attendance
        function markAttendance(subjectCode, subjectName) {
            if (!currentLocation) {
                alert('Please enable location services to mark attendance.');
                return;
            }

            const button = document.querySelector(`[data-subject="${subjectCode}"]`);
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marking...';
            button.disabled = true;

            const formData = new FormData();
            formData.append('subject_code', subjectCode);
            formData.append('latitude', currentLocation.latitude);
            formData.append('longitude', currentLocation.longitude);
            formData.append('mark_attendance', '1');

            fetch('mark_attendance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('success-message').textContent = 
                        `Attendance marked for ${subjectName} at ${data.time}`;
                    
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    
                    // Update button
                    button.innerHTML = '<i class="fas fa-check-circle"></i> Marked';
                    button.className = 'btn btn-success attendance-btn w-100';
                    button.disabled = true;
                    
                    // Refresh page after modal closes
                    document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                        location.reload();
                    });
                } else {
                    alert(data.message);
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking attendance.');
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeGeolocation();
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (locationWatchId) {
                navigator.geolocation.clearWatch(locationWatchId);
            }
        });
    </script>
</body>
</html>