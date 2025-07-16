# Quick Start Guide - XAMPP Setup

Get the College Attendance Management System running in 5 minutes!

## 🚀 Super Quick Setup

### Step 1: Install XAMPP
- Download from [https://www.apachefriends.org/](https://www.apachefriends.org/)
- Install with default settings
- Start Apache and MySQL services

### Step 2: Copy Files
Copy the `attendance_system` folder to:
- **Windows**: `C:\xampp\htdocs\attendance_system`
- **Mac**: `/Applications/XAMPP/htdocs/attendance_system`
- **Linux**: `/opt/lampp/htdocs/attendance_system`

### Step 3: Run Auto-Setup
- **Windows**: Double-click `start_xampp.bat`
- **Mac/Linux**: Run `./start_xampp.sh`

### Step 4: Database Setup
1. In phpMyAdmin (opens automatically):
   - Create database: `attendance_db`
   - Import: `database.sql`

### Step 5: Configure Email
Edit `includes/config.php`:
```php
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
define('FROM_EMAIL', 'your_email@gmail.com');
```

### Step 6: Test
- Login: `http://localhost/attendance_system`
- Admin: `admin` / `password`
- Student: `STU001` / `password`

## 📋 Checklist
- [ ] XAMPP installed and running
- [ ] Files copied to htdocs
- [ ] Database created and imported
- [ ] Email configured
- [ ] System tested

## 🔧 Need Help?
- **Setup Issues**: Check `XAMPP_SETUP.md`
- **Email Problems**: Check `EMAIL_SETUP.md`
- **System Check**: Visit `xampp_quick_setup.php`

## 🎯 Default Credentials
- **Admin**: username `admin`, password `password`
- **Student**: ID `STU001`, password `password`
- **Database**: user `root`, password `` (empty)

## 📍 Important URLs
- **Main System**: `http://localhost/attendance_system`
- **phpMyAdmin**: `http://localhost/phpmyadmin`
- **Setup Check**: `http://localhost/attendance_system/xampp_quick_setup.php`
- **Email Test**: `http://localhost/attendance_system/test_email.php`

## 🆘 Common Issues
| Problem | Solution |
|---------|----------|
| Apache won't start | Change port 80 to 8080 |
| MySQL won't start | Change port 3306 to 3307 |
| Email not sending | Check Gmail App Password |
| Database error | Restart MySQL service |

## 📧 Email Setup (Gmail)
1. Enable 2-Factor Authentication
2. Generate App Password
3. Use App Password in config
4. Test with `test_email.php`

---

**That's it! Your attendance system should be running! 🎉**