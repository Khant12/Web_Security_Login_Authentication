<?php

// Include PHPMailer and Database Connection
require "../private/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    // Redirect to login page if user is not logged in
    header("Location: login.php");
    exit;
}

// Function to mask email address
function maskEmail($email) {
    $parts = explode('@', $email);
    $username = $parts[0];
    $domain = $parts[1];

    // Mask the username except for the first and last characters
    $maskedUsername = substr($username, 0, 1) . str_repeat('*', max(strlen($username) - 2, 0)) . substr($username, -1);

    return $maskedUsername . '@' . $domain;
}

// Get the user's email address from the session
$email = $_SESSION['email'];
$maskedEmail = maskEmail($email); // Generate masked email

// Generate a unique token for email verification
$token = bin2hex(random_bytes(16));

// Save the token to the database for the user
$query = "UPDATE users SET verify_token = :token WHERE email = :email";
$stmt = $connection->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->bindParam(':email', $email);
if (!$stmt->execute()) {
    // Error saving the token
    die("Error updating the database with the token.");
}

// Generate the verification and rejection links
$verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/login/public/confirm-login.php?token=$token";
$rejection_link = "http://" . $_SERVER['HTTP_HOST'] . "/login/public/logout.php?token=$token";

// Send the email with PHPMailer
$mail = new PHPMailer(true);

try {
    // SMTP settings
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->Username = 'testingotp234@gmail.com'; // Your SMTP username
    $mail->Password = 'lqxy iltd pzqb yrmi';     // Your SMTP password (App Password for Gmail)

    // Set email sender and recipient
    $mail->setFrom('no-reply@gmail.com', 'Verify Login');
    $mail->addAddress($email);

    // Set email subject and body
    $mail->Subject = 'Confirm Your Login Attempt';
    $mail->isHTML(true);
    $mail->Body = "
    <html>
    <body>
        <p>Is this you trying to log in?</p>
        <a href='$verification_link' style='padding:10px 20px; background:green; color:white; text-decoration:none;'>Yes, it's me</a>
        <a href='$rejection_link' style='padding:10px 20px; background:red; color:white; text-decoration:none;'>No, it's not me</a>
        <p>This link will expire in 5 minutes.</p>
    </body>
    </html>";

    // Send the email
    $mail->send();

    // Display success message with Bootstrap card
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Verification</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="d-flex justify-content-center align-items-center vh-100" style="background-color:#eeede7;">

        <div class="card shadow-lg" style="max-width: 500px; width: 100%;">
            <div class="card-body">
                <h5 class="card-title text-center">Confirm Login</h5>
                <div class="alert alert-success" role="alert">
                    <strong>One More Step!</strong><br><br>Before you login, please check your Gmail inbox at <strong>' . htmlspecialchars($maskedEmail) . '</strong> to confirm your login attempt.
                    <a href="verify-login.php">Resend? </a>
                </div>
                <hr>
                <p class="text-center"><a href="logout.php">Back to Login</a></p>
            </div>
        </div>

        <!-- Bootstrap JS and Popper.js -->
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    </body>
    </html>
    ';

} catch (Exception $e) {
    // Error handling
    echo "Error sending email: {$mail->ErrorInfo}";
}
?>
