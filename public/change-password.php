<?php
require "../private/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Initialize message variable
$message = '';

// Check if reset token is provided in the URL
if (isset($_GET['token'])) {
    $reset_token = $_GET['token'];

    // Fetch the token from the database
    $query = "SELECT email FROM password_reset_tokens WHERE token = :token AND expires_at > NOW() LIMIT 1";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(':token', $reset_token);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        // Token is valid, allow user to change password
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $user_data['email'];
            $old_password = trim($_POST['old_password']);
            $new_password = trim($_POST['new_password']);
            $confirm_password = trim($_POST['confirm_password']);
    
            // Validate password requirements
            $requirements = [
                '/.{8,}/',     // At least 8 characters
                '/[A-Z]/',      // One uppercase letter
                '/[a-z]/',      // One lowercase letter
                '/\d/',         // At least one digit
                '/[\W_]/'       // At least one special character
            ];
    
            $password_valid = true;
            $missing_requirements = [];
    
            foreach ($requirements as $index => $regex) {
                if (!preg_match($regex, $new_password)) {
                    $password_valid = false;
                    switch ($index) {
                        case 0: $missing_requirements[] = "at least 8 characters"; break;
                        case 1: $missing_requirements[] = "one uppercase letter (A-Z)"; break;
                        case 2: $missing_requirements[] = "one lowercase letter (a-z)"; break;
                        case 3: $missing_requirements[] = "at least one number (0-9)"; break;
                        case 4: $missing_requirements[] = "at least one special character (e.g., !@#)"; break;
                    }
                }
            }
    
            // Check if the new password and confirm password match
            if ($new_password !== $confirm_password) {
                $message = '<div class="alert alert-danger" role="alert">New passwords do not match.</div>';
            } elseif (!$password_valid) {
                $message = "<div class='alert alert-danger' role='alert'>Password must include " . implode(", ", $missing_requirements) . ".</div>";

            } else {
                // Fetch the user's current password from the database
                $user_query = "SELECT password, username FROM users WHERE email = :email LIMIT 1";
                $user_stmt = $connection->prepare($user_query);
                $user_stmt->bindParam(':email', $email);
                $user_stmt->execute();
                $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
                $username = $user['username'] ?? '';
    
                // Check if password contains username or email
                if (stripos($new_password, $username) !== false) {
                    $message = '<div class="alert alert-danger" role="alert">Password cannot contain the username.</div>';
                } elseif (stripos($new_password, $email) !== false) {
                    $message = '<div class="alert alert-danger" role="alert">Password cannot contain the email address.</div>';
                } elseif (password_verify($old_password, $user['password'])) {
                    if ($new_password === $old_password) {
                        $message = '<div class="alert alert-danger" role="alert">The new password should not be the same as the old password.</div>';
                    } else {
                        // Hash the new password
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    
                        // Update the password in the database
                        $update_query = "UPDATE users SET password = :new_password WHERE email = :email";
                        $update_stmt = $connection->prepare($update_query);
                        $update_stmt->bindParam(':new_password', $hashed_password);
                        $update_stmt->bindParam(':email', $email);
                        $update_stmt->execute();
    
                        // Send confirmation email
                        $mail = new PHPMailer(true);
                        try {
                            // Server settings
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'testingotp234@gmail.com'; // Your email address
                            $mail->Password = 'lqxy iltd pzqb yrmi'; // Your email app password
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;
    
                            // Recipients
                            $mail->setFrom('no-reply@gmail.com', 'Change Me');
                            $mail->addAddress($email);
    
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Password Changed Successfully';
                            $mail->Body = "Hello, your password has been successfully updated.";
    
                            // Send the email
                            $mail->send();
                            $message = "<div class='alert alert-success' role='alert'>Your password has been changed successfully. You will be logged out in a moment, please log in again with your new password.</div>";
    
                            // Redirect to logout page after 3 seconds
                            echo "<script>
                                setTimeout(() => {
                                    window.location.href = 'logout.php';
                                }, 3000);
                            </script>";
                        } catch (Exception $e) {
                            $message = '<div class="alert alert-danger" role="alert">There was an error sending the confirmation email: ' . $mail->ErrorInfo . '</div>';
                        }
                    }
                } else {
                    $message = '<div class="alert alert-danger" role="alert">The old password is incorrect.</div>';
                }
            }
        }
    } else {
        $message = '<div class="alert alert-danger" role="alert">Invalid or expired token.</div>';
    }
    
} else {
    $message = '<div class="alert alert-danger" role="alert">No reset token found.</div>';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .strength-bar {
            display: flex;
            gap: 4px;
        }
        .strength-bar div {
            flex: 1;
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 3px;
        }
        .strength-bar div.active-weak { background-color: #ff4d4d; }
        .strength-bar div.active-fair { background-color: #ffcc00; }
        .strength-bar div.active-good { background-color: #66cc66; }
        .strength-bar div.active-strong { background-color: #00b33c; }

        .strength-bar, #passwordStrengthMessage, #confirmPasswordMessage {
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .card {
            width: 100%;
            max-width: 500px; /* Increased max-width for better size */
            min-height: 500px; /* Ensures form has enough height for messages */
            overflow-y: auto;
            padding: 20px;
        }

        
        .card-body {
            padding-bottom: 30px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .card-body .form-control {
            margin-bottom: 15px;
        }


        .form-control {
            padding: 10px;
        }


        .password-toggle {
            position: absolute;
            right: 10px;
            padding-bottom:15px;
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
        }

        .input-with-button {
            position: relative;
            display: flex;
            align-items: center;
        }
        

    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color:#eeede7;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="card-title text-center mb-4" style="color:#003155;">Change Your Password</h4>

            <!-- Display message -->
            <?= $message; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="old_password" class="form-label">Old Password</label>
                    <div class="input-with-button">
                        <input type="password" class="form-control" id="old_password" name="old_password" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('old_password')">Show</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="input-with-button">
                        <input type="password" class="form-control" id="new_password" name="new_password" required oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new_password')">Show</button>
                    </div>
                    <div class="strength-bar">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <small id="passwordStrengthMessage" class="form-text"></small>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-with-button">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required oninput="checkPasswordMatch()">
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">Show</button>
                    </div>
                    <small id="confirmPasswordMessage" class="form-text"></small>
                </div>

                <button type="submit" class="btn w-100" style="background-color:#003155; color:#fff; ">Change Password</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPasswordStrength(password) {
            const requirements = [
                /.{8,}/,
                /[A-Z]/,
                /[a-z]/,
                /\d/,
                /[\W_]/
            ];

            const strengthBar = document.querySelectorAll(".strength-bar div");
            const strengthMessage = document.getElementById("passwordStrengthMessage");
            let score = 0;
            let missingRequirements = [];

            if (requirements[0].test(password)) score++;
            else missingRequirements.push("at least 8 characters");

            if (requirements[1].test(password)) score++;
            else missingRequirements.push("one uppercase letter (A-Z)");

            if (requirements[2].test(password)) score++;
            else missingRequirements.push("one lowercase letter (a-z)");

            if (requirements[3].test(password)) score++;
            else missingRequirements.push("at least one number (0-9)");

            if (requirements[4].test(password)) score++;
            else missingRequirements.push("at least one special character (e.g., !@#)");

            strengthBar.forEach(bar => bar.classList.remove("active-weak", "active-fair", "active-good", "active-strong"));

            if (score >= 1) strengthBar[0].classList.add("active-weak");
            if (score >= 2) strengthBar[1].classList.add("active-fair");
            if (score >= 3) strengthBar[2].classList.add("active-good");
            if (score > 4) strengthBar[3].classList.add("active-strong");

            if (score === 5) {
                strengthMessage.textContent = "Strong password.";
                strengthMessage.className = "text-success";
            } else {
                strengthMessage.textContent = "Password must include " + missingRequirements.join(", ");
                strengthMessage.className = "text-danger";
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById("new_password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            const message = document.getElementById("confirmPasswordMessage");
            if (password !== confirmPassword) {
                message.textContent = "Passwords do not match.";
                message.className = "text-danger";
            } else {
                message.textContent = "";
            }
        }

        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const button = passwordInput.nextElementSibling;

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                button.textContent = "Hide";
            } else {
                passwordInput.type = "password";
                button.textContent = "Show";
            }
        }

    </script>
</body>
</html>
