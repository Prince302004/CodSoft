# XAMPP Setup Guide for College Attendance Management System

This guide will help you set up the College Attendance Management System on XAMPP (Windows/Mac/Linux).

## Prerequisites

- XAMPP installed on your computer
- A valid email account for OTP delivery
- Modern web browser

## Step 1: Download and Install XAMPP

### Windows
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Run the installer as Administrator
3. Select components: Apache, MySQL, PHP, phpMyAdmin
4. Install to default location: `C:\xampp`

### Mac
1. Download XAMPP for Mac
2. Run the installer
3. Install to `/Applications/XAMPP`

### Linux
1. Download XAMPP for Linux
2. Make installer executable: `chmod +x xampp-linux-installer.run`
3. Run installer: `sudo ./xampp-linux-installer.run`

## Step 2: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Verify services are running (green indicators)

**Default URLs:**
- Apache: `http://localhost` or `http://localhost:80`
- phpMyAdmin: `http://localhost/phpmyadmin`
- MySQL: `localhost:3306`

## Step 3: Place Project Files

### Method 1: Direct Placement
1. Copy the `attendance_system` folder to:
   - **Windows**: `C:\xampp\htdocs\attendance_system`
   - **Mac**: `/Applications/XAMPP/htdocs/attendance_system`
   - **Linux**: `/opt/lampp/htdocs/attendance_system`

### Method 2: Create Symlink (Advanced)
```bash
# Windows (Run as Administrator)
mklink /D "C:\xampp\htdocs\attendance_system" "C:\path\to\your\project\attendance_system"

# Mac/Linux
ln -s /path/to/your/project/attendance_system /Applications/XAMPP/htdocs/attendance_system
```

## Step 4: Database Setup

### 4.1 Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" in the left sidebar
3. Create database named: `attendance_db`
4. Set collation: `utf8mb4_general_ci`

### 4.2 Import Database Schema
1. Select `attendance_db` database
2. Click "Import" tab
3. Choose file: `attendance_system/database.sql`
4. Click "Go" to import

### 4.3 Verify Database
Check that these tables were created:
- `admin`
- `students`
- `courses`
- `attendance`
- `otp_verification`
- `campus_location`

## Step 5: Configure Database Connection

Edit `attendance_system/includes/config.php`:

```php
<?php
session_start();
date_default_timezone_set('America/New_York'); // Change to your timezone

// Database configuration for XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Default XAMPP MySQL user
define('DB_PASS', '');               // Default XAMPP MySQL password (empty)
define('DB_NAME', 'attendance_db');

// Campus location settings (update with your coordinates)
define('CAMPUS_LATITUDE', 40.7128);
define('CAMPUS_LONGITUDE', -74.0060);
define('CAMPUS_RADIUS', 100); // meters

// OTP settings
define('OTP_EXPIRY_MINUTES', 5);
define('OTP_LENGTH', 6);

// Email configuration (see Email Setup section below)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
define('FROM_EMAIL', 'your_email@gmail.com');
define('FROM_NAME', 'College Attendance System');

// Rest of the config remains the same...
```

## Step 6: Email Configuration for XAMPP

### Option 1: Gmail SMTP (Recommended)

1. **Enable 2-Factor Authentication** on your Google account
2. **Generate App Password**:
   - Go to Google Account Settings
   - Security > 2-Step Verification
   - App passwords > Generate new password
   - Use this password in config

3. **Update config.php**:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_16_digit_app_password');
define('FROM_EMAIL', 'your_email@gmail.com');
define('FROM_NAME', 'College Attendance System');
```

### Option 2: Local Mail Server (Testing Only)

For testing purposes, you can use XAMPP's built-in mail server:

1. **Configure php.ini**:
   - Open `C:\xampp\php\php.ini`
   - Find and update:
```ini
[mail function]
SMTP = localhost
smtp_port = 25
sendmail_from = your_email@localhost
```

2. **Install fake sendmail** (Windows):
   - Download fake sendmail
   - Configure in php.ini:
```ini
sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"
```

## Step 7: Test the Installation

### 7.1 Access the System
1. Open browser and go to: `http://localhost/attendance_system`
2. You should see the login page

### 7.2 Test Email Configuration
1. Visit: `http://localhost/attendance_system/test_email.php`
2. Update the test email address in the script
3. Check if email is sent successfully

