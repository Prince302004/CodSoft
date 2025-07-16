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

-- Teachers/Faculty table
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    qualification VARCHAR(200) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Years table for academic years
CREATE TABLE IF NOT EXISTS academic_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_name VARCHAR(50) NOT NULL,
    year_number INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Semesters table
CREATE TABLE IF NOT EXISTS semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester_name VARCHAR(50) NOT NULL,
    semester_number INT NOT NULL,
    academic_year_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
);

-- Subjects table with year-specific subjects
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    academic_year_id INT NOT NULL,
    semester_id INT NOT NULL,
    teacher_id VARCHAR(20) NOT NULL,
    credits INT DEFAULT 3,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE
);

-- Students table with extended information
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    course VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    academic_year_id INT NOT NULL,
    semester_id INT NOT NULL,
    roll_number VARCHAR(20) NOT NULL,
    admission_date DATE NOT NULL,
    address TEXT,
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(15),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
);

-- Student Subject Enrollment
CREATE TABLE IF NOT EXISTS student_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('enrolled', 'dropped', 'completed') DEFAULT 'enrolled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, subject_code)
);

-- Courses table (schedule information)
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

-- Attendance table with enhanced tracking
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_time TIME NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    location_verified BOOLEAN DEFAULT FALSE,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    marked_by ENUM('student', 'teacher', 'admin') DEFAULT 'student',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, subject_code, attendance_date)
);

-- OTP verification table
CREATE TABLE IF NOT EXISTS otp_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    user_type ENUM('student', 'teacher', 'admin') NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    email VARCHAR(100) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    user_type ENUM('student', 'teacher', 'admin') NOT NULL,
    token VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

-- Course programs table
CREATE TABLE IF NOT EXISTS course_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    department VARCHAR(100) NOT NULL,
    duration_years INT NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data

