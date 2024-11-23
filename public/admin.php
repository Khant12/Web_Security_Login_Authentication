<?php
require "../private/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}


// Fetch all users from the database (this logic is only accessible for admin)
$query = "SELECT * FROM users";
$stmt = $connection->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no users are found, redirect to login page
if (count($data) == 0) {
    header("Location: login.php");
    exit;
}

// Initialize message variable
$message = '';

// Send password reset email using PHPMailer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user ID is selected
    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        $user_id = $_POST['user_id'];

        // Fetch the user's email from the database using the selected user ID
        $query = "SELECT email FROM users WHERE id = :user_id";
        $stmt = $connection->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists
        if ($user) {
            $email = $user['email'];

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
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "Hello, to reset your password, please click the following link: <a href='$reset_link'>Reset Password</a>";

                // Send the email
                $mail->send();
                $message = '<div class="alert alert-success" role="alert">A password reset link has been sent to <strong>' .$email. '</strong></div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger" role="alert">There was an error sending the password reset email: ' . $mail->ErrorInfo . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger" role="alert">User not found.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger" role="alert">Please select a user to reset the password.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .btn-change-password:hover {
            background-color: #23272b;
        }
        body{
            background-color:#eeede7;
        }

        /* Custom logout button style */
        .btn-logout {
            background-color: #dc3545;
            border: none;
            width: 150px;
            margin-left: 200px;
            padding: 10px 15px;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-logout:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
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

    <!-- Admin Dashboard -->
    <div class="container mt-5">
        <h2 class="text-center mb-4">Admin Dashboard</h2>

        <div class="card shadow-sm">
            <div class="card-body">
                <h4>User Information</h4>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Password Hash</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($data as $user): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><pre><?= htmlspecialchars($user['password']) ?></pre></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-warning">Change Password</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mt-3">
                <?= $message ?>
            </div>
        <?php endif; ?>

    </div>
    <a href="logout.php" class="btn btn-logout  mt-3 text-white">Logout</a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
