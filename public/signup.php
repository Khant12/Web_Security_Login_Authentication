<?php
require "../private/autoload.php";

$Error = "";
$email = "";
$username = "";
$confirmPass = "";
$verify_token = md5(rand());



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

function sendemail_verify($name, $email, $verify_token){
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->Host = "smtp.gmail.com";
    $mail->Username = "testingotp234@gmail.com";
    $mail->Password = "lqxy iltd pzqb yrmi";
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;

    $mail->setFrom("no-reply@gmail.com", "Verify Me");
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Email Verification";
    $email_template = "
     <h2>Verify Your Email To Login</h2>
     <h5>Click the link below to verify your account:</h5>
     <br/><br/>
     <a href='http://localhost/login/public/verify-email.php?token=$verify_token'> Verify Account</a>
    ";

    $mail->Body = $email_template;
    $mail->send();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $captcha_secret = '6LdRhHIqAAAAAI9Pd3gYEm9kz90GvHhTgXbUb1Qs'; 
    $captcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : null;

    if (empty($captcha_response)) {
        $Error = "Please complete the CAPTCHA";
    } else {
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$captcha_secret}&response={$captcha_response}");
        $response_keys = json_decode($response, true);

        if (intval($response_keys["success"]) !== 1) {
            $Error = "CAPTCHA validation failed. Please try again.";
        }
    }


    // Fetch password, username, and email from form
    $password = $_POST['password'];
    $username = $_POST['username'];  // Assuming the username is also sent in the form
    $email = $_POST['email'];        // Assuming the email is also sent in the form

    // Password requirements
    $minLength = 8;
    $uppercase = "/[A-Z]/";
    $lowercase = "/[a-z]/";
    $number = "/[0-9]/";
    $specialChar = "/[!@#$%^&*(),.?\":{}|<>]/";

    // Check if password contains username or email
    if (stripos($password, $username) !== false) {
        $Error = "Password cannot contain the username.";
    } elseif (stripos($password, $email) !== false) {
        $Error = "Password cannot contain the email address.";
    }

    // Check each password requirement and add error messages as needed
    elseif (strlen($password) < $minLength) {
        $Error = "Password must be at least 8 characters long.";
    } elseif (!preg_match($uppercase, $password)) {
        $Error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match($lowercase, $password)) {
        $Error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match($number, $password)) {
        $Error = "Password must contain at least one number.";
    } elseif (!preg_match($specialChar, $password)) {
        $Error = "Password must contain at least one special character.";
    }
}

if($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];
    if(!preg_match("/^[\w\-.]+@[\w\-]+\.[\w\-.]+$/", $email)){
        $Error = "Please enter a valid email";
    }
    $date = date("Y-m-d H:i:s");
    $url_address = get_random_string(60);

    $username = trim($_POST['username']);
    if (strlen($username) > 30) {
        $Error = "Username cannot exceed 30 characters.";
    } elseif (!preg_match("/^[a-zA-Z0-9._]+$/", $username)) {
        $Error = "Username can only include letters, numbers, periods, and underscores.";
    } elseif (strpos($username, '@gmail.com') !== false) {
        $Error = "Username cannot be a Gmail address.";
    }

    $username = esc($username);
    $password = esc($_POST['password']);
    $confirmPass = esc($_POST['confirmpass']);

    if($password !== $confirmPass){
        $Error = "Passwords don't match";
    }

    if($Error == "") {
        $arr = ['email' => $email];
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stm = $connection->prepare($query);
        $check = $stm->execute($arr);

        if($check){
            $data = $stm->fetchAll(PDO::FETCH_OBJ);
            if(is_array($data) && count($data) > 0){
                $Error = "This email is already used";
            }
        } else {
            $Error = "Database error. Please try again.";
        }
    }

    if($Error == "") {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $arr = [
            'url_address' => $url_address,
            'date' => $date,
            'username' => $username,
            'password' => $hashedPassword,
            'email' => $email,
            'verify_token' => $verify_token
        ];

        $query = "INSERT INTO users (url_address, username, password, email, verify_token, date) VALUES (:url_address, :username, :password, :email, :verify_token, :date)";
        $stm = $connection->prepare($query);
        $save = $stm->execute($arr);

        if ($save) {
            sendemail_verify($username, $email, $verify_token);
            $_SESSION['status'] = "Registration successful. Please verify your Email address";
            $_SESSION['status_type'] = 'alert-success';
            header("Location: login.php");
            die;
        } else {
            $_SESSION['status'] = "Registration Failed";
            $_SESSION['status_type'] = 'alert-danger';
            header("Location: signup.php");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Elegant Signup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>

        body{
            flex-direction: column; /* Aligns navbar above the form */
            font-family: 'Arial', sans-serif;
            background-color:#eeede7;
           
        }

         /* Additional styles for the navbar */
         .navbar {
            width: 100%;
            margin-bottom: 20px;
            
        }


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


        .signup-form {
            max-width: 480px;
            width: 100%;
            padding: 30px;
            margin: auto;
            background-color: #fff;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .signup-form h4 {
            font-size: 24px;
            text-align: center;
        }

        .signup-form input {
            height: 45px;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .strength-bar, #passwordStrengthMessage, #confirmPasswordMessage {
			margin-bottom: 20px;
			font-size:14px;
        }

        .password-container {
            position: relative;
        }
        .input-with-button {
            position: relative;
            display: flex;
            align-items: center;
        }
        .form-control {
            width: 100%;
            padding-right: 50px; /* Add space for the button */
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            background: none;
            padding-bottom:15px;
            border: none;
            color: #007bff;
            cursor: pointer;
        }

		.error-message {
            color: #e74c3c;
            font-size: 0.9em;
            text-align: center;
            margin: 15px 0;
        }

    </style>
    <script>
        
        function checkPasswordStrength(password, username, email) {
            const requirements = [
                /.{8,}/,         // At least 8 characters
                /[A-Z]/,         // One uppercase letter
                /[a-z]/,         // One lowercase letter
                /\d/,            // At least one number
                /[\W_]/          // At least one special character
            ];

            const strengthBar = document.querySelectorAll(".strength-bar div");
            const strengthMessage = document.getElementById("passwordStrengthMessage");
            let score = 0;
            let missingRequirements = [];
            
            // Reset the bar and message on each password change
            strengthBar.forEach(bar => bar.className = "");
            strengthMessage.textContent = "";
            strengthMessage.classList.remove("text-success", "text-warning", "text-danger");

            // Check if password contains username or email
         
            if (password.toLowerCase().includes(username.toLowerCase())) {
                strengthMessage.textContent = `Password cannot contain the username.`;
                strengthMessage.className = "text-danger";
                return;
            }
            if (email && password.toLowerCase().includes(email.toLowerCase())) {
                strengthMessage.textContent = `Password cannot contain the email address.`;
                strengthMessage.className = "text-danger";
                return;
            }

            // Check each requirement
            if (requirements[0].test(password)) score++; // Length requirement
            else missingRequirements.push("at least 8 characters");

            if (requirements[1].test(password)) score++; // Uppercase requirement
            else missingRequirements.push("one uppercase letter (A-Z)");

            if (requirements[2].test(password)) score++; // Lowercase requirement
            else missingRequirements.push("one lowercase letter (a-z)");

            if (requirements[3].test(password)) score++; // Number requirement
            else missingRequirements.push("at least one number (0-9)");

            if (requirements[4].test(password)) score++; // Special character requirement
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


		function checkConfirmPassword() {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmpass").value;
            const confirmPasswordMessage = document.getElementById("confirmPasswordMessage");

            if (password && confirmPassword) {
                if (confirmPassword === password) {
                    confirmPasswordMessage.textContent = "✔ Passwords match!";
                    confirmPasswordMessage.classList.add("text-success");
                    confirmPasswordMessage.classList.remove("text-danger");
                } else {
                    confirmPasswordMessage.textContent = "✖ Passwords do not match!";
                    confirmPasswordMessage.classList.add("text-danger");
                    confirmPasswordMessage.classList.remove("text-success");
                }
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
</head>
<body class="d-flex justify-content-center align-items-center min-vh-100">
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
                        <a class="nav-link " href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="signup.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="card signup-form">
        <h4 class="card-title text-center p-3 mb-3" style="background-color: #003155; color:#fff;">Signup</h4>
        <form method="POST">
            <div class="form-group">
            <?php if(!empty($Error)): ?>
                <div class="error-message">
                    <?php echo $Error; ?>
                </div>
            <?php endif; ?>
                <input type="text" name="username" id="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="form-group password-container">
                <div class="input-with-button">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required onkeyup="checkPasswordStrength(this.value, document.getElementById('username').value, document.getElementById('email').value)">
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password')">Show</button>
                </div>
                <div class="strength-bar">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
                <div id="passwordStrengthMessage"></div>
            </div>
            <div class="form-group password-container">
                <div class="input-with-button">
                    <input type="password" name="confirmpass" id="confirmpass" class="form-control" placeholder="Confirm Password" required onkeyup="checkConfirmPassword()">
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirmpass')">Show</button>
                </div>
                <div id="confirmPasswordMessage"></div>
            </div>
                
            <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="6LdRhHIqAAAAAFkhBKmFxCfXrfrKWVJAtcaFRE44"></div>
            </div>
            <button type="submit" class="btn"  style="background-color:#003155; color:#fff; ">Register</button>
            <hr>
            <p  class="mt-3">Didn't receive verification email?
            <a href="resend-email-verification.php">Resend</a></p>
        </form>
       
    </div>
</body>
</html>