-- Admin user
INSERT INTO admin (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@college.edu');

-- Campus location
INSERT INTO campus_location (campus_name, latitude, longitude, radius_meters) VALUES 
('Main Campus', 40.7128, -74.0060, 100);

-- Academic years
INSERT INTO academic_years (year_name, year_number, description) VALUES 
('First Year', 1, 'Foundation Year - Basic concepts and fundamentals'),
('Second Year', 2, 'Intermediate Year - Core subjects and specialization'),
('Third Year', 3, 'Advanced Year - Specialization and projects');

-- Semesters
INSERT INTO semesters (semester_name, semester_number, academic_year_id, start_date, end_date, is_active) VALUES 
('First Semester', 1, 1, '2024-01-15', '2024-06-15', TRUE),
('Second Semester', 2, 1, '2024-07-01', '2024-12-15', TRUE),
('Third Semester', 1, 2, '2024-01-15', '2024-06-15', TRUE),
('Fourth Semester', 2, 2, '2024-07-01', '2024-12-15', TRUE),
('Fifth Semester', 1, 3, '2024-01-15', '2024-06-15', TRUE),
('Sixth Semester', 2, 3, '2024-07-01', '2024-12-15', TRUE);

-- Sample Teachers
INSERT INTO teachers (teacher_id, first_name, last_name, email, phone, password, department, qualification) VALUES 
('TCH001', 'Dr. John', 'Smith', 'john.smith@college.edu', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Computer Science', 'PhD in Computer Science'),
('TCH002', 'Dr. Emily', 'Johnson', 'emily.johnson@college.edu', '+1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mathematics', 'PhD in Mathematics'),
('TCH003', 'Prof. Robert', 'Brown', 'robert.brown@college.edu', '+1234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'English', 'MA in English Literature');

-- First Year Subjects
INSERT INTO subjects (subject_code, subject_name, academic_year_id, semester_id, teacher_id, credits, description) VALUES 
('CS101', 'Programming Fundamentals', 1, 1, 'TCH001', 4, 'Basic programming concepts using C++'),
('MATH101', 'Calculus I', 1, 1, 'TCH002', 4, 'Differential and integral calculus'),
('ENG101', 'English Communication', 1, 1, 'TCH003', 3, 'Communication skills and grammar'),
('CS102', 'Data Structures', 1, 2, 'TCH001', 4, 'Arrays, linked lists, stacks, queues'),
('MATH102', 'Calculus II', 1, 2, 'TCH002', 4, 'Advanced calculus and applications'),
('ENG102', 'Technical Writing', 1, 2, 'TCH003', 3, 'Technical documentation and report writing');

-- Second Year Subjects
INSERT INTO subjects (subject_code, subject_name, academic_year_id, semester_id, teacher_id, credits, description) VALUES 
('CS201', 'Object Oriented Programming', 2, 3, 'TCH001', 4, 'OOP concepts using Java'),
('CS202', 'Database Management', 2, 3, 'TCH001', 4, 'Database design and SQL'),
('MATH201', 'Discrete Mathematics', 2, 3, 'TCH002', 4, 'Logic, sets, and combinatorics'),
('CS203', 'Web Development', 2, 4, 'TCH001', 4, 'HTML, CSS, JavaScript, PHP'),
('CS204', 'Software Engineering', 2, 4, 'TCH001', 4, 'Software development methodologies'),
('MATH202', 'Statistics', 2, 4, 'TCH002', 3, 'Statistical analysis and probability');

-- Third Year Subjects
INSERT INTO subjects (subject_code, subject_name, academic_year_id, semester_id, teacher_id, credits, description) VALUES 
('CS301', 'Advanced Algorithms', 3, 5, 'TCH001', 4, 'Complex algorithms and analysis'),
('CS302', 'Computer Networks', 3, 5, 'TCH001', 4, 'Network protocols and architecture'),
('CS303', 'Machine Learning', 3, 5, 'TCH001', 4, 'ML algorithms and applications'),
('CS304', 'Project I', 3, 6, 'TCH001', 6, 'Major project development'),
('CS305', 'Mobile App Development', 3, 6, 'TCH001', 4, 'Android and iOS development'),
('CS306', 'Cybersecurity', 3, 6, 'TCH001', 4, 'Security principles and practices');

-- Sample Students
INSERT INTO students (student_id, first_name, last_name, email, phone, password, course, department, academic_year_id, semester_id, roll_number, admission_date, address, guardian_name, guardian_phone) VALUES 
('STU001', 'John', 'Doe', 'john.doe@student.edu', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bachelor of Computer Applications', 'Computer Science', 1, 1, 'BCA001', '2024-01-15', '123 Main St, City', 'Mr. Richard Doe', '+1234567800'),
('STU002', 'Jane', 'Smith', 'jane.smith@student.edu', '+1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bachelor of Computer Applications', 'Computer Science', 2, 3, 'BCA002', '2023-01-15', '456 Oak Ave, City', 'Ms. Mary Smith', '+1234567801');

-- Student Subject Enrollments
INSERT INTO student_subjects (student_id, subject_code, enrollment_date) VALUES 
('STU001', 'CS101', '2024-01-15'),
('STU001', 'MATH101', '2024-01-15'),
('STU001', 'ENG101', '2024-01-15'),
('STU002', 'CS201', '2024-01-15'),
('STU002', 'CS202', '2024-01-15'),
('STU002', 'MATH201', '2024-01-15');

-- Sample Course Programs
INSERT INTO course_programs (course_name, course_code, department, duration_years, description) VALUES 
('Bachelor of Computer Applications', 'BCA', 'Computer Science', 3, 'A comprehensive undergraduate program in computer applications'),
('Master of Computer Applications', 'MCA', 'Computer Science', 2, 'A postgraduate program in computer applications'),
('Bachelor of Science in Computer Science', 'BSC-CS', 'Computer Science', 3, 'A degree program focused on computer science fundamentals'),
('Bachelor of Technology', 'B.Tech', 'Computer Science', 4, 'An engineering degree in computer science and technology');

-- Legacy courses table (keeping for backward compatibility)
INSERT INTO courses (course_code, course_name, instructor, schedule_time, schedule_day, room) VALUES 
('CS101', 'Programming Fundamentals', 'Dr. John Smith', '09:00:00', 'Monday', 'Room 101'),
('MATH101', 'Calculus I', 'Dr. Emily Johnson', '10:30:00', 'Tuesday', 'Room 201'),
('ENG101', 'English Communication', 'Prof. Robert Brown', '14:00:00', 'Wednesday', 'Room 301');