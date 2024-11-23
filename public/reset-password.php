<?php
require "../private/autoload.php";


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';


// Check if token is in the URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verify token exists in the database and is still valid
    $query = "SELECT * FROM password_reset_tokens WHERE token = :token AND expires_at > NOW() LIMIT 1";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($token_data) {
        // Token is valid
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

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

            // Fetch user's email for comparison
            $email = $token_data['email'];
            $query = "SELECT username FROM users WHERE email = :email LIMIT 1";
            $stmt = $connection->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $username = $user_data['username'] ?? '';

            // Check if password contains username or email
            if (stripos($new_password, $username) !== false) {
                $error = "Password cannot contain the username.";
                $password_valid = false;
            } elseif (stripos($new_password, $email) !== false) {
                $error = "Password cannot contain the email address.";
                $password_valid = false;
            }

            if (!$password_valid) {
                if (empty($error)) {
                    $error = "Password must include " . implode(", ", $missing_requirements) . ".";
                }
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                // Update the user's password in the database
                $update_query = "UPDATE users SET password = :password WHERE email = :email";
                $update_stmt = $connection->prepare($update_query);
                $update_stmt->bindParam(':password', $hashed_password);
                $update_stmt->bindParam(':email', $email);

                if ($update_stmt->execute()) {
                    // Send confirmation email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'testingotp234@gmail.com';
                        $mail->Password = 'lqxy iltd pzqb yrmi';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('no-reply@gmail.com', 'Change Me');
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Changed Successfully';
                        $mail->Body = 'Your password has been successfully changed. If you did not request this change, please contact support immediately.';
                        $mail->send();
                    } catch (Exception $e) {
                        $error = "Error sending email: " . $mail->ErrorInfo;
                    }

                    // Delete the reset token from the database
                    $delete_query = "DELETE FROM password_reset_tokens WHERE token = :token";
                    $delete_stmt = $connection->prepare($delete_query);
                    $delete_stmt->bindParam(':token', $token);
                    $delete_stmt->execute();

                    // Clear the session and redirect to login
                    session_start();
                    $_SESSION['status'] = "Password changed successfully. Please log in with your new password.";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "There was an error updating your password.";
                }
            }
        }
    } else {
        // Token is invalid or expired
        $error = "Invalid or expired token.";
    }
} else {
    // Token not provided in URL
    $error = "No token provided.";
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
            margin-bottom: 25px;
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

        /* Increased padding for input fields to make them more spacious */
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
            <h4 class="card-title text-center mb-4" style="color: #003155;">Reset Your Password</h4>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Password Reset Form -->
            <form method="POST">
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="input-with-button ">
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
                    <div class="input-with-button ">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required oninput="checkPasswordMatch()">
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">Show</button>
                    </div>
                    <small id="confirmPasswordMessage" class="form-text"></small>
                </div>

                <button type="submit" class="btn w-100 mt-3" style="background-color:#003155; color:#fff;">Reset Password</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-vDaz6A9muZaJAA2uQ34ttxN5oH7kciQs77JzDSh9hdBzAvTnl95H7k2nxXsuydOh" crossorigin="anonymous"></script>
    <script>
        function checkPasswordStrength(password, username = "", email = "") {
            const requirements = [
                /.{8,}/, // At least 8 characters
                /[A-Z]/, // One uppercase letter
                /[a-z]/, // One lowercase letter
                /\d/,    // At least one number
                /[\W_]/  // At least one special character
            ];

            const strengthBar = document.querySelectorAll(".strength-bar div");
            const strengthMessage = document.getElementById("passwordStrengthMessage");
            let score = 0;
            let missingRequirements = [];

            
            // Evaluate password strength
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
            
            // Update the bar based on the score
            if (score >= 1) strengthBar[0].classList.add("active-weak");
            if (score >= 2) strengthBar[1].classList.add("active-fair");
            if (score >= 3) strengthBar[2].classList.add("active-good");
            if (score > 4) strengthBar[3].classList.add("active-strong");

            // Provide dynamic password strength message
            if (score === 0) {
                strengthMessage.textContent = `Password must include ${missingRequirements.join(", ")}.`;
                strengthMessage.className = "text-danger";
            } else if (score < 5) {
                strengthMessage.textContent = `Still missing: ${missingRequirements.join(", ")}.`;
                strengthMessage.className = "text-warning";
            } else if (score === 5) {
                strengthMessage.textContent = "Strong password! You're good to go.";
                strengthMessage.className = "text-success";
            }
        
        }
        // Triggered when password changes
        document.getElementById("password").addEventListener("input", function() {
            checkPasswordStrength(this.value, document.getElementById('username').value, document.getElementById('email').value);
        });


        function checkPasswordMatch() {
            const password = document.getElementById("new_password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            const message = document.getElementById("confirmPasswordMessage");
            if (password !== confirmPassword) {
                message.textContent = "Passwords do not match.";
                message.className = "text-danger";
            } else {
                message.textContent = "Passwords match.";
                message.className = "text-success";
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
