<?php
require_once 'config.php';

class AttendancePDFGenerator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function generateStudentReport($student_id, $start_date = null, $end_date = null) {
        // Get student information
        $stmt = $this->pdo->prepare("SELECT s.*, ay.year_name, sem.semester_name 
                                    FROM students s 
                                    JOIN academic_years ay ON s.academic_year_id = ay.id 
                                    JOIN semesters sem ON s.semester_id = sem.id 
                                    WHERE s.student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            throw new Exception("Student not found");
        }
        
        // Set default date range if not provided
        if (!$start_date) {
            $start_date = date('Y-m-01'); // First day of current month
        }
        if (!$end_date) {
            $end_date = date('Y-m-t'); // Last day of current month
        }
        
        // Get attendance records
        $stmt = $this->pdo->prepare("SELECT a.*, s.subject_name, s.credits 
                                    FROM attendance a 
                                    JOIN subjects s ON a.subject_code = s.subject_code 
                                    WHERE a.student_id = ? AND a.attendance_date BETWEEN ? AND ? 
                                    ORDER BY a.attendance_date DESC, a.attendance_time DESC");
        $stmt->execute([$student_id, $start_date, $end_date]);
        $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate statistics
        $stats = $this->calculateStudentStats($student_id, $start_date, $end_date);
        
        // Generate PDF content
        $html = $this->generateStudentReportHTML($student, $attendance_records, $stats, $start_date, $end_date);
        
        // Convert to PDF and return
        return $this->htmlToPDF($html, "attendance_report_{$student_id}_" . date('Y-m-d') . ".pdf");
    }
    
    public function generateAdminReport($subject_code = null, $start_date = null, $end_date = null) {
        // Set default date range if not provided
        if (!$start_date) {
            $start_date = date('Y-m-01'); // First day of current month
        }
        if (!$end_date) {
            $end_date = date('Y-m-t'); // Last day of current month
        }
        
        // Get attendance records
        $query = "SELECT a.*, s.first_name, s.last_name, s.student_id, s.roll_number, 
                         sub.subject_name, sub.credits, ay.year_name, sem.semester_name 
                  FROM attendance a 
                  JOIN students s ON a.student_id = s.student_id 
                  JOIN subjects sub ON a.subject_code = sub.subject_code 
                  JOIN academic_years ay ON sub.academic_year_id = ay.id 
                  JOIN semesters sem ON sub.semester_id = sem.id 
                  WHERE a.attendance_date BETWEEN ? AND ?";
        
        $params = [$start_date, $end_date];
        
        if ($subject_code) {
            $query .= " AND a.subject_code = ?";
            $params[] = $subject_code;
        }
        
        $query .= " ORDER BY a.attendance_date DESC, a.attendance_time DESC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate overall statistics
        $stats = $this->calculateAdminStats($subject_code, $start_date, $end_date);
        
        // Generate PDF content
        $html = $this->generateAdminReportHTML($attendance_records, $stats, $start_date, $end_date, $subject_code);
        
        // Convert to PDF and return
        $filename = "admin_attendance_report_" . date('Y-m-d') . ".pdf";
        return $this->htmlToPDF($html, $filename);
    }
    
