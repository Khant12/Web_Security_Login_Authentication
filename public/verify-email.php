<?php

require "../private/autoload.php";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Prepare the query
    $verify_query = "SELECT verify_token, verify_status FROM users WHERE verify_token = :token LIMIT 1";
    $stmt = $connection->prepare($verify_query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo $row['verify_token'];

        if ($row['verify_status'] == "0") {
            // Update the verify status
            $update_query = "UPDATE users SET verify_status = '1' WHERE verify_token = :token LIMIT 1";
            $update_stmt = $connection->prepare($update_query);
            $update_stmt->bindParam(':token', $token);
            $update_success = $update_stmt->execute();

            if ($update_success) {
                $_SESSION['status'] = "Your Account has been verified successfully!";
                header("Location: login.php");
                exit(0);
            } else {
                $_SESSION['status'] = "Verification failed!";
                header("Location: login.php");
                exit(0);
            }
        } else {
            $_SESSION['status'] = "Email is already verified. Please log in.";
            header("Location: login.php");
            exit(0);
        }
    } else {
        $_SESSION['status'] = "This Token does not exist";
        header("Location: login.php");
        exit(0);
    }
} else {
    $_SESSION['status'] = "Not Allowed";
    header("Location: login.php");
    exit(0);
}
?>