### 7.3 Test Login
**Default Credentials:**
- **Admin**: username: `admin`, password: `password`
- **Student**: ID: `STU001`, password: `password`

## Step 8: Common XAMPP Issues and Solutions

### Issue 1: Apache Won't Start
**Cause**: Port 80 is already in use
**Solution**:
1. Open XAMPP Control Panel
2. Click "Config" next to Apache
3. Select "httpd.conf"
4. Change `Listen 80` to `Listen 8080`
5. Save and restart Apache
6. Access via: `http://localhost:8080/attendance_system`

### Issue 2: MySQL Won't Start
**Cause**: Port 3306 is already in use
**Solution**:
1. Open XAMPP Control Panel
2. Click "Config" next to MySQL
3. Select "my.ini"
4. Change `port = 3306` to `port = 3307`
5. Update database config in `config.php`

### Issue 3: Email Not Sending
**Solutions**:
1. Check Gmail app password is correct
2. Verify internet connection
3. Check firewall settings
4. Enable OpenSSL in php.ini:
```ini
extension=openssl
```

### Issue 4: Database Connection Error
**Solutions**:
1. Verify MySQL service is running
2. Check database credentials in config.php
3. Ensure database exists
4. Check MySQL error logs

### Issue 5: File Permission Issues (Mac/Linux)
**Solution**:
```bash
# Give proper permissions
chmod -R 755 /Applications/XAMPP/htdocs/attendance_system
chown -R your_username:your_group /Applications/XAMPP/htdocs/attendance_system
```

## Step 9: Production Considerations

### Security Settings
1. **Change default passwords**:
   - MySQL root password
   - Admin account password
   - Default student passwords

2. **Update php.ini settings**:
```ini
display_errors = Off
log_errors = On
error_log = C:\xampp\php\logs\php_error_log
```

3. **Enable HTTPS** (Optional):
   - Enable SSL module in Apache
   - Configure SSL certificates

### Performance Optimization
1. **Enable OPcache**:
```ini
zend_extension = opcache
opcache.enable = 1
opcache.memory_consumption = 128
```

2. **Optimize MySQL**:
   - Adjust `my.ini` settings
   - Enable query caching

## Step 10: Backup and Maintenance

### Database Backup
```bash
# Create backup
mysqldump -u root -p attendance_db > backup.sql

# Restore backup
mysql -u root -p attendance_db < backup.sql
```

### File Backup
- Regularly backup the entire project folder
- Use version control (Git) for code changes

## Troubleshooting Commands

### Check Services
```bash
# Windows
netstat -an | findstr :80
netstat -an | findstr :3306

# Mac/Linux
netstat -an | grep :80
netstat -an | grep :3306
```

### View Logs
- **Apache Error Log**: `C:\xampp\apache\logs\error.log`
- **MySQL Error Log**: `C:\xampp\mysql\data\mysql_error.log`
- **PHP Error Log**: `C:\xampp\php\logs\php_error_log`

## Development Workflow

1. **Start XAMPP services** (Apache + MySQL)
2. **Access project**: `http://localhost/attendance_system`
3. **Make changes** to PHP files
4. **Test changes** in browser
5. **Check logs** for errors
6. **Backup regularly**

## Additional Tools

### Recommended XAMPP Add-ons
- **phpMyAdmin**: Database management
- **Composer**: PHP dependency management
- **Git**: Version control
- **VS Code**: Code editor with PHP extensions

### Useful phpMyAdmin Features
- SQL query execution
- Database export/import
- User management
- Performance monitoring

## Support Resources

- **XAMPP Official Documentation**: [https://www.apachefriends.org/docs/](https://www.apachefriends.org/docs/)
- **PHP Documentation**: [https://www.php.net/docs.php](https://www.php.net/docs.php)
- **MySQL Documentation**: [https://dev.mysql.com/doc/](https://dev.mysql.com/doc/)

---

## Quick Start Checklist

- [ ] XAMPP installed and services running
- [ ] Project files in htdocs folder
- [ ] Database created and imported
- [ ] Database connection configured
- [ ] Email settings configured
- [ ] System accessible via browser
- [ ] Test email functionality working
- [ ] Default login credentials tested
- [ ] Error logs checked

Once all items are checked, your attendance system should be fully functional on XAMPP!