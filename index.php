<?php
// Start output buffering at the very beginning
ob_start();
session_start();

// Include the database connection
$pdo = require 'database/db_connection.php';

// Check for error messages
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// Debug login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST request received on index.php');
    error_log('POST data: ' . print_r($_POST, true));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    error_log('Login form submitted');
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            // Check if the user exists in the database
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password using password_verify()
            if ($user && password_verify($password, $user['password'])) {
                // Check if the user has verified their email
                $stmt = $pdo->prepare('SELECT * FROM otp WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
                $stmt->execute(['user_id' => $user['user_id']]);
                $otp = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$otp || $otp['status'] == 0) {
                    // Store user data in session for OTP verification
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['first_name'] = $user['FirstName'];
                    $_SESSION['last_name'] = $user['LastName'];
                    $_SESSION['error'] = 'Please verify your email first';
                    
                    header('Location: otp.php');
                    exit();
                } else {
                    // Store user data in session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['first_name'] = $user['FirstName'];
                    $_SESSION['last_name'] = $user['LastName'];
                    $_SESSION['verified'] = true;

                    ob_end_clean();
                    header('Location: user-dashboard.php');
                    exit();
                }
            } else {
                // If the password doesn't verify with the hash, but matches directly,
                // it means we need to update this password
                if ($user && $password === $user['password']) {
                    // Update this user's password to a hash
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $updateStmt->execute([$hashedPassword, $user['user_id']]);
                    
                    // Continue with login
                    // Store user data in session for OTP verification
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['first_name'] = $user['FirstName'];
                    $_SESSION['last_name'] = $user['LastName'];
                    $_SESSION['error'] = 'Please verify your email first';
                    
                    header('Location: otp.php');
                    exit();
                } else {
                    $_SESSION['error'] = 'Invalid Username or Password';
                    header('Location: index.php');
                    exit();
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            header('Location: index.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Username and password are required';
        header('Location: index.php');
        exit();
    }
}

// Initialize variables for error/success messages
$message = '';

// Check if user is already verified
if (isset($_SESSION['verified']) && $_SESSION['verified'] === true) {
    header('Location: user-dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($firstName) && !empty($lastName) && !empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (FirstName, LastName, username, password) VALUES (:firstName, :lastName, :username, :password)');
            $stmt->execute([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT) // Note: Using password_hash()
            ]);
            $message = 'Account created successfully!';
        } catch (PDOException $e) {
            $message = 'Error creating account: ' . $e->getMessage();
        }
    } else {
        $message = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div id="success-popup" class="success-popup">
                <div class="popup-content">
                    <p><?= htmlspecialchars($_SESSION['success']) ?></p>
                    <span class="popup-close">&times;</span>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <script>
            // Auto-dismiss after 5 seconds
            const successPopup = document.getElementById('success-popup');
            if (successPopup) {
                setTimeout(() => {
                    successPopup.style.animation = 'fadeInDown 0.4s ease-out reverse';
                    setTimeout(() => successPopup.remove(), 400);
                }, 5000);
                
                // Manual close
                successPopup.querySelector('.popup-close').addEventListener('click', () => {
                    successPopup.style.animation = 'fadeInDown 0.4s ease-out reverse';
                    setTimeout(() => successPopup.remove(), 400);
                });
            }
        </script>

        <!-- Left Section (Login Form) -->
        <div class="left-section">
            <h1 class="welcome-text">Welcome Back !!</h1>
            <p class="login-subtitle">Please enter your credentials to log in</p>

            <form method="POST" action="index.php" id="login-form" autocomplete="off">
                <div class="input-container">
                    <input type="text" name="username" class="input-field" placeholder="Username" required autocomplete="off">
                </div>

                <div class="input-container">
                    <div class="password-container">
                        <input type="password" name="password" id="password" class="input-field" placeholder="Password" required autocomplete="off">
                        <span class="password-toggle">
                            <i class="fas fa-eye-slash"></i>
                        </span>
                    </div>
                </div>

                <a class="forgot-password" href="forgot-password.php">Forgot password?</a>
                
                <button type="submit" name="login" class="signin-btn" value="1">
                    <span class="signin-btn-text">SIGN IN</span>
                </button>
            </form>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="images/logo.png" alt="Book King Logo">
            <p class="signup-text">New to our platform? Sign Up now.</p>
            <button onclick="window.location.href='signup.php'" class="signup-btn">
                <span class="signup-btn-text">SIGN UP</span>
            </button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password visibility toggle
        const togglePassword = document.querySelector('.password-toggle');
        const password = document.getElementById('password');
        const icon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle icon class
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
        
        // Add form submission handler
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', function(event) {
                // Don't prevent default - we want normal form submission
                console.log('Form submitted');
            });
        }
    });
    </script>
</body>

</html>