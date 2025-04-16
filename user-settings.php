<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
$pdo = require 'database/db_connection.php';

// Get error message if any
$errorMessage = '';
$successMessage = '';
if (isset($_SESSION['update_error'])) {
    $errorMessage = htmlspecialchars($_SESSION['update_error']);
    unset($_SESSION['update_error']);
}
if (isset($_SESSION['update_success'])) {
    $successMessage = htmlspecialchars($_SESSION['update_success']);
    unset($_SESSION['update_success']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/user-settings.css">
</head>

<body>
    <div class="settings-container">
        <div class="settings-header">
            <h1>Change Credentials</h1>
        </div>
        <div class="divider"></div>

        <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="success-message"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <form id="settingsForm" action="update_user_settings.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="currentPassword">Enter Current Password</label>
                <div class="input-container">
                    <input type="password" id="currentPassword" name="currentPassword" required>
                    <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('currentPassword', this)"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="newPassword">Enter New Password</label>
                <div class="input-container">
                    <input type="password" id="newPassword" name="newPassword" required>
                    <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('newPassword', this)"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <div class="input-container">
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                    <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
                </div>
            </div>

            <div class="button-group">
                <button type="button" class="cancel-btn" onclick="window.location.href='user-dashboard.php'">CANCEL</button>
                <button type="submit" class="confirm-btn">CONFIRM</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            var newPassword = document.getElementById('newPassword').value;
            var confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>

</html>