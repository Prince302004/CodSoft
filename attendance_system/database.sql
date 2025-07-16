-- College Attendance Management System Database
-- Created: 2024

CREATE DATABASE IF NOT EXISTS attendance_db;
USE attendance_db;

-- Admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    course VARCHAR(100) NOT NULL,
    year_level INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    instructor VARCHAR(100) NOT NULL,
    schedule_time TIME NOT NULL,
    schedule_day VARCHAR(20) NOT NULL,
    room VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_time TIME NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    location_verified BOOLEAN DEFAULT FALSE,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, course_code, attendance_date)
);

-- OTP verification table
CREATE TABLE IF NOT EXISTS otp_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    email VARCHAR(100) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Campus location configuration
CREATE TABLE IF NOT EXISTS campus_location (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_name VARCHAR(100) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    radius_meters INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO admin (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@college.edu');

INSERT INTO campus_location (campus_name, latitude, longitude, radius_meters) VALUES 
('Main Campus', 40.7128, -74.0060, 100);

INSERT INTO courses (course_code, course_name, instructor, schedule_time, schedule_day, room) VALUES 
('CS101', 'Introduction to Computer Science', 'Dr. Smith', '09:00:00', 'Monday', 'Room 101'),
('MATH101', 'Calculus I', 'Dr. Johnson', '10:30:00', 'Tuesday', 'Room 201'),
('ENG101', 'English Literature', 'Prof. Brown', '14:00:00', 'Wednesday', 'Room 301');

INSERT INTO students (student_id, first_name, last_name, email, phone, password, course, year_level) VALUES 
('STU001', 'John', 'Doe', 'john.doe@student.edu', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Computer Science', 1),
('STU002', 'Jane', 'Smith', 'jane.smith@student.edu', '+1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mathematics', 2);