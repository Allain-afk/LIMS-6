<?php
session_start();
require 'database/db_connection.php';
require_once 'helpers/activity_logger.php';

$message = '';
$messageClass = '';

// Check if reset flow is active
if (!isset($_SESSION['reset_otp']) && !isset($_SESSION['reset_user_id'])) {
    header('Location: forgot-password.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'] ?? '';
    
    if (!empty($entered_otp)) {
        if ($entered_otp == $_SESSION['reset_otp']) {
            // Update OTP status
            $stmt = $pdo->prepare('UPDATE otp SET status = 1 WHERE user_id = ? AND otp = ?');
            $stmt->execute([$_SESSION['reset_user_id'], $_SESSION['reset_otp']]);
            
            // Redirect immediately without showing messages
            header('Location: reset-password.php');
            exit();
        } else {
            $message = 'Invalid OTP. Please try again.';
            $messageClass = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>OTP Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/otp.css">
</head>
<body>
    <div class="container">
        <div class="right-section">
            <img src="images/logo.png" alt="Main Logo" class="main-logo">
            <p class="tagline">"Your premier digital library for borrowing and reading books"</p>
        </div>

        <div class="left-section">
            <h1 class="title">Check your Mailbox</h1>
            <p class="subtitle">Please enter the OTP to proceed</p>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageClass; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-container">
                    <input type="text" name="otp" class="input-field" placeholder="Enter OTP" maxlength="6" required>
                </div>
                
                <button type="submit" class="verify-btn">
                    <span class="verify-btn-text">VERIFY OTP</span>
                </button>

                <a href="forgot-password.php" class="back-to-login" style="color: #B07154; font-size: 13px; text-align: center; text-decoration: none; display: block; margin-top: 20px; opacity: 0.8;">Back to Forgot Password</a>
            </form>
        </div>
    </div>

    <style>
    .popup {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.95);
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        text-align: center;
        border: 2px solid #B07154;
    }
    .popup-content {
        color: #B07154;
        font-family: 'Montserrat', sans-serif;
        font-weight: 500;
    }
    .message {
        position: absolute;
        left: 102px;
        top: 290px;
        color: red;
        font-size: 13px;
    }
    </style>
</body>
</html>