    private function calculateStudentStats($student_id, $start_date, $end_date) {
        $stats = [];
        
        // Total days in range
        $total_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
        
        // Get enrolled subjects
        $stmt = $this->pdo->prepare("SELECT s.subject_code, s.subject_name 
                                    FROM student_subjects ss 
                                    JOIN subjects s ON ss.subject_code = s.subject_code 
                                    WHERE ss.student_id = ? AND ss.status = 'enrolled'");
        $stmt->execute([$student_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($subjects as $subject) {
            // Count attendance for this subject
            $stmt = $this->pdo->prepare("SELECT 
                                        COUNT(*) as total_records,
                                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
                                        FROM attendance 
                                        WHERE student_id = ? AND subject_code = ? 
                                        AND attendance_date BETWEEN ? AND ?");
            $stmt->execute([$student_id, $subject['subject_code'], $start_date, $end_date]);
            $subject_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $attendance_percentage = $subject_stats['total_records'] > 0 ? 
                round(($subject_stats['present_count'] + $subject_stats['late_count']) / $subject_stats['total_records'] * 100, 2) : 0;
            
            $stats[$subject['subject_code']] = [
                'subject_name' => $subject['subject_name'],
                'total_records' => $subject_stats['total_records'],
                'present_count' => $subject_stats['present_count'],
                'absent_count' => $subject_stats['absent_count'],
                'late_count' => $subject_stats['late_count'],
                'attendance_percentage' => $attendance_percentage
            ];
        }
        
        return $stats;
    }
    
    private function calculateAdminStats($subject_code, $start_date, $end_date) {
        $query = "SELECT 
                  COUNT(*) as total_records,
                  COUNT(DISTINCT student_id) as unique_students,
                  SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                  SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                  SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
                  FROM attendance 
                  WHERE attendance_date BETWEEN ? AND ?";
        
        $params = [$start_date, $end_date];
        
        if ($subject_code) {
            $query .= " AND subject_code = ?";
            $params[] = $subject_code;
        }
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['attendance_percentage'] = $stats['total_records'] > 0 ? 
            round(($stats['present_count'] + $stats['late_count']) / $stats['total_records'] * 100, 2) : 0;
        
        return $stats;
    }
    
    private function generateStudentReportHTML($student, $attendance_records, $stats, $start_date, $end_date) {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Student Attendance Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .student-info {
                    background-color: #e9ecef;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .student-info h3 {
                    margin-top: 0;
                    color: #007bff;
                }
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 15px;
                    margin-bottom: 30px;
                }
                .stat-card {
                    background-color: #fff;
                    border: 1px solid #dee2e6;
                    border-radius: 5px;
                    padding: 15px;
                    text-align: center;
                }
                .stat-card h4 {
                    margin-top: 0;
                    color: #495057;
                }
                .stat-value {
                    font-size: 24px;
                    font-weight: bold;
                    color: #007bff;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #dee2e6;
                    padding: 10px;
                    text-align: left;
                }
                th {
                    background-color: #f8f9fa;
                    font-weight: bold;
                }
                .status-present { color: #28a745; font-weight: bold; }
                .status-absent { color: #dc3545; font-weight: bold; }
                .status-late { color: #ffc107; font-weight: bold; }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #6c757d;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Student Attendance Report</h1>
                <p>Generated on: ' . date('F j, Y') . '</p>
                <p>Report Period: ' . date('F j, Y', strtotime($start_date)) . ' to ' . date('F j, Y', strtotime($end_date)) . '</p>
            </div>
            
            <div class="student-info">
                <h3>Student Information</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <div><strong>Name:</strong> ' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</div>
                    <div><strong>Student ID:</strong> ' . htmlspecialchars($student['student_id']) . '</div>
                    <div><strong>Roll Number:</strong> ' . htmlspecialchars($student['roll_number']) . '</div>
                    <div><strong>Course:</strong> ' . htmlspecialchars($student['course']) . '</div>
                    <div><strong>Year:</strong> ' . htmlspecialchars($student['year_name']) . '</div>
                    <div><strong>Semester:</strong> ' . htmlspecialchars($student['semester_name']) . '</div>
                </div>
            </div>
            
            <h3>Subject-wise Attendance Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Total Classes</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($stats as $subject_code => $stat) {
            $percentage_class = $stat['attendance_percentage'] >= 75 ? 'status-present' : ($stat['attendance_percentage'] >= 50 ? 'status-late' : 'status-absent');
            $html .= '<tr>
                        <td>' . htmlspecialchars($stat['subject_name']) . '</td>
                        <td>' . $stat['total_records'] . '</td>
                        <td class="status-present">' . $stat['present_count'] . '</td>
                        <td class="status-absent">' . $stat['absent_count'] . '</td>
                        <td class="status-late">' . $stat['late_count'] . '</td>
                        <td class="' . $percentage_class . '">' . $stat['attendance_percentage'] . '%</td>
                    </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <h3>Detailed Attendance Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Marked By</th>
                    </tr>
                </thead>
                <tbody>';
        
        if (empty($attendance_records)) {
            $html .= '<tr><td colspan="5" style="text-align: center; color: #6c757d;">No attendance records found for this period.</td></tr>';
        } else {
            foreach ($attendance_records as $record) {
                $status_class = 'status-' . $record['status'];
                $html .= '<tr>
                            <td>' . date('M j, Y', strtotime($record['attendance_date'])) . '</td>
                            <td>' . date('g:i A', strtotime($record['attendance_time'])) . '</td>
                            <td>' . htmlspecialchars($record['subject_name']) . '</td>
                            <td class="' . $status_class . '">' . ucfirst($record['status']) . '</td>
                            <td>' . ucfirst($record['marked_by']) . '</td>
                        </tr>';
            }
        }
        
        $html .= '</tbody>
            </table>
            
            <div class="footer">
                <p>This report was generated by the College Attendance Management System.</p>
                <p>For any queries, please contact the system administrator.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function generateAdminReportHTML($attendance_records, $stats, $start_date, $end_date, $subject_code = null) {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Admin Attendance Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 15px;
                    margin-bottom: 30px;
                }
                .stat-card {
                    background-color: #fff;
                    border: 1px solid #dee2e6;
                    border-radius: 5px;
                    padding: 15px;
                    text-align: center;
                }
                .stat-card h4 {
                    margin-top: 0;
                    color: #495057;
                }
                .stat-value {
                    font-size: 24px;
                    font-weight: bold;
                    color: #007bff;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #dee2e6;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f8f9fa;
                    font-weight: bold;
                }
                .status-present { color: #28a745; font-weight: bold; }
                .status-absent { color: #dc3545; font-weight: bold; }
                .status-late { color: #ffc107; font-weight: bold; }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #6c757d;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Admin Attendance Report</h1>
                <p>Generated on: ' . date('F j, Y') . '</p>
                <p>Report Period: ' . date('F j, Y', strtotime($start_date)) . ' to ' . date('F j, Y', strtotime($end_date)) . '</p>
                ' . ($subject_code ? '<p>Subject: ' . htmlspecialchars($subject_code) . '</p>' : '') . '
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Records</h4>
                    <div class="stat-value">' . $stats['total_records'] . '</div>
                </div>
                <div class="stat-card">
                    <h4>Unique Students</h4>
                    <div class="stat-value">' . $stats['unique_students'] . '</div>
                </div>
                <div class="stat-card">
                    <h4>Present</h4>
                    <div class="stat-value status-present">' . $stats['present_count'] . '</div>
                </div>
                <div class="stat-card">
                    <h4>Absent</h4>
                    <div class="stat-value status-absent">' . $stats['absent_count'] . '</div>
                </div>
                <div class="stat-card">
                    <h4>Late</h4>
                    <div class="stat-value status-late">' . $stats['late_count'] . '</div>
                </div>
                <div class="stat-card">
                    <h4>Overall Attendance</h4>
                    <div class="stat-value">' . $stats['attendance_percentage'] . '%</div>
                </div>
            </div>
            
            <h3>Detailed Attendance Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Roll Number</th>
                        <th>Subject</th>
                        <th>Year/Semester</th>
                        <th>Status</th>
                        <th>Marked By</th>
                    </tr>
                </thead>
                <tbody>';
        
        if (empty($attendance_records)) {
            $html .= '<tr><td colspan="9" style="text-align: center; color: #6c757d;">No attendance records found for this period.</td></tr>';
        } else {
            foreach ($attendance_records as $record) {
                $status_class = 'status-' . $record['status'];
                $html .= '<tr>
                            <td>' . date('M j, Y', strtotime($record['attendance_date'])) . '</td>
                            <td>' . date('g:i A', strtotime($record['attendance_time'])) . '</td>
                            <td>' . htmlspecialchars($record['student_id']) . '</td>
                            <td>' . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . '</td>
                            <td>' . htmlspecialchars($record['roll_number']) . '</td>
                            <td>' . htmlspecialchars($record['subject_name']) . '</td>
                            <td>' . htmlspecialchars($record['year_name'] . ' - ' . $record['semester_name']) . '</td>
                            <td class="' . $status_class . '">' . ucfirst($record['status']) . '</td>
                            <td>' . ucfirst($record['marked_by']) . '</td>
                        </tr>';
            }
        }
        
        $html .= '</tbody>
            </table>
            
            <div class="footer">
                <p>This report was generated by the College Attendance Management System.</p>
                <p>For any queries, please contact the system administrator.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function htmlToPDF($html, $filename) {
        // For basic HTML to PDF conversion using DomPDF or similar
        // This is a simplified version - in production, you'd use a proper PDF library
        
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // For this demo, we'll output HTML that can be printed as PDF
        // In production, integrate with libraries like DomPDF, TCPDF, or wkhtmltopdf
        echo $html;
        
        // Add JavaScript for automatic print dialog
        echo '<script>window.print();</script>';
        
        return true;
    }
}
?>