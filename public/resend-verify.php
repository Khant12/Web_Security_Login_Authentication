<?php

require "../private/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

function resend_email_verify($username, $email, $verify_token)
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->SMTPAuth   = true;
    $mail->Host       = "smtp.gmail.com";
    $mail->Username   = "testingotp234@gmail.com";
    $mail->Password   = "lqxy iltd pzqb yrmi";
    $mail->SMTPSecure = "tls";
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom("no-reply@gmail.com", "Verify Me");
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "Resend - Email Verification";
    $email_template = "
    <h2>Verify Your Email To Login</h2>
    <h5>Click the below link</h5>
    <br/><br/>
    <a href='http://localhost/login/public/verify-email.php?token=$verify_token'> Verify Account</a>
    ";

    $mail->Body = $email_template;
    $mail->send();
}

if (isset($_POST['resend-email-verify-btn'])) {
    if (!empty(trim($_POST['email']))) {

        $email = $_POST['email'];
        $email_quoted = $connection->quote($email); // Quote the email for PDO
        $checkemail_query = "SELECT * FROM users WHERE email=$email_quoted LIMIT 1";
        $checkemail_query_run = $connection->query($checkemail_query);

        if ($checkemail_query_run && $checkemail_query_run->rowCount() > 0) {
            $row = $checkemail_query_run->fetch(PDO::FETCH_ASSOC);

            if ($row['verify_status'] == "0") {
                $name = $row['name'];
                $email = $row['email'];
                $verify_token = $row['verify_token'];

                resend_email_verify($username, $email, $verify_token);

                $_SESSION['status'] = "Verification Email link has been sent to your email address";
                header("Location: login.php");
                exit(0);
            } else {
                $_SESSION['status'] = "Email is already verified. Please Login";
                header("Location: resend-email-verification.php");
                exit(0);
            }
        } else {
            $_SESSION['status'] = "Email is not registered. Please Register now!";
            header("Location: signup.php");
            exit(0);
        }

    } else {
        $_SESSION['status'] = "Please enter the email field";
        header("Location: resend-email-verification.php");
        exit(0);
    }
}

?>
