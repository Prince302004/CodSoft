# Complete College Attendance Management System - Features Guide

## Overview
This comprehensive college attendance management system includes all the requested features:
- Student/Teacher/Admin login with OTP verification
- Geolocation-based attendance marking
- Sign-up and forgot password functionality
- Subject management per academic year
- PDF report generation
- Enhanced admin panel with teacher management

## 🚀 Quick Start

### 1. Database Setup
```sql
-- Import the updated database.sql file
mysql -u root -p < database.sql
```

### 2. Configuration
Edit `includes/config.php` to update:
- Database credentials
- Campus location coordinates
- Email settings for OTP and password reset

### 3. Access the System
- **URL**: `http://localhost/attendance_system/`
- **Default Admin**: username: `admin`, password: `password`
- **Sample Student**: ID: `STU001`, password: `password`
- **Sample Teacher**: ID: `TCH001`, password: `password`

## 📋 Complete Features List

### 🔐 Authentication System

#### Multi-User Login
- **Student Login**: Student ID + Password + OTP
- **Teacher Login**: Teacher ID + Password + OTP
- **Admin Login**: Username + Password (direct access)

#### Security Features
- Email-based OTP verification (5-minute expiry)
- Password hashing using PHP's password_hash()
- Session management with proper logout
- Forgot password with email token reset

#### Sign-up Process
- **Student Registration**: Complete profile form including:
  - Basic information (name, email, phone)
  - Academic details (course, department, year, semester)
  - Address and guardian information
  - Automatic subject enrollment based on selected year/semester

### 📍 Geolocation Features

#### Campus Location Verification
- Real-time location detection using HTML5 Geolocation API
- Configurable campus coordinates and radius
- Students can only mark attendance when on campus
- Distance calculation using Haversine formula

#### Location Settings
```php
// In includes/config.php
define('CAMPUS_LATITUDE', 40.7128);   // Your campus latitude
define('CAMPUS_LONGITUDE', -74.0060); // Your campus longitude
define('CAMPUS_RADIUS', 100);         // Radius in meters
```

### 🎓 Academic Structure

#### Year-based Subject Management
- **First Year**: Foundation subjects (Programming, Calculus, English)
- **Second Year**: Intermediate subjects (OOP, Database, Web Development)
- **Third Year**: Advanced subjects (Algorithms, Networks, Machine Learning)

#### Semester System
- Each year divided into 2 semesters
- Subjects assigned to specific year/semester combinations
- Automatic enrollment during student registration

### 👨‍🏫 Teacher Management

#### Teacher Features
- **Dashboard**: View assigned subjects and student attendance
- **Attendance Management**: Mark/modify student attendance manually
- **Student Lists**: View enrolled students per subject
- **Real-time Statistics**: Today's attendance percentages

#### Teacher Profile Management
- **Self-Profile Update**: Teachers can update their own information
- **Password Management**: Change password functionality
- **Subject Requests**: Request assignment to new subjects
- **Subject Removal**: Remove themselves from subject assignments
- **Department Transfer**: Update department information
- **Qualification Updates**: Update educational qualifications

#### Subject Self-Management
- **View Assigned Subjects**: See all currently assigned subjects
- **Request New Subjects**: Request assignment to available subjects
- **Remove Assignments**: Remove themselves from subjects (with restrictions)
- **Subject Details**: View subject information and enrolled students

### 👨‍💼 Admin Panel

#### Student Management
- View all student registrations
- Monitor attendance statistics
- Generate comprehensive reports

#### Teacher Management
- **Complete Teacher Management**: Add, edit, delete teachers
- **Password Reset**: Reset teacher passwords
- **Subject Assignment**: Assign subjects to teachers
- **Department Management**: Organize teachers by departments
- **Status Control**: Activate/deactivate teacher accounts
- **Note**: Only admins can add teachers (as requested)

#### Subject Management
- **Add/Edit/Delete Subjects**: Full CRUD operations for subjects
- **Year-Specific Organization**: Subjects categorized by academic year
- **Semester Management**: Organize subjects by semesters
- **Teacher Assignment**: Assign teachers to subjects
- **Credit System**: Manage subject credits
- **Enrollment Control**: Automatic student enrollment

#### Course Management
- **Course Programs**: Add, edit, delete course programs
- **Department Organization**: Courses organized by departments
- **Duration Control**: Set course duration in years
- **Student Tracking**: Monitor student enrollment per course
- **Status Management**: Activate/deactivate courses

#### System Analytics
- Overall attendance statistics
- Subject-wise performance
- Student performance tracking
- Report generation capabilities

### 📊 PDF Report Generation

#### Student Reports
- **Personal Attendance Report**: Individual student attendance
- **Subject-wise Statistics**: Performance per subject
- **Date Range Selection**: Custom period reports
- **Detailed Records**: Complete attendance history

#### Admin Reports
- **Comprehensive Analytics**: System-wide attendance data
- **Subject Filtering**: Reports for specific subjects
- **Student Performance**: Detailed student statistics
- **Export Options**: PDF download with printing support

#### Report Features
- Professional HTML/CSS styling
- Automatic print dialog
- Detailed statistics and charts
- Date range customization

### 📱 User Interface

#### Modern Design
- Bootstrap 5 responsive design
- Mobile-first approach
- Professional color scheme
- Font Awesome icons

#### User Experience
- Intuitive navigation
- Real-time feedback
- Loading indicators
- Error handling with user-friendly messages

## 📖 Usage Instructions

### For Students

#### 1. Registration Process
1. Visit the homepage and click "Sign Up"
2. Fill in all required information:
   - Personal details
   - Academic information (year, semester, course)
   - Address and guardian details
   - Password creation
