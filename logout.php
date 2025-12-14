<?php
// Start session first
session_start();

// Destroy all session data
session_destroy();

// Redirect to login page using header
header("Location: login.php");
exit();
?>