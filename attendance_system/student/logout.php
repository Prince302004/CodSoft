<?php
require_once '../includes/config.php';

// Destroy session and redirect
session_destroy();
redirect('../index.php');
?>