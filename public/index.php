<?php
require "../private/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

// Redirect admin to admin.php if logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit;
}


// Assuming the user is logged in and user_data is fetched from a database
$user_data = check_login($connection);

$username = $user_data->username ?? "";
$email = $user_data->email ?? "";
$password_placeholder = "********"; // Placeholder for password

// Initialize message variable
$message = '';

// Send password reset email using PHPMailer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate a unique token
    $reset_token = bin2hex(random_bytes(32)); // Create a secure token for password reset

    // Store this token in the database using PDO
    $query = "INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':token', $reset_token);
    $stmt->execute();

    // Create the reset link
    $reset_link = "http://localhost/login/public/change-password.php?token=$reset_token";

    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'testingotp234@gmail.com'; // Your Gmail email address
        $mail->Password = 'lqxy iltd pzqb yrmi'; // Your Gmail password or app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('no-reply@gmail.com', 'Change Me');
        $mail->addAddress($email); // Add recipient's email address

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Change Password Request';
        $mail->Body    = "Hello, $username. To change your password, please click the following link: <a href='$reset_link'>Reset Password</a>";

        // Send the email
        $mail->send();
        $message = '<div class="alert alert-success" role="alert">A password reset link has been sent to your email.</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger" role="alert">There was an error sending the password reset email: ' . $mail->ErrorInfo . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Information</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            
        }
        /* Custom logout button style */
        .btn-logout {
            background-color: #dc3545;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-logout:hover {
            background-color: #c82333;
        }

        /* Custom change password button style */
        .btn-change-password {
            background-color: #0a3c61;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-change-password:hover {
            background-color: #003155;
        }

        /* Custom card container style */
        .card {
            max-width: 500px;
            width: 100%;
        }
    </style>
</head>
<body style="background-color:#eeede7;">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#003155;">
        <div class="container">
            <a class="navbar-brand" href="login.php">HONEYPOT</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="signup.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Form Section -->
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh; padding-top: 5rem;">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-center mb-4">User Information</h4>

                <!-- Display success or error message -->
                <?= $message; ?>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($username) ?></p>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($email) ?></p>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <p class="form-control-plaintext"><?= $password_placeholder ?></p>
                </div>

                <!-- Form for sending email for password reset -->
                <form method="POST">
                    <button type="submit" class="btn btn-change-password w-100 mt-3 text-white">Change Password</button>
                </form>

                <a href="logout.php" class="btn btn-logout w-100 mt-3 text-white">Logout</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-vDaz6A9muZaJAA2uQ34ttxN5oH7kciQs77JzDSh9hdBzAvTnl95H7k2nxXsuydOh" crossorigin="anonymous"></script>
</body>
</html>
