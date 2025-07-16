# PHP Attendance Management System

A comprehensive web-based attendance management system built with PHP, HTML, CSS, JavaScript, MySQL, and Bootstrap. Features include OTP verification, geolocation tracking for teachers, and role-based access control.

## 🚀 Features

### Core Features
- **User Authentication**: Secure login system with role-based access control
- **OTP Verification**: SMS/Email OTP verification for enhanced security
- **Geolocation Tracking**: Real-time location tracking for teachers during attendance marking
- **Role-Based Dashboard**: Different interfaces for Admin, Teachers, and Students
- **Responsive Design**: Mobile-friendly interface using Bootstrap 5
- **Real-time Updates**: Auto-refresh functionality for live attendance data

### User Roles

#### Admin
- System overview and statistics
- User management (Teachers and Students)
- Class management
- View all attendance records
- System configuration

#### Teachers
- Mark attendance for their classes
- View class-wise attendance records
- Generate attendance reports
- OTP-verified attendance marking
- Location-based attendance validation

#### Students
- View personal attendance history
- Attendance statistics and percentages
- Class-wise attendance breakdown
- Real-time attendance status

### Technical Features
- **Database**: MySQL with comprehensive schema
- **Security**: CSRF protection, SQL injection prevention, password hashing
- **OTP System**: Mock SMS/Email OTP implementation
- **Geolocation**: HTML5 Geolocation API with radius validation
- **Responsive Design**: Bootstrap 5 with custom CSS
- **Modern UI**: Clean, professional interface with animations

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser with JavaScript enabled
- HTTPS recommended for geolocation features

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/attendance-system.git
cd attendance-system
```

### 2. Database Setup
1. Create a MySQL database named `attendance_system`
2. Import the database schema:
```bash
mysql -u root -p attendance_system < database.sql
```

### 3. Configuration
1. Edit `config.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_system');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

2. Update the base URL:
```php
define('BASE_URL', 'http://your-domain.com/attendance-system/');
```

3. Configure email settings for OTP (optional):
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

### 4. File Permissions
Ensure proper file permissions:
```bash
chmod 755 /path/to/attendance-system
chmod 644 /path/to/attendance-system/*.php
```

### 5. Web Server Configuration
Point your web server document root to the project directory or create a virtual host.

## 🔧 Usage

### Default Login Credentials
- **Admin**: `admin` / `password`
- **Teacher**: `teacher1` / `password`
- **Student**: `student1` / `password`

### Getting Started

1. **Access the System**
   - Open your web browser and navigate to the system URL
   - You'll be redirected to the login page

2. **Login**
   - Enter your credentials
   - Optional: Use OTP verification for enhanced security

3. **Dashboard Navigation**
   - Each role has a customized dashboard
   - Use the navigation menu to access different features

### For Teachers

1. **Taking Attendance**
   - Select a class from the dropdown
   - Choose the date (defaults to today)
   - Mark attendance for each student (Present/Absent/Late)
   - Location will be automatically captured

2. **OTP Verification**
   - For secure attendance marking, use the OTP option
   - OTP will be sent to your registered phone/email
   - Enter the 6-digit code to verify

3. **View Reports**
   - Access attendance statistics
   - Generate class-wise reports
   - View historical attendance data

### For Students

1. **View Attendance**
   - Check your attendance percentage
   - View recent attendance records
   - See class-wise attendance breakdown

2. **Attendance History**
   - Filter by date range
   - View detailed attendance records
   - Check teacher who marked attendance

### For Admins

1. **System Overview**
   - View system statistics
   - Monitor user activity
   - Check recent attendance records

2. **User Management**
   - Add/edit/delete users
   - Manage teacher and student accounts
   - Reset passwords

3. **Class Management**
   - Create and manage classes
   - Assign teachers to classes
   - Organize students by class/section

## 🗂️ Project Structure

```
attendance-system/
├── config.php              # Database and app configuration
├── index.php              # Main entry point
├── login.php               # Login page
├── dashboard.php           # Main dashboard
├── auth.php                # Authentication functions
├── otp.php                 # OTP verification system
├── attendance.php          # Attendance management
├── geolocation.php         # Geolocation functions
├── database.sql            # Database schema
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   └── js/
│       └── app.js          # JavaScript functions
└── README.md               # This file
```

## 🔐 Security Features

- **Password Hashing**: Bcrypt password hashing
- **CSRF Protection**: Token-based CSRF prevention
- **SQL Injection Prevention**: Prepared statements
- **Session Management**: Secure session handling
- **Input Validation**: Server-side input sanitization
- **Role-Based Access**: Permission-based feature access

## 🌐 Browser Compatibility

- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## 🔧 Customization

### Adding New Features
1. Create new PHP files in the root directory
2. Add database tables if needed
3. Update the navigation in `dashboard.php`
4. Add JavaScript functions in `assets/js/app.js`

### Styling
- Modify `assets/css/style.css` for custom styles
- CSS variables are defined in `:root` for easy theming
- Bootstrap classes can be overridden as needed

### Database Schema
- The system uses a relational database design
- Foreign keys maintain data integrity
- Indexes optimize query performance

## 🚨 Development Notes

### OTP Implementation
- Current implementation is for development/testing
- Replace with actual SMS/Email service for production
- OTP codes are logged to console in development mode

### Geolocation
- Requires HTTPS for production use
- Default school coordinates need to be updated
- Distance calculations use Haversine formula

### Email Configuration
- Configure SMTP settings for production
- Use app-specific passwords for Gmail
- Consider using services like SendGrid or Mailgun

## 📝 API Endpoints

### Authentication
- `POST /auth.php` - Login/logout operations
- `POST /otp.php` - OTP generation and verification

### Attendance
- `POST /attendance.php` - Attendance operations
- `POST /geolocation.php` - Location tracking

### Data Format
All API responses follow this format:
```json
{
    "success": true|false,
    "message": "Status message",
    "data": {...}
}
```

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config.php`
   - Ensure MySQL service is running
   - Verify database exists and user has permissions

2. **Geolocation Not Working**
   - Ensure HTTPS is enabled
   - Check browser permissions
   - Verify location services are enabled

3. **OTP Not Received**
   - Check email/SMS configuration
   - Verify credentials and settings
   - Check spam/junk folders

4. **Login Issues**
   - Verify user exists in database
   - Check password hash format
   - Ensure session handling is working

## 📱 Mobile Responsiveness

The system is fully responsive and works on:
- Smartphones (iOS/Android)
- Tablets
- Desktop computers
- Various screen sizes

## 🔄 Future Enhancements

- [ ] Push notifications for attendance alerts
- [ ] QR code-based attendance
- [ ] Integration with school management systems
- [ ] Advanced reporting and analytics
- [ ] Mobile app companion
- [ ] Biometric authentication
- [ ] Multi-language support
- [ ] Offline mode capability

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Email: support@yourdomain.com
- Documentation: Check the code comments and this README

## 🙏 Acknowledgments

- Bootstrap team for the responsive framework
- Font Awesome for icons
- jQuery for DOM manipulation
- MySQL for database management
- PHP community for excellent documentation

---

**Made with ❤️ for educational institutions**