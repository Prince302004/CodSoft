<?php
require_once 'config.php';

// Check if student is logged in
if (!isStudent()) {
    echo '<div class="alert alert-danger">Please log in first.</div>';
    exit;
}

try {
    $student_id = $_SESSION['student_id'];
    
    // Get recent attendance (last 10 records)
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.instructor 
        FROM attendance a 
        JOIN courses c ON a.course_code = c.course_code 
        WHERE a.student_id = ? 
        ORDER BY a.attendance_date DESC, a.attendance_time DESC 
        LIMIT 10
    ");
    $stmt->execute([$student_id]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($attendance_records)) {
        echo '<div class="text-center py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="text-muted">No attendance records found</p>
              </div>';
    } else {
        echo '<div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Instructor</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($attendance_records as $record) {
            $status_class = '';
            $status_text = '';
            
            switch ($record['status']) {
                case 'present':
                    $status_class = 'success';
                    $status_text = 'Present';
                    break;
                case 'late':
                    $status_class = 'warning';
                    $status_text = 'Late';
                    break;
                case 'absent':
                    $status_class = 'danger';
                    $status_text = 'Absent';
                    break;
            }
            
            $location_class = $record['location_verified'] ? 'success' : 'warning';
            $location_text = $record['location_verified'] ? 'Verified' : 'Pending';
            
            echo '<tr>
                    <td>' . date('M d, Y', strtotime($record['attendance_date'])) . '</td>
                    <td>' . htmlspecialchars($record['course_name']) . '</td>
                    <td>' . htmlspecialchars($record['instructor']) . '</td>
                    <td>' . date('h:i A', strtotime($record['attendance_time'])) . '</td>
                    <td><span class="badge bg-' . $status_class . '">' . $status_text . '</span></td>
                    <td><span class="badge bg-' . $location_class . '">' . $location_text . '</span></td>
                  </tr>';
        }
        
        echo '</tbody>
                </table>
              </div>';
    }
    
} catch (Exception $e) {
    error_log("Error fetching attendance records: " . $e->getMessage());
    echo '<div class="alert alert-danger">Error loading attendance records.</div>';
}
?>