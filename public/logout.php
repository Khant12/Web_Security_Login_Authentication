<?php

require "../private/autoload.php";

// Start the session
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: login.php");
exit;4


?>