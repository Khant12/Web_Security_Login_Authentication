<?php
require "../private/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

// Initialize message variable
$message = '';

// Send password reset email using PHPMailer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']); // Retrieve the email from form input

    // Fetch username associated with the email
    $query = "SELECT username, verify_status FROM users WHERE email = :email LIMIT 1";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user){

        if ( $user['verify_status'] == "1") {
            $username = $user['username'];
    
            // Generate a unique token
            $reset_token = bin2hex(random_bytes(32)); // Create a secure token
    
            // Store this token in the database using PDO
            $query = "INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
            $stmt = $connection->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':token', $reset_token);
            $stmt->execute();
    
            // Create the reset link
            $reset_link = "http://localhost/Web_Security_Login_Authentication/public/reset-password.php?token=$reset_token";
    
            // Initialize PHPMailer
            $mail = new PHPMailer(true);
    
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'testingotp234@gmail.com';
                $mail->Password = 'lqxy iltd pzqb yrmi';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
    
                // Recipients
                $mail->setFrom('no-reply@gmail.com', 'Change Me');
                $mail->addAddress($email);
    
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Forget Password Reset Request';
                $mail->Body = "Hello, $username. To reset your password, please click the following link: <a href='$reset_link'>Reset Password</a>";
    
                // Send the email
                $mail->send();
                $message = '<div class="alert alert-success" role="alert">A password reset link has been sent to your email.</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger" role="alert">There was an error sending the password reset email: ' . $mail->ErrorInfo . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger" role="alert">This email is not verified. Please verify your email before resetting the password.</div>';
            
        }

    }else {
        $message = '<div class="alert alert-danger" role="alert">No user found with this email.</div>';
    }
    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            width: 100%;
            max-width: 500px;
            padding: 20px;
            margin-top: 50px;
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color:#eeede7;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="card-title text-center mb-4" style="color: #003155;">Forgot Your Password?</h4>

            <!-- Display success or error message -->
            <?= $message; ?>

            <!-- Forgot Password Form -->
            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Enter your email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <button type="submit" class="btn w-100 mt-3" style="background-color:#003155; color:#fff;">Send Reset Link</button>
            </form>
            <hr>
            <p class="text-center"><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-vDaz6A9muZaJAA2uQ34ttxN5oH7kciQs77JzDSh9hdBzAvTnl95H7k2nxXsuydOh" crossorigin="anonymous"></script>
</body>
</html>
