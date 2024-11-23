<?php
require '../2favendor/autoload.php';

use PragmaRX\Google2FA\Google2FA;

// Start session
if (!session_id()) {
    session_start();
}

// Initialize Google2FA
$googleOTP = new Google2FA();

// Retrieve the user data
$user = $_SESSION['user'] ?? null;
if (!$user) {
    $_SESSION['otp_message'] = 'User data not found.';
    header('Location: 2fa.php'); // Redirect if no user data is found
    exit;
}

// Validate OTP
$otp = $_POST['otp'] ?? '';
$isValid = $googleOTP->verifyKey($user['google2fa_secret'], $otp);

if ($isValid) {
    $_SESSION['otp_message'] = 'OTP is correct! Redirecting to the next page...';
    // Set a redirect flag to wait before redirecting
    $_SESSION['redirect_after_delay'] = true;
    header('Location: 2fa.php'); // Redirect to the same page to show the message first
} else {
    $_SESSION['otp_message'] = 'Invalid OTP! Please scan QR again.';
    header('Location: 2fa.php'); // Redirect back to show the error
}
exit;
?>