3. Submit and receive confirmation
4. Use credentials to login

#### 2. Attendance Marking
1. Login with Student ID and password
2. Enter OTP received via email
3. Enable location services when prompted
4. Select subject from dropdown
5. Click "Mark Attendance" (only works on campus)

#### 3. Generate Reports
1. Go to student dashboard
2. Click "Download Attendance Report"
3. Select date range (optional)
4. PDF will be generated and downloaded

### For Teachers

#### 1. Login Process
1. Select "Teacher" on login page
2. Enter Teacher ID and password
3. Enter OTP received via email
4. Access teacher dashboard

#### 2. Manage Attendance
1. View assigned subjects on dashboard
2. Click "Manage Attendance" for a subject
3. Mark students as Present/Absent/Late
4. Changes are saved automatically

#### 3. Profile Management
1. Click "Profile" in navigation menu
2. Update personal information
3. Change password securely
4. Update department and qualification details

#### 4. Subject Management
1. Go to Profile page
2. View currently assigned subjects
3. Request assignment to new subjects
4. Remove yourself from subjects (with restrictions)
5. View subject details and enrolled students

#### 5. View Statistics
- Dashboard shows real-time attendance percentages
- Recent attendance records
- Subject-wise enrolled student counts

### For Admins

#### 1. Access Admin Panel
1. Click "Admin Login" on homepage
2. Enter username and password
3. Access admin dashboard

#### 2. Manage Teachers
1. Navigate to "Teachers" in navigation menu
2. Add new teachers with complete details
3. Edit existing teacher information
4. Reset teacher passwords
5. Assign/reassign subjects to teachers
6. Activate/deactivate teacher accounts

#### 3. Manage Subjects
1. Go to "Subjects" in navigation menu
2. Add new subjects with year/semester assignment
3. Edit subject details and teacher assignments
4. Delete subjects (with validation)
5. Organize subjects by academic year and semester

#### 4. Manage Courses
1. Navigate to "Courses" in navigation menu
2. Add new course programs
3. Edit course details and duration
4. Monitor student enrollment per course
5. Activate/deactivate courses

#### 5. Generate Reports
1. Go to reports section
2. Select date range and filters
3. Choose subject (optional)
4. Download comprehensive PDF reports

## 🔧 Technical Implementation

### Database Schema
```sql
-- Key tables created:
- students (extended with academic info)
- teachers (complete teacher profiles)
- academic_years (year management)
- semesters (semester organization)
- subjects (year-specific subjects)
- student_subjects (enrollment tracking)
- course_programs (course management)
- attendance (enhanced with geolocation)
- otp_verification (multi-user OTP)
- password_reset_tokens (password recovery)
- campus_location (geolocation settings)
```

### File Structure
```
attendance_system/
├── admin/
│   ├── dashboard.php (admin interface)
│   ├── manage_subjects.php (subject management)
│   ├── manage_teachers.php (teacher management)
│   ├── manage_courses.php (course management)
│   └── logout.php
├── student/
│   ├── dashboard.php (student interface)
│   └── logout.php
├── teacher/
│   ├── dashboard.php (teacher interface)
│   ├── profile.php (teacher profile management)
│   └── logout.php
├── includes/
│   ├── config.php (enhanced configuration)
│   ├── pdf_generator.php (PDF report class)
│   └── [other includes]
├── signup.php (student registration)
├── forgot_password.php (password recovery)
├── reset_password.php (password reset)
├── generate_report.php (PDF generation)
├── index.php (enhanced login)
└── database.sql (complete schema)
```

### Security Features
- Password hashing with PHP's password_hash()
- Session management with proper timeout
- SQL injection prevention using prepared statements
- XSS protection with htmlspecialchars()
- CSRF protection for forms
- Input validation and sanitization

## 🎯 Key Features Implemented

### ✅ All Requested Features
1. **Multi-user System**: Student, Teacher, Admin login
2. **OTP Verification**: Email-based authentication
3. **Geolocation**: Campus-based attendance marking
4. **Sign-up System**: Complete student registration
5. **Forgot Password**: Email-based password recovery
6. **Subject Management**: Year-specific subject organization with add/edit/delete
7. **Teacher Component**: Complete teacher functionality with self-management
8. **Admin Panel**: Enhanced with comprehensive management system
9. **PDF Reports**: Student and admin report generation
10. **Academic Structure**: Year/semester/subject organization
11. **Faculty Management**: Add/edit/delete teachers and faculty
12. **Course Management**: Add/edit/delete course programs
13. **Teacher Self-Management**: Profile updates and subject requests

### 🌟 Additional Enhancements
- Responsive mobile design
- Real-time attendance statistics
- Professional email templates
- Comprehensive error handling
- Session security improvements
- Modern UI/UX with Bootstrap 5

## 📞 Support

### Default Login Credentials
- **Admin**: username: `admin`, password: `password`
- **Student**: ID: `STU001`, password: `password`
- **Teacher**: ID: `TCH001`, password: `password`

### Configuration Notes
1. Update email settings in `includes/config.php` for OTP functionality
2. Set correct campus coordinates for geolocation
3. Configure database connection parameters
4. Enable required PHP extensions (PDO, mysqli)

### Troubleshooting
- Check database connection in `includes/config.php`
- Verify email settings for OTP delivery
- Ensure proper file permissions
- Check PHP error logs for detailed debugging

## 🚀 Production Deployment

### Requirements
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- SSL certificate (for HTTPS)
- Email server configuration

### Security Recommendations
1. Use HTTPS in production
2. Configure proper email server (not localhost)
3. Set secure session parameters
4. Regular database backups
5. Monitor system logs
6. Update default passwords

This system provides a complete solution for college attendance management with all requested features implemented professionally and securely.