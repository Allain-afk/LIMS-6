<?php
session_start();
require 'database/db_connection.php';
require_once 'email_service.php';

$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!empty($email)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $otp = rand(100000, 999999);
                $emailService = new EmailService();
                
                if ($emailService->sendOTP($email, $otp)) {
                    $_SESSION['reset_otp'] = $otp;
                    $_SESSION['reset_user_id'] = $user['user_id'];
                    $_SESSION['reset_email'] = $email;
                    
                    $stmt = $pdo->prepare("INSERT INTO otp (user_id, otp, status) VALUES (?, ?, 0)");
                    $stmt->execute([$user['user_id'], $otp]);
                    
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Email not found']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/forgot-password.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Left Section -->
        <div class="left-section">
            <img src="images/logo.png" alt="Book King Logo">
            <p class="info-text">We'll send a code to your email to reset your password</p>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <h1 class="title">Forgot Password</h1>
            <p class="subtitle">Enter your email to receive a password reset code</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form id="forgotPasswordForm" method="POST">
                <div class="input-container">
                    <input type="email" name="email" class="input-field" placeholder="Enter your email" required>
                </div>

                <button type="submit" class="submit-btn">
                    <span class="submit-btn-text">SEND RESET CODE</span>
                </button>

                <a href="index.php" class="back-to-login">Back to Login</a>
            </form>
        </div>
    </div>

    <div id="successPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <p>Email found! Redirecting to OTP verification...</p>
        </div>
    </div>

    <script>
    document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('forgot-password.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                const popup = document.getElementById('successPopup');
                popup.style.display = 'block';
                
                setTimeout(() => {
                    window.location.href = 'otp2.php';
                }, 3000);
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
    });
    </script>

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
    </style>
</body>
</html>