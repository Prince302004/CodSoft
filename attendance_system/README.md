# College Attendance Management System

A modern, web-based attendance management system for colleges and universities with OTP verification and geolocation features.

## Features

### 🔐 Authentication & Security
- **OTP Verification**: SMS-based OTP verification for secure login
- **Geolocation Verification**: Students can only mark attendance when on campus
- **Session Management**: Secure session handling with proper logout
- **Role-based Access**: Separate interfaces for students and administrators

### 📱 Mobile-First Design
- **Responsive UI**: Works seamlessly on mobile devices and desktops
- **Bootstrap 5**: Modern, clean interface with Bootstrap components
- **Progressive Web App Ready**: Designed for mobile usage by students

### 🎯 Core Functionality
- **Real-time Attendance**: Mark attendance with location verification
- **Course Management**: Support for multiple courses and schedules
- **Attendance Tracking**: Comprehensive attendance history and statistics
- **Late Arrival Detection**: Automatic detection of late arrivals
- **Admin Dashboard**: Complete administrative control panel

### 📊 Analytics & Reports
- **Attendance Statistics**: Individual and course-wide attendance stats
- **Low Attendance Alerts**: Identify students with poor attendance
- **Real-time Updates**: Live dashboard updates with attendance data
- **Historical Data**: Complete attendance history with filtering

## Technology Stack

- **Backend**: PHP 7.4+ with PDO MySQL
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.1.3
- **Database**: MySQL 8.0+
- **Icons**: Font Awesome 6.0
- **Geolocation**: HTML5 Geolocation API

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Modern web browser with geolocation support

### Step 1: Clone/Download
```bash
git clone <repository-url>
cd attendance_system
```

### Step 2: Database Setup
1. Create a MySQL database named `attendance_db`
2. Import the database schema:
```bash
mysql -u username -p attendance_db < database.sql
```

### Step 3: Configuration
1. Edit `includes/config.php` and update database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'attendance_db');
```

2. Update campus location coordinates in `includes/config.php`:
```php
define('CAMPUS_LATITUDE', 40.7128);  // Your campus latitude
define('CAMPUS_LONGITUDE', -74.0060); // Your campus longitude
define('CAMPUS_RADIUS', 100);         // Radius in meters
```

### Step 4: Web Server Setup
1. Place files in your web server document root
2. Ensure proper permissions for PHP files
3. Enable PHP extensions: PDO, PDO_MySQL

### Step 5: Testing
1. Access `index.php` in your browser
2. Use default credentials:
   - **Admin**: username: `admin`, password: `password`
   - **Student**: ID: `STU001`, password: `password`

## Usage Guide

### For Students

#### Login Process
1. Enter your Student ID and password
2. You'll receive an OTP on your registered phone
3. Enter the OTP to complete login
4. Enable location services when prompted

#### Marking Attendance
1. Navigate to the student dashboard
2. Select the course from the dropdown
3. Ensure you're on campus (location indicator should be green)
4. Click "Mark Attendance"
5. Attendance will be recorded with timestamp and location

#### Viewing Attendance
- View today's attendance on the dashboard
- Check attendance history in the "Recent Attendance" section
- See statistics including total, weekly, and monthly attendance

### For Administrators

#### Admin Dashboard
- Overview of total students, courses, and attendance
- Recent attendance records with student details
- Course-wise attendance statistics
- Low attendance alerts

#### Managing the System
- Access student management (future feature)
- Course management (future feature)
- Generate reports (future feature)
- Monitor system usage

## Security Features

### Location Verification
- Uses HTML5 Geolocation API for precise location detection
- Validates student location against campus coordinates
- Configurable radius for campus boundaries
- Prevents attendance marking from off-campus locations

### OTP Security
- 6-digit OTP with 5-minute expiration
- One-time use tokens
- Secure OTP storage in database
- SMS integration ready (currently simulated)

### Session Management
- Secure session handling with proper timeout
- Role-based access control
- Protection against session hijacking
- Automatic logout on browser close

## Customization

### Campus Location
Update the campus coordinates in `includes/config.php`:
```php
define('CAMPUS_LATITUDE', YOUR_LATITUDE);
define('CAMPUS_LONGITUDE', YOUR_LONGITUDE);
define('CAMPUS_RADIUS', RADIUS_IN_METERS);
```

### OTP Settings
Modify OTP configuration in `includes/config.php`:
```php
define('OTP_EXPIRY_MINUTES', 5);
define('OTP_LENGTH', 6);
```

### SMS Integration
Replace the simulated SMS function in `includes/config.php` with actual SMS service:
```php
function sendOTP($phone, $otp) {
    // Integrate with Twilio, Nexmo, or other SMS service
    // Example: Send actual SMS here
    return true;
}
```

## File Structure

```
attendance_system/
├── admin/                  # Admin interface
│   ├── dashboard.php      # Admin dashboard
│   └── logout.php         # Admin logout
├── student/               # Student interface
│   ├── dashboard.php      # Student dashboard
│   └── logout.php         # Student logout
├── includes/              # Core PHP files
│   ├── config.php         # Database config and functions
│   ├── mark_attendance.php # Attendance marking logic
│   └── get_recent_attendance.php # Attendance data retrieval
├── css/                   # Stylesheets
│   └── style.css          # Custom CSS
├── js/                    # JavaScript files
│   └── main.js            # Main JS functionality
├── assets/                # Static assets
│   └── images/            # Image files
├── database.sql           # Database schema
├── index.php              # Main login page
└── README.md              # This file
```

## Database Schema

### Tables
- **students**: Student information and credentials
- **admin**: Administrator accounts
- **courses**: Course details and schedules
- **attendance**: Attendance records with location data
- **otp_verification**: OTP tokens and verification status
- **campus_location**: Campus location configuration

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers with geolocation support

## Security Considerations

1. **HTTPS Required**: Always use HTTPS in production for secure data transmission
2. **Input Validation**: All user inputs are sanitized and validated
3. **SQL Injection Prevention**: Uses prepared statements throughout
4. **XSS Protection**: HTML encoding for all user-generated content
5. **Session Security**: Secure session configuration and management

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## Changelog

### Version 1.0.0
- Initial release with core attendance functionality
- OTP verification system
- Geolocation verification
- Student and admin dashboards
- Mobile-responsive design

## Future Enhancements

- [ ] Student management interface
- [ ] Course scheduling system
- [ ] Attendance reports and analytics
- [ ] Email notifications
- [ ] Mobile app development
- [ ] Biometric authentication
- [ ] Integration with student information systems
- [ ] Automated attendance notifications
- [ ] Advanced analytics and insights

---

**Note**: This system is designed for educational purposes and should be thoroughly tested before production use. Ensure compliance with your institution's data protection and privacy policies.