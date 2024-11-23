<?php
 
require "../private/autoload.php";

$Error = "";

// Get the user's IP address
$ip_address = getIpAddr();

// Check the login attempts for this IP address
$query = "SELECT attempt_count, last_attempt, lock_time FROM login_attempts WHERE ip_address = :ip_address LIMIT 1";
$stmt = $connection->prepare($query);
$stmt->execute(['ip_address' => $ip_address]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$current_time = time();
$cooldown_period = 300; // (recommended 5 minutes)

// Initialize variables
$attempt_count = 0;
$locked = false;
$remaining_wait_time = 0;

// Modify the lock time check
if ($result) {
    $attempt_count = $result['attempt_count'];
    $lock_time = strtotime($result['lock_time'] ?? '0');

    // Check if the account is currently locked
    if ($attempt_count >= 3 && ($current_time - $lock_time) < $cooldown_period) {
        $locked = true;
        $remaining_wait_time = $cooldown_period - ($current_time - $lock_time);
        $minutes = floor($remaining_wait_time / 60);
        $seconds = $remaining_wait_time % 60;
        $Error = "Your account is temporarily locked.";
    } else {
        // Reset the count if the cooldown period has expired
        if ($attempt_count >= 3 && ($current_time - $lock_time) >= $cooldown_period) {
            $attempt_count = 0;
            $query = "UPDATE login_attempts SET attempt_count = :attempt_count, lock_time = NULL WHERE ip_address = :ip_address";
            $stmt = $connection->prepare($query);
            $stmt->execute(['attempt_count' => $attempt_count, 'ip_address' => $ip_address]);
        }
    }
}

// CSRF token check and login processing
if ($_SERVER['REQUEST_METHOD'] == "POST") {
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

    // CSRF check
    if (isset($_SESSION['token']) && isset($_POST['token']) && $_SESSION['token'] == $_POST['token']) {
        if (!$locked) {
            $email = $_POST['email'];

            // Validate email format
            if (!preg_match("/^[\w\-.]+@[\w\-]+\.[\w\-.]+$/", $email)) {
                $Error = "Please enter a valid email.";
            }

            $password = $_POST['password'];

            if ($Error == "") {
                // Prepare and execute the query to select the user by email
                $arr['email'] = $email;
                $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
                $stm = $connection->prepare($query);
                $check = $stm->execute($arr);

                // Fetch the user data
                $data = $stm->fetch(PDO::FETCH_OBJ);

                if ($data) {
                    // Check if the email is verified
                    if ($data->verify_status != "1") {
                        $Error = "This email is not verified yet.";
                    } elseif (password_verify($password, $data->password)) {
                        // Clear login attempts on successful login
                        $query = "DELETE FROM login_attempts WHERE ip_address = :ip_address";
                        $stmt = $connection->prepare($query);
                        $stmt->execute(['ip_address' => $ip_address]);
                
                        $_SESSION['username'] = $data->username;
                        $_SESSION['url_address'] = $data->url_address;
                        $_SESSION['email'] = $email;
                        $_SESSION['allow_2fa'] = true;
       
                        header("Location: 2fa.php"); // Create a new file for OTP generation
                        exit;
                    } else {
                        // Increment the failed attempt count
                        $attempt_count++;
                        $lock_time = ($attempt_count >= 3) ? date("Y-m-d H:i:s", $current_time) : NULL;
                
                        $query = "INSERT INTO login_attempts (ip_address, attempt_count, last_attempt, lock_time) 
                                  VALUES (:ip_address, :attempt_count, NOW(), :lock_time)
                                  ON DUPLICATE KEY UPDATE 
                                  attempt_count = :attempt_count, 
                                  last_attempt = NOW(),
                                  lock_time = :lock_time";
                
                        $stmt = $connection->prepare($query);
                        $stmt->execute([ 
                            'ip_address' => $ip_address, 
                            'attempt_count' => $attempt_count, 
                            'lock_time' => $lock_time
                        ]);
                
                        if ($attempt_count >= 3) {
                            // Lock the account
                            $locked = true;
                            $remaining_wait_time = $cooldown_period - ($current_time - strtotime($lock_time));
                            $minutes = floor($remaining_wait_time / 60);
                            $seconds = $remaining_wait_time % 60;
                            $Error = "Your account is temporarily locked.";
                            
                        } else {
                            $Error = "Wrong password. You have " . (3 - $attempt_count) . " attempt(s) remaining.";
                        }
                    }
                } else {
                    // User not found
                    $Error = "No user found with this email.";
                }
                
            }
        }
    }
}

// Generate a new CSRF token for the form
$_SESSION['token'] = get_random_string(60);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color:#eeede7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 50vh;
            margin: 0;
            flex-direction: column; /* Aligns navbar above the form */
        }

        .password-container {
            position: relative;
        }
        .login-form {
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
        }
        
        .input-field {
            height: 45px;
            font-size: 16px;
            border-radius: 5px;
            padding: 12px;
            width: 100%;
        }
        .input-field:focus {
            outline: none;
            border-color: #007bff;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }

        .message {
            font-size: 1em;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 20px auto;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid green;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 0.9em;
            text-align: center;
            margin: 15px 0;
        }
        #countdown {
            margin-top: 10px;
            color: #333;
            font-size: 0.9em;
            text-align: center;
        }
   

        .form-control {
            height: 45px;
        }


        .input-with-button {
            position: relative;
            display: flex;
            align-items: center;
        }
		

         /* Additional styles for the navbar */
         .navbar {
            width: 100%;
            margin-bottom: 80px;
          
            
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            padding-top:5px;
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
        }


    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark w-100"  style="background-color:#003155;">
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
    <!-- Login Form -->
    <div class="card login-form">
        <h4 class="card-title text-center text-white p-3" style="background-color: #003155; margin-bottom:20px;">Login</h4>

        <?php if (isset($_SESSION['status'])) : ?>
            <div class="message success"><?= $_SESSION['status']; unset($_SESSION['status']); ?></div>
        <?php endif; ?>

        <?php if ($Error): ?>
            <div class="error-message"><?= $Error ?></div>
            <div id="countdown"></div>
        <?php endif; ?>


        <form action="" method="POST">
            <div class="mb-3">
                <input type="email" name="email" class="input-field form-control" placeholder="Email" required>
            </div>
            <div class="mb-3 input-with-button">
                <input id="password" class="input-field form-control" type="password" name="password" placeholder="Password" required >
                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password')">Show</button>
            </div>
            <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="6LdRhHIqAAAAAFkhBKmFxCfXrfrKWVJAtcaFRE44"></div>
            </div>
            <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
            <button type="submit" class="btn w-100"  style="background-color:#003155; color:#fff; margin-top:10px;" >Login</button>
        </form>
       
         <!-- Link to verification email Page -->
        
         <p class="mt-5">Forget password?  <a href="forget-password.php">Click here</a></p>
         <p class="mt-1">Resend verify? <a href="resend-email-verification.php">Click here</a></p>
         
    </div>

    <script>
        function startCountdown(waitTime) {
        let countdownElement = document.getElementById('countdown');
        let countdown = waitTime;
        let inputFields = document.querySelectorAll('.input-field');

        inputFields.forEach(input => input.disabled = true); // Disable inputs during countdown

        let interval = setInterval(function() {
            if (countdown <= 0) {
                clearInterval(interval);
                countdownElement.textContent = "You can login again.";
                inputFields.forEach(input => input.disabled = false); // Enable inputs
            } else {
                let minutes = Math.floor(countdown / 60);
                let seconds = countdown % 60;
                countdownElement.textContent = "Please wait " + minutes + " minute(s) and " + seconds + " second(s).";
                countdown--;
            }
        }, 1000);
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

       

        <?php if ($locked): ?>
            startCountdown(<?= $remaining_wait_time ?>);
        <?php endif; ?>
    </script>
</body>
</html>
