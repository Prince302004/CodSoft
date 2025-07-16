@echo off
echo ===============================================
echo    College Attendance Management System
echo         XAMPP Quick Start Script
echo ===============================================
echo.

:: Check if XAMPP is installed in default location
if exist "C:\xampp\xampp-control.exe" (
    echo XAMPP found in default location: C:\xampp\
    set XAMPP_PATH=C:\xampp
) else (
    echo XAMPP not found in default location.
    echo Please enter your XAMPP installation path (e.g., C:\xampp):
    set /p XAMPP_PATH=
)

:: Start XAMPP Control Panel
echo Starting XAMPP Control Panel...
start "" "%XAMPP_PATH%\xampp-control.exe"

:: Wait for services to start
echo Waiting for services to start...
timeout /t 5 /nobreak >nul

:: Check if services are running and open URLs
echo.
echo ===============================================
echo Opening system URLs...
echo ===============================================

:: Open phpMyAdmin
echo Opening phpMyAdmin for database setup...
start "" "http://localhost/phpmyadmin"

:: Wait a moment
timeout /t 2 /nobreak >nul

:: Open system setup page
echo Opening system setup page...
start "" "http://localhost/attendance_system/xampp_quick_setup.php"

:: Wait a moment
timeout /t 2 /nobreak >nul

:: Open main application
echo Opening attendance system...
start "" "http://localhost/attendance_system"

echo.
echo ===============================================
echo Setup Instructions:
echo ===============================================
echo 1. In phpMyAdmin: Create database 'attendance_db'
echo 2. Import the 'database.sql' file
echo 3. Edit 'includes/config.php' for email settings
echo 4. Test the system with default credentials:
echo    - Admin: username 'admin', password 'password'
echo    - Student: ID 'STU001', password 'password'
echo.
echo Press any key to exit...
pause >nul