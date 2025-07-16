// Attendance Management System - Main JavaScript File

class AttendanceSystem {
    constructor() {
        this.currentUser = null;
        this.currentLocation = null;
        this.init();
    }

    init() {
        // Initialize the application
        this.bindEvents();
        this.getCurrentUser();
        this.requestLocationPermission();
    }

    // Event Binding
    bindEvents() {
        // Login form
        $(document).on('submit', '#loginForm', (e) => this.handleLogin(e));
        $(document).on('submit', '#loginOTPForm', (e) => this.handleLoginOTP(e));
        
        // OTP requests
        $(document).on('click', '.send-otp-btn', (e) => this.sendOTP(e));
        $(document).on('click', '.verify-otp-btn', (e) => this.verifyOTP(e));
        
        // Attendance marking
        $(document).on('click', '.mark-attendance-btn', (e) => this.markAttendance(e));
        $(document).on('click', '.mark-attendance-otp-btn', (e) => this.markAttendanceOTP(e));
        
        // Class selection
        $(document).on('change', '#classSelect', (e) => this.loadClassStudents(e));
        $(document).on('change', '#attendanceDate', (e) => this.loadAttendanceRecords(e));
        
        // Logout
        $(document).on('click', '.logout-btn', (e) => this.logout(e));
        
        // Profile management
        $(document).on('submit', '#profileForm', (e) => this.updateProfile(e));
        $(document).on('submit', '#passwordForm', (e) => this.updatePassword(e));
        
        // Location refresh
        $(document).on('click', '.refresh-location-btn', (e) => this.refreshLocation(e));
        
        // Filter controls
        $(document).on('click', '.filter-btn', (e) => this.applyFilters(e));
        
        // Auto-refresh functionality
        setInterval(() => this.autoRefresh(), 60000); // Refresh every minute
    }

    // Authentication Functions
    async handleLogin(e) {
        e.preventDefault();
        const form = $(e.target);
        const formData = new FormData(form[0]);
        formData.append('action', 'login');

        try {
            this.showLoading(form.find('button[type="submit"]'));
            const response = await this.makeRequest('auth.php', formData);
            
            if (response.success) {
                this.showSuccess('Login successful!');
                window.location.href = 'dashboard.php';
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Login failed. Please try again.');
        } finally {
            this.hideLoading(form.find('button[type="submit"]'));
        }
    }

    async handleLoginOTP(e) {
        e.preventDefault();
        const form = $(e.target);
        const formData = new FormData(form[0]);
        formData.append('action', 'login_otp');

        try {
            this.showLoading(form.find('button[type="submit"]'));
            const response = await this.makeRequest('auth.php', formData);
            
            if (response.success) {
                this.showSuccess('Login successful!');
                window.location.href = 'dashboard.php';
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Login failed. Please try again.');
        } finally {
            this.hideLoading(form.find('button[type="submit"]'));
        }
    }

    async logout(e) {
        e.preventDefault();
        
        try {
            const response = await this.makeRequest('auth.php', { action: 'logout' });
            
            if (response.success) {
                this.showSuccess('Logged out successfully!');
                window.location.href = 'login.php';
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Logout failed. Please try again.');
        }
    }

    // OTP Functions
    async sendOTP(e) {
        e.preventDefault();
        const btn = $(e.target);
        const userId = btn.data('user-id');
        const purpose = btn.data('purpose') || 'login';

        try {
            this.showLoading(btn);
            const response = await this.makeRequest('otp.php', {
                action: 'send_otp',
                user_id: userId,
                purpose: purpose
            });
            
            if (response.success) {
                this.showSuccess('OTP sent successfully!');
                this.startOTPTimer(btn);
                
                // Show OTP code in development mode
                if (response.otp_code) {
                    console.log('OTP Code (Dev Mode):', response.otp_code);
                    this.showInfo(`OTP Code (Dev Mode): ${response.otp_code}`);
                }
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Failed to send OTP. Please try again.');
        } finally {
            this.hideLoading(btn);
        }
    }

    async verifyOTP(e) {
        e.preventDefault();
        const btn = $(e.target);
        const userId = btn.data('user-id');
        const otpCode = $('#otpCode').val();
        const purpose = btn.data('purpose') || 'login';

        if (!otpCode || otpCode.length !== 6) {
            this.showError('Please enter a valid 6-digit OTP code.');
            return;
        }

        try {
            this.showLoading(btn);
            const response = await this.makeRequest('otp.php', {
                action: 'verify_otp',
                user_id: userId,
                otp_code: otpCode,
                purpose: purpose
            });
            
            if (response.success) {
                this.showSuccess('OTP verified successfully!');
                $('#otpModal').modal('hide');
                
                // Handle different purposes
                if (purpose === 'attendance') {
                    this.proceedWithAttendance();
                } else if (purpose === 'login') {
                    window.location.href = 'dashboard.php';
                }
            } else {
                this.showError('Invalid or expired OTP code.');
            }
        } catch (error) {
            this.showError('OTP verification failed. Please try again.');
        } finally {
            this.hideLoading(btn);
        }
    }

    startOTPTimer(btn) {
        let timeLeft = 300; // 5 minutes
        const originalText = btn.text();
        
        const timer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            btn.text(`Resend OTP (${minutes}:${seconds.toString().padStart(2, '0')})`);
            btn.prop('disabled', true);
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                btn.text(originalText);
                btn.prop('disabled', false);
            }
            
            timeLeft--;
        }, 1000);
    }

    // Geolocation Functions
    async requestLocationPermission() {
        if (navigator.geolocation) {
            try {
                const position = await this.getCurrentPosition();
                this.currentLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                this.updateLocationDisplay();
            } catch (error) {
                console.warn('Geolocation permission denied:', error);
                this.showLocationError();
            }
        } else {
            this.showError('Geolocation is not supported by this browser.');
        }
    }

    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            });
        });
    }

    async refreshLocation(e) {
        e.preventDefault();
        const btn = $(e.target);
        
        try {
            this.showLoading(btn);
            await this.requestLocationPermission();
            this.showSuccess('Location updated successfully!');
        } catch (error) {
            this.showError('Failed to get location. Please try again.');
        } finally {
            this.hideLoading(btn);
        }
    }

    updateLocationDisplay() {
        if (this.currentLocation) {
            const locationInfo = `
                <div class="location-status success">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Location: ${this.currentLocation.latitude.toFixed(6)}, ${this.currentLocation.longitude.toFixed(6)}</span>
                </div>
                <div class="location-status success">
                    <i class="fas fa-crosshairs"></i>
                    <span>Accuracy: ${this.currentLocation.accuracy.toFixed(0)}m</span>
                </div>
            `;
            $('.location-info').html(locationInfo);
        }
    }

    showLocationError() {
        const errorInfo = `
            <div class="location-status danger">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Location access denied or unavailable</span>
            </div>
        `;
        $('.location-info').html(errorInfo);
    }

    // Attendance Functions
    async markAttendance(e) {
        e.preventDefault();
        const btn = $(e.target);
        const studentId = btn.data('student-id');
        const classId = $('#classSelect').val();
        const status = btn.data('status') || 'present';

        if (!classId) {
            this.showError('Please select a class first.');
            return;
        }

        try {
            this.showLoading(btn);
            const response = await this.makeRequest('attendance.php', {
                action: 'mark_attendance',
                class_id: classId,
                student_id: studentId,
                teacher_id: this.currentUser.id,
                status: status,
                latitude: this.currentLocation?.latitude,
                longitude: this.currentLocation?.longitude
            });
            
            if (response.success) {
                this.showSuccess('Attendance marked successfully!');
                this.loadAttendanceRecords();
                this.updateAttendanceStats();
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Failed to mark attendance. Please try again.');
        } finally {
            this.hideLoading(btn);
        }
    }

    async markAttendanceOTP(e) {
        e.preventDefault();
        const btn = $(e.target);
        const studentId = btn.data('student-id');
        const classId = $('#classSelect').val();
        const status = btn.data('status') || 'present';

        if (!classId) {
            this.showError('Please select a class first.');
            return;
        }

        // First, send OTP
        try {
            const otpResponse = await this.makeRequest('otp.php', {
                action: 'send_otp',
                user_id: this.currentUser.id,
                purpose: 'attendance'
            });
            
            if (otpResponse.success) {
                // Show OTP modal
                $('#otpModal').modal('show');
                
                // Store attendance data for later use
                this.pendingAttendance = {
                    class_id: classId,
                    student_id: studentId,
                    status: status
                };
                
                // Show OTP code in development mode
                if (otpResponse.otp_code) {
                    console.log('OTP Code (Dev Mode):', otpResponse.otp_code);
                    this.showInfo(`OTP Code (Dev Mode): ${otpResponse.otp_code}`);
                }
            } else {
                this.showError(otpResponse.message);
            }
        } catch (error) {
            this.showError('Failed to send OTP. Please try again.');
        }
    }

    async proceedWithAttendance() {
        if (!this.pendingAttendance) return;

        const otpCode = $('#otpCode').val();
        
        try {
            const response = await this.makeRequest('attendance.php', {
                action: 'mark_attendance_otp',
                ...this.pendingAttendance,
                teacher_id: this.currentUser.id,
                otp_code: otpCode,
                latitude: this.currentLocation?.latitude,
                longitude: this.currentLocation?.longitude
            });
            
            if (response.success) {
                this.showSuccess('Attendance marked successfully with OTP verification!');
                this.loadAttendanceRecords();
                this.updateAttendanceStats();
                this.pendingAttendance = null;
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Failed to mark attendance. Please try again.');
        }
    }

    async loadClassStudents(e) {
        const classId = $(e.target).val();
        
        if (!classId) {
            $('#studentsContainer').empty();
            return;
        }

        try {
            const response = await this.makeRequest('attendance.php', {
                action: 'get_class_students',
                class_id: classId
            });
            
            if (response.success) {
                this.renderStudentsList(response.data);
                this.loadAttendanceRecords();
                this.updateAttendanceStats();
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Failed to load students. Please try again.');
        }
    }

    async loadAttendanceRecords() {
        const classId = $('#classSelect').val();
        const date = $('#attendanceDate').val() || new Date().toISOString().split('T')[0];
        
        if (!classId) return;

        try {
            const response = await this.makeRequest('attendance.php', {
                action: 'get_class_attendance',
                class_id: classId,
                date: date
            });
            
            if (response.success) {
                this.renderAttendanceRecords(response.data);
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Failed to load attendance records. Please try again.');
        }
    }

    async updateAttendanceStats() {
        const classId = $('#classSelect').val();
        
        if (!classId) return;

        try {
            const response = await this.makeRequest('attendance.php', {
                action: 'get_attendance_stats',
                class_id: classId
            });
            
            if (response.success) {
                this.renderAttendanceStats(response.data);
            }
        } catch (error) {
            console.error('Failed to load attendance stats:', error);
        }
    }

    // Render Functions
    renderStudentsList(students) {
        const container = $('#studentsContainer');
        container.empty();
        
        if (students.length === 0) {
            container.html('<p class="text-center">No students found in this class.</p>');
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-striped table-hover">';
        html += '<thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Actions</th></tr></thead><tbody>';
        
        students.forEach(student => {
            html += `
                <tr>
                    <td>${student.student_id}</td>
                    <td>${student.first_name} ${student.last_name}</td>
                    <td>${student.email}</td>
                    <td>
                        <button class="btn btn-success btn-sm mark-attendance-btn" data-student-id="${student.id}" data-status="present">
                            <i class="fas fa-check"></i> Present
                        </button>
                        <button class="btn btn-danger btn-sm mark-attendance-btn" data-student-id="${student.id}" data-status="absent">
                            <i class="fas fa-times"></i> Absent
                        </button>
                        <button class="btn btn-warning btn-sm mark-attendance-btn" data-student-id="${student.id}" data-status="late">
                            <i class="fas fa-clock"></i> Late
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        container.html(html);
    }

    renderAttendanceRecords(records) {
        const container = $('#attendanceRecordsContainer');
        container.empty();
        
        if (records.length === 0) {
            container.html('<p class="text-center">No attendance records found for this date.</p>');
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-striped table-hover">';
        html += '<thead><tr><th>Student ID</th><th>Name</th><th>Status</th><th>Time</th><th>Location</th></tr></thead><tbody>';
        
        records.forEach(record => {
            const statusClass = this.getStatusClass(record.status);
            const statusText = record.status.charAt(0).toUpperCase() + record.status.slice(1);
            
            html += `
                <tr>
                    <td>${record.student_id}</td>
                    <td>${record.first_name} ${record.last_name}</td>
                    <td><span class="badge badge-${statusClass}">${statusText}</span></td>
                    <td>${record.time_in || 'N/A'}</td>
                    <td>${record.teacher_location || 'N/A'}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        container.html(html);
    }

    renderAttendanceStats(stats) {
        const container = $('#attendanceStatsContainer');
        
        const html = `
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card present">
                        <h3>${stats.present_count || 0}</h3>
                        <p>Present</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card absent">
                        <h3>${stats.absent_count || 0}</h3>
                        <p>Absent</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card late">
                        <h3>${stats.late_count || 0}</h3>
                        <p>Late</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card total">
                        <h3>${stats.total_records || 0}</h3>
                        <p>Total</p>
                    </div>
                </div>
            </div>
        `;
        
        container.html(html);
    }

    // Utility Functions
    getStatusClass(status) {
        switch (status) {
            case 'present': return 'success';
            case 'absent': return 'danger';
            case 'late': return 'warning';
            case 'excused': return 'info';
            default: return 'secondary';
        }
    }

    async makeRequest(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            body: data instanceof FormData ? data : new URLSearchParams(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }

    showLoading(element) {
        element.prop('disabled', true);
        const originalText = element.text();
        element.data('original-text', originalText);
        element.html('<span class="spinner"></span> Loading...');
    }

    hideLoading(element) {
        element.prop('disabled', false);
        element.text(element.data('original-text'));
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'danger');
    }

    showInfo(message) {
        this.showNotification(message, 'info');
    }

    showNotification(message, type) {
        const alertClass = `alert-${type}`;
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert
        $('body').prepend(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }

    getCurrentUser() {
        // This would typically be set by the server
        // For now, we'll assume it's available globally
        if (typeof currentUser !== 'undefined') {
            this.currentUser = currentUser;
        }
    }

    autoRefresh() {
        // Auto-refresh functionality for real-time updates
        if ($('#classSelect').val()) {
            this.loadAttendanceRecords();
            this.updateAttendanceStats();
        }
    }
}

// Initialize the application when DOM is ready
$(document).ready(function() {
    window.attendanceSystem = new AttendanceSystem();
});

// Export for use in other scripts
window.AttendanceSystem = AttendanceSystem;