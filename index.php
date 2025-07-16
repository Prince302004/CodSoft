<?php
require_once 'config.php';

// Check if user is logged in
if (is_logged_in()) {
    // Redirect to dashboard if logged in
    redirect('dashboard.php');
} else {
    // Redirect to login if not logged in
    redirect('login.php');
}
?>