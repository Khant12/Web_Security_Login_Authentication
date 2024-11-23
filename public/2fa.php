<?php
session_start();
require '../2favendor/autoload.php';

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;

// Start Session
if(!session_id())
{
    session_start();
}


// Restrict access if the user has not logged in
if ((!isset($_SESSION['allow_2fa'])) || $_SESSION['allow_2fa'] !== true) {
    // Redirect to login.php if the session is not set
    header('Location: login.php');
    exit();
}



// Initialize
$googleOTP = new Google2FA();

// Generate a secret key
$user = [
    'google2fa_secret' => $googleOTP->generateSecretKey(), 
    'email' => 'honeypot@gmail.com'
];

// Store user data in the session
$_SESSION['user'] = $user;

// Provide name of app
$app_name = 'Honeypot Google OTP';

// Generate a custom URL from user data
$qrCodeUrl = $googleOTP->getQRCodeUrl($app_name, $user['email'], $user['google2fa_secret']);

// Generate QR Code image with GD
$imageSize = 250;
$writer = new Writer(
    new GDLibRenderer($imageSize)
);

// Create a string with the image base64 data
$encoded_qr_data = base64_encode($writer->writeString($qrCodeUrl));

// Check if a message exists
$message = $_SESSION['otp_message'] ?? '';



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Authenticator with PHP</title>

    <!-- Bootstrap CDN -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-pzjw8f+ua7Kw1TIq0Yo+zZjXnpb/m/zrCp2P1ydFdAfmH/jhMlTjuyVp0z7fF1F5" crossorigin="anonymous">

    <!-- Custom CSS (Optional) -->
    <style>
        body {
            background-color:#eeede7;
            font-family: 'Arial', sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;

        }
        .card {
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
            text-align: center;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-size: 16px;
            margin-top:35px;
        
            width: 30%;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .qr-code {
            margin-top: 20px;
            text-align: center;
        }
        h1, h2 {
            color: #333;
            font-size: 24px;
        }
        .form-group input {
            font-size: 16px;
            padding: 8px;
        }

        .alert { margin-top: 20px; }
        /* Success and Error Message Styles */
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
    
       
    </style>
</head>
<body>
    

<div class="container">

    <div class="card p-4">
        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'correct') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <script>
                setTimeout(function () {
                    window.location.href = "<?php echo strpos($message, 'correct') !== false ? 'verify-login.php' : '2fa.php'; ?>";
                }, 2000); // Redirect after 3 seconds
            </script>
        <?php unset($_SESSION['otp_message']); ?>
        <?php endif; ?>


        <h1 class="mb-4">Scan QR Code with Google Authenticator</h1>
        
        <div class="qr-code">
            <img src="data:image/png;base64,<?php echo $encoded_qr_data; ?>" alt="QR Code" class="img-fluid"/>
        </div>

        <h2 class="mt-4">Verify OTP</h2>
        <form action="verify-2fa.php" method="POST">
            <div class="form-group">
                <label for="otp">Enter OTP Code</label>
                <input type="text" name="otp" id="otp" class="form-control" placeholder="Input OTP code" required>
            </div>
            <button type="submit" class="btn btn-custom">Verify</button>
        </form>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zyW9qXzLnhY8t7fF3zD40vPp6v6F5lhzj5swks2M" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js" integrity="sha384-pzjw8f+ua7Kw1TIq0Yo+zZjXnpb/m/zrCp2P1ydFdAfmH/jhMlTjuyVp0z7fF1F5" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-pzjw8f+ua7Kw1TIq0Yo+zZjXnpb/m/zrCp2P1ydFdAfmH/jhMlTjuyVp0z7fF1F5" crossorigin="anonymous"></script>
</body>
</html>