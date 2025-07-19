<?php
require_once 'includes/config.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('index.php');
}

$message = '';
$message_type = '';

// Get current campus location
$stmt = $pdo->prepare("SELECT * FROM campus_location ORDER BY id DESC LIMIT 1");
$stmt->execute();
$campus_location = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $campus_name = sanitizeInput($_POST['campus_name']);
    $latitude = (float)$_POST['latitude'];
    $longitude = (float)$_POST['longitude'];
    $radius_meters = (int)$_POST['radius_meters'];
    
    if (empty($campus_name) || empty($latitude) || empty($longitude) || empty($radius_meters)) {
        $message = "All fields are required!";
        $message_type = 'danger';
    } elseif ($latitude < -90 || $latitude > 90) {
        $message = "Latitude must be between -90 and 90 degrees!";
        $message_type = 'danger';
    } elseif ($longitude < -180 || $longitude > 180) {
        $message = "Longitude must be between -180 and 180 degrees!";
        $message_type = 'danger';
    } elseif ($radius_meters < 10 || $radius_meters > 10000) {
        $message = "Radius must be between 10 and 10,000 meters!";
        $message_type = 'danger';
    } else {
        try {
            if ($campus_location) {
                // Update existing campus location
                $stmt = $pdo->prepare("UPDATE campus_location SET campus_name = ?, latitude = ?, longitude = ?, radius_meters = ? WHERE id = ?");
                $stmt->execute([$campus_name, $latitude, $longitude, $radius_meters, $campus_location['id']]);
            } else {
                // Insert new campus location
                $stmt = $pdo->prepare("INSERT INTO campus_location (campus_name, latitude, longitude, radius_meters) VALUES (?, ?, ?, ?)");
                $stmt->execute([$campus_name, $latitude, $longitude, $radius_meters]);
            }
            
            $message = "Campus location updated successfully!";
            $message_type = 'success';
            
            // Refresh campus location data
            $stmt = $pdo->prepare("SELECT * FROM campus_location ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $campus_location = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $message = "Error updating campus location: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Location Configuration - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .campus-card { max-width: 800px; margin: 50px auto; }
        .map-container { height: 400px; background-color: #f8f9fa; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="card campus-card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4><i class="fas fa-map-marker-alt"></i> Campus Location Configuration</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i> 
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> About Campus Location:</h6>
                    <p class="mb-0">This configuration determines the geographical boundaries for attendance marking. Students must be within this radius to mark their attendance.</p>
                </div>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="campus_name" class="form-label">Campus Name</label>
                                <input type="text" class="form-control" id="campus_name" name="campus_name" 
                                       value="<?php echo htmlspecialchars($campus_location['campus_name'] ?? 'Main Campus'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="radius_meters" class="form-label">Campus Radius (meters)</label>
                                <input type="number" class="form-control" id="radius_meters" name="radius_meters" 
                                       value="<?php echo $campus_location['radius_meters'] ?? 100; ?>" min="10" max="10000" required>
                                <small class="text-muted">Recommended: 100-500 meters</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" class="form-control" id="latitude" name="latitude" step="any"
                                       value="<?php echo $campus_location['latitude'] ?? '40.7128'; ?>" required>
                                <small class="text-muted">Range: -90 to 90 degrees</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" class="form-control" id="longitude" name="longitude" step="any"
                                       value="<?php echo $campus_location['longitude'] ?? '-74.0060'; ?>" required>
                                <small class="text-muted">Range: -180 to 180 degrees</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quick Location Setup</label>
                        <div class="d-grid gap-2 d-md-block">
                            <button type="button" class="btn btn-outline-primary" onclick="getCurrentLocation()">
                                <i class="fas fa-crosshairs"></i> Use My Current Location
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="showLocationHelp()">
                                <i class="fas fa-question-circle"></i> How to Find Coordinates
                            </button>
                        </div>
                    </div>
                    
                    <div class="map-container d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <i class="fas fa-map fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Map preview will be shown here</p>
                            <small class="text-muted">Latitude: <span id="preview-lat"><?php echo $campus_location['latitude'] ?? '40.7128'; ?></span> | 
                                                     Longitude: <span id="preview-lon"><?php echo $campus_location['longitude'] ?? '-74.0060'; ?></span></small>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Campus Location
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <a href="admin/dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Location Help Modal -->
    <div class="modal fade" id="locationHelpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">How to Find Campus Coordinates</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Method 1: Using Google Maps</h6>
                    <ol>
                        <li>Go to <a href="https://maps.google.com" target="_blank">Google Maps</a></li>
                        <li>Search for your campus location</li>
                        <li>Right-click on the exact campus center point</li>
                        <li>Select "What's here?" from the context menu</li>
                        <li>The coordinates will appear at the bottom of the screen</li>
                    </ol>
                    
                    <h6>Method 2: Using GPS Device</h6>
                    <ol>
                        <li>Go to the center of your campus</li>
                        <li>Use a GPS device or smartphone GPS app</li>
                        <li>Note down the latitude and longitude coordinates</li>
                    </ol>
                    
                    <h6>Method 3: Using Online Tools</h6>
                    <ol>
                        <li>Visit <a href="https://www.latlong.net" target="_blank">latlong.net</a></li>
                        <li>Search for your campus address</li>
                        <li>Copy the coordinates provided</li>
                    </ol>
                    
                    <div class="alert alert-warning">
                        <strong>Note:</strong> Make sure to set an appropriate radius that covers your entire campus area.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Get current location
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
                        document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
                        updatePreview();
                    },
                    function(error) {
                        alert('Error getting location: ' + error.message);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }
        
        // Show location help modal
        function showLocationHelp() {
            const modal = new bootstrap.Modal(document.getElementById('locationHelpModal'));
            modal.show();
        }
        
        // Update preview
        function updatePreview() {
            const lat = document.getElementById('latitude').value;
            const lon = document.getElementById('longitude').value;
            document.getElementById('preview-lat').textContent = lat;
            document.getElementById('preview-lon').textContent = lon;
        }
        
        // Update preview when coordinates change
        document.getElementById('latitude').addEventListener('input', updatePreview);
        document.getElementById('longitude').addEventListener('input', updatePreview);
    </script>
</body>
</html>