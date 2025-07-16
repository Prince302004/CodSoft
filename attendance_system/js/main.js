// Main JavaScript file for attendance system
let currentLocation = null;
let watchId = null;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeGeolocation();
    initializeEventListeners();
});

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
                console.error('Error getting location:', error);
                updateLocationStatus(false);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );

        // Watch position for continuous updates
        watchId = navigator.geolocation.watchPosition(
            function(position) {
                currentLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                updateLocationStatus(true);
            },
            function(error) {
                console.error('Error watching location:', error);
                updateLocationStatus(false);
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 30000
            }
        );
    } else {
        alert('Geolocation is not supported by this browser.');
    }
}

// Update location status in UI
function updateLocationStatus(hasLocation) {
    const locationStatus = document.getElementById('location-status');
    const locationButton = document.getElementById('location-button');
    
    if (locationStatus) {
        if (hasLocation) {
            locationStatus.className = 'badge bg-success';
            locationStatus.innerHTML = '<i class="fas fa-map-marker-alt"></i> Location Detected';
        } else {
            locationStatus.className = 'badge bg-danger';
            locationStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Location Required';
        }
    }
    
    if (locationButton) {
        locationButton.disabled = !hasLocation;
    }
}

// Initialize event listeners
function initializeEventListeners() {
    // OTP input formatting
    const otpInput = document.querySelector('input[name="otp"]');
    if (otpInput) {
        otpInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Mark attendance with location verification
    const attendanceForm = document.getElementById('attendance-form');
    if (attendanceForm) {
        attendanceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            markAttendance();
        });
    }
    
    // Auto-refresh attendance table
    if (document.getElementById('attendance-table')) {
        setInterval(refreshAttendanceTable, 30000); // Refresh every 30 seconds
    }
}

// Mark attendance function
function markAttendance() {
    if (!currentLocation) {
        showAlert('Please enable location services to mark attendance.', 'danger');
        return;
    }
    
    const courseCode = document.getElementById('course-select').value;
    if (!courseCode) {
        showAlert('Please select a course.', 'warning');
        return;
    }
    
    // Show loading state
    const submitButton = document.getElementById('attendance-submit');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marking...';
    submitButton.disabled = true;
    
    // Send attendance data
    const formData = new FormData();
    formData.append('course_code', courseCode);
    formData.append('latitude', currentLocation.latitude);
    formData.append('longitude', currentLocation.longitude);
    formData.append('mark_attendance', '1');
    
    fetch('includes/mark_attendance.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            refreshAttendanceTable();
            document.getElementById('course-select').value = '';
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while marking attendance.', 'danger');
    })
    .finally(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
}

// Show alert function
function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Refresh attendance table
function refreshAttendanceTable() {
    const tableContainer = document.getElementById('attendance-table-container');
    if (!tableContainer) return;
    
    fetch('includes/get_attendance.php')
        .then(response => response.text())
        .then(html => {
            tableContainer.innerHTML = html;
        })
        .catch(error => {
            console.error('Error refreshing table:', error);
        });
}

// Toggle admin login modal
function toggleAdminLogin() {
    const modal = new bootstrap.Modal(document.getElementById('adminLoginModal'));
    modal.show();
}

// Format time function
function formatTime(timeString) {
    const time = new Date('2000-01-01 ' + timeString);
    return time.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// Format date function
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString();
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

// Verify campus location
function verifyCampusLocation() {
    if (!currentLocation) {
        return false;
    }
    
    // Campus coordinates (adjust these for your campus)
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

// Update location display
function updateLocationDisplay() {
    const locationDisplay = document.getElementById('location-display');
    if (!locationDisplay || !currentLocation) return;
    
    const isOnCampus = verifyCampusLocation();
    const statusClass = isOnCampus ? 'success' : 'danger';
    const statusText = isOnCampus ? 'On Campus' : 'Off Campus';
    
    locationDisplay.innerHTML = `
        <span class="badge bg-${statusClass}">
            <i class="fas fa-map-marker-alt"></i> ${statusText}
        </span>
        <small class="text-muted d-block mt-1">
            Lat: ${currentLocation.latitude.toFixed(6)}, 
            Lon: ${currentLocation.longitude.toFixed(6)}
        </small>
    `;
}

// Cleanup function
function cleanup() {
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
    }
}

// Cleanup on page unload
window.addEventListener('beforeunload', cleanup);

// Export functions for use in other files
window.attendanceSystem = {
    markAttendance,
    showAlert,
    refreshAttendanceTable,
    verifyCampusLocation,
    currentLocation: () => currentLocation
};