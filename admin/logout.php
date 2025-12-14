

<?php
// Include config and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Destroy all session data
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>