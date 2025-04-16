<?php
session_start();
require 'database/db_connection.php';

if (!isset($_SESSION['reset_user_id'])) {
    header('Location: forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($newPassword === $confirmPassword) {
        try {
            $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE user_id = :user_id');
            $stmt->execute([
                'password' => $newPassword,
                'user_id' => $_SESSION['reset_user_id']
            ]);
            
            // Clear reset session data
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_email']);
            
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/reset-password-new.css">
</head>
<body>
    <div class="container">
        <div class="left-section">
            <img src="images/logo.png" alt="Main Logo" class="main-logo">
            <p class="tagline">"Your premier digital library for borrowing and reading books"</p>
        </div>
        
        <div class="right-section">
            <h1 class="title">Reset Password</h1>
            <p class="subtitle">Please enter your new password</p>
            
            <form id="resetPasswordForm" method="POST">
                <div class="input-container">
                    <input type="password" name="new_password" class="input-field" placeholder="New Password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <div class="input-container">
                    <input type="password" name="confirm_password" class="input-field" placeholder="Confirm Password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit" class="reset-btn">
                    <span class="reset-btn-text">RESET PASSWORD</span>
                </button>
            </form>
        </div>
    </div>

    <div id="successPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <p>Password reset successful! Redirecting to login...</p>
        </div>
    </div>

    <script>
    function togglePassword(inputName) {
        const input = document.querySelector(`input[name="${inputName}"]`);
        const icon = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('reset-password.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                const popup = document.getElementById('successPopup');
                popup.style.display = 'block';
                
                setTimeout(() => {
                    window.location.href = 'index.php';
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