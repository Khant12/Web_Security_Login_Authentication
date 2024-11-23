<?php
// Include database connection
require "../private/autoload.php";


// Check if the token is provided in the URL
if (!isset($_GET['token'])) {
    die("Invalid access. No token provided.");
}

// Get the token from the URL
$token = $_GET['token'];

// Validate the token in the database
$query = "SELECT * FROM users WHERE verify_token = :token";
$stmt = $connection->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_data) {
    // Token is invalid
    die("Invalid or expired token.");
}

// Get the user's role and ID
$user_role = $user_data['role'];
$user_id = $user_data['id'];

// Store the user ID and role in the session
$_SESSION['user_id'] = $user_id;
$_SESSION['role'] = $user_role;

// Clear the token after validation to prevent reuse
$query = "UPDATE users SET verify_token = NULL WHERE verify_token = :token";
$stmt = $connection->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->execute();

// Redirect based on the user's role
if ($user_role == "admin") {
    // Redirect to the admin page if the user is an admin
    header("Location: admin.php");
    exit;
} else {
    // Redirect to the user page or home page if the user is not an admin
    header("Location: index.php");
    exit;
}
?>
