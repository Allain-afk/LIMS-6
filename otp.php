<?php
session_start();

// Add back button handling
if (isset($_GET['back']) && $_GET['back'] == '1') {
    if (isset($_SESSION['pending_otp'])) {
        $_SESSION['from_back'] = true;
    }
    header('Location: signup.php');
    exit();
}

require 'database/db_connection.php';
require_once 'helpers/activity_logger.php';

$message = '';
$messageClass = '';

// Add this condition at the top of the file
if (isset($_SESSION['reset_otp'])) {
    $stored_otp = $_SESSION['reset_otp'];
    $user_id = $_SESSION['reset_user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $entered_otp = $_POST['otp'] ?? '';
        
        if ($entered_otp == $stored_otp) {
            // Show success popup and redirect
            echo '<script>
                setTimeout(() => {
                    const popup = document.createElement("div");
                    popup.className = "popup";
                    popup.innerHTML = `
                        <div class="popup-content">
                            <p>OTP verified! Redirecting to password reset...</p>
                        </div>
                    `;
                    document.body.appendChild(popup);
                    
                    setTimeout(() => {
                        window.location.href = "reset-password.php";
                    }, 3000);
                }, 500);
            </script>';
        }
    }
}

// Check if pending OTP exists in session
if (!isset($_SESSION['pending_otp']) || !isset($_SESSION['pending_user_id'])) {
    header('Location: signup.php');
    exit();
}

// Check if user is already verified
if (isset($_SESSION['verified']) && $_SESSION['verified'] === true) {
    header('Location: user-dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'] ?? '';
    
    if (!empty($entered_otp)) {
        try {
            // Get the user_id from session
            $user_id = $_SESSION['pending_user_id'];
            $stored_otp = $_SESSION['pending_otp'];
            
            if ($user_id && $stored_otp) {
                if ($entered_otp == $stored_otp) {
                    // Update OTP status
                    $update_stmt = $pdo->prepare('UPDATE otp SET status = 1 WHERE user_id = :user_id AND otp = :otp');
                    $update_stmt->execute([
                        'user_id' => $user_id,
                        'otp' => $stored_otp
                    ]);
                    
                    // Set success message in session
                    $_SESSION['success'] = 'Account Created Successfully';
                    
                    // Clear verification session data
                    unset($_SESSION['pending_otp']);
                    unset($_SESSION['pending_user_id']);
                    unset($_SESSION['user_id']);
                    unset($_SESSION['username']);
                    unset($_SESSION['first_name']);
                    unset($_SESSION['last_name']);
                    
                    // Redirect to login page
                    header('Location: index.php');
                    exit();
                } else {
                    $message = 'Invalid OTP. Please try again.';
                    $messageClass = 'error';
                }
            } else {
                $message = 'Session expired. Please try again.';
                $messageClass = 'error';
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $message = 'Error verifying OTP. Please try again.';
            $messageClass = 'error';
        }
    } else {
        $message = 'Please enter the OTP.';
        $messageClass = 'error';
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
                    <input type="text" name="otp" class="input-field" placeholder="OTP" required>
                </div>
                
                <button type="submit" class="verify-btn">
                    <span class="verify-btn-text">VERIFY</span>
                </button>

                <a href="otp.php?back=1" class="back-to-login" style="color: #B07154; font-size: 13px; text-align: center; text-decoration: none; display: block; margin-top: 20px; opacity: 0.8;">Back to Sign Up</a>
            </form>
        </div>
    </div>
</body>
</html>