<?php
$page_title = "Login Form";
require "../private/autoload.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Reset basic styling */
        * { 
            box-sizing: border-box; 
            font-family: Arial, sans-serif; 
        }

        /* Body styling */
        body {
            font-family: 'Arial', sans-serif;
            background-color:#eeede7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .container { 
            max-width: 450px; 
            padding: 20px; 
            background-color: #fff; 
            border-radius: 8px; 
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); 
            text-align: center; 
        }

        /* Message styling */
        .message { 
            margin-top: 1em; 
            padding: 12px; 
            border-radius: 5px; 
            font-weight: bold; 
        }

        .message.success { 
            background-color: #d4edda; 
            color: #155724; 
        }

        .message.error { 
            background-color: #f8d7da; 
            color: #721c24; 
        }
    </style>
</head>
<body>
    
    <div class="card container">
        <h4 class="card-title text-center p-3"  style="color: #003155;">Resend Verification Email</h4>
        
        <?php if (isset($_SESSION['status'])): ?>
            <div class="message <?= strpos($_SESSION['status'], 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php 
                    echo $_SESSION['status']; 
                    unset($_SESSION['status']);
                ?>
            </div>
        <?php endif; ?>

        <form action="resend-verify.php" method="POST">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <button type="submit" name="resend-email-verify-btn" class="btn w-100"  style="background-color: #003155; color:#fff;">Resend</button>
        </form>
        <hr>
        <p class="text-center"><a href="login.php">Back to Login</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
