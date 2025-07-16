#!/bin/bash

echo "==============================================="
echo "   College Attendance Management System"
echo "        XAMPP Quick Start Script"
echo "==============================================="
echo ""

# Check OS type
OS_TYPE=$(uname -s)

if [ "$OS_TYPE" = "Darwin" ]; then
    # Mac OS
    XAMPP_PATH="/Applications/XAMPP"
    XAMPP_CONTROL="$XAMPP_PATH/manager-osx"
    
    if [ -f "$XAMPP_CONTROL" ]; then
        echo "XAMPP found on Mac: $XAMPP_PATH"
        echo "Starting XAMPP services..."
        sudo "$XAMPP_PATH/xamppfiles/xampp" start
    else
        echo "XAMPP not found. Please install XAMPP for Mac."
        exit 1
    fi
elif [ "$OS_TYPE" = "Linux" ]; then
    # Linux
    XAMPP_PATH="/opt/lampp"
    
    if [ -d "$XAMPP_PATH" ]; then
        echo "XAMPP found on Linux: $XAMPP_PATH"
        echo "Starting XAMPP services..."
        sudo "$XAMPP_PATH/lampp" start
    else
        echo "XAMPP not found. Please install XAMPP for Linux."
        exit 1
    fi
else
    echo "Unsupported operating system: $OS_TYPE"
    exit 1
fi

echo "Waiting for services to start..."
sleep 5

echo ""
echo "==============================================="
echo "Opening system URLs..."
echo "==============================================="

# Function to open URL based on OS
open_url() {
    if [ "$OS_TYPE" = "Darwin" ]; then
        open "$1"
    elif [ "$OS_TYPE" = "Linux" ]; then
        if command -v xdg-open > /dev/null; then
            xdg-open "$1"
        elif command -v gnome-open > /dev/null; then
            gnome-open "$1"
        else
            echo "Please open $1 in your browser"
        fi
    fi
}

# Open phpMyAdmin
echo "Opening phpMyAdmin for database setup..."
open_url "http://localhost/phpmyadmin"

sleep 2

# Open system setup page
echo "Opening system setup page..."
open_url "http://localhost/attendance_system/xampp_quick_setup.php"

sleep 2

# Open main application
echo "Opening attendance system..."
open_url "http://localhost/attendance_system"

echo ""
echo "==============================================="
echo "Setup Instructions:"
echo "==============================================="
echo "1. In phpMyAdmin: Create database 'attendance_db'"
echo "2. Import the 'database.sql' file"
echo "3. Edit 'includes/config.php' for email settings"
echo "4. Test the system with default credentials:"
echo "   - Admin: username 'admin', password 'password'"
echo "   - Student: ID 'STU001', password 'password'"
echo ""
echo "Press Enter to exit..."
read -r