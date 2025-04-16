<?php
// Start the session
session_start();

// Include the database connection
$pdo = require 'database/db_connection.php';

// Add near the top after session_start()
require_once 'helpers/activity_logger.php';

// Function to generate a random six-digit OTP
function generateOTP($pdo, $user_id)
{
    $otp = rand(100000, 999999); // This will generate a random six-digit OTP

    $stmt = $pdo->prepare('INSERT INTO otp (user_id, otp) VALUES (:user_id, :otp)');
    $stmt->execute([
        'user_id' => $user_id,
        'otp' => $otp
    ]);

    return $otp;
}

// Initialize message variable
$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $contactNo = $_POST['contactNo'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (
        !empty($firstName) && !empty($lastName) && !empty($contactNo) &&
        !empty($email) && !empty($username) && !empty($password)
    ) {

        try {
            // Check if username already exists
            $checkStmt = $pdo->prepare('SELECT username FROM users WHERE username = :username');
            $checkStmt->execute(['username' => $username]);

            // Check if email already exists
            $checkEmailStmt = $pdo->prepare('SELECT Email FROM users WHERE Email = :email');
            $checkEmailStmt->execute(['email' => $email]);

            if ($checkStmt->rowCount() > 0) {
                $message = 'Username already exists. Please choose another.';
            } else if ($checkEmailStmt->rowCount() > 0) {
                $message = 'Email already registered. Please use a different email or try to login.';
            } else {
                // Begin transaction
                $pdo->beginTransaction();

                try {
                    // Hash the password securely
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user with hashed password
                    $stmt = $pdo->prepare('INSERT INTO users (FirstName, LastName, contactNo, Email, username, password) 
                                         VALUES (:firstName, :lastName, :contactNo, :email, :username, :password)');

                    $stmt->execute([
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'contactNo' => $contactNo,
                        'email' => $email,
                        'username' => $username,
                        'password' => $hashedPassword  // Store hashed password
                    ]);

                    $user_id = $pdo->lastInsertId();

                    // Log the signup
                    logActivity(
                        $pdo,
                        'SIGNUP',
                        "New user registered: {$username}",
                        $username,
                        $user_id,
                        'completed'
                    );

                    // Generate OTP and store in session for verification
                    $otp = generateOTP($pdo, $user_id);
                    if ($otp) {
                        require_once 'email_service.php';
                        $emailService = new EmailService();
                        
                        if ($emailService->sendOTP($email, $otp)) {
                            $_SESSION['pending_otp'] = $otp;
                            $_SESSION['pending_user_id'] = $user_id;
                            error_log("OTP sent successfully to: " . $email);
                            
                            // Store success message in session
                            $_SESSION['success_message'] = 'OTP sent successfully. Please check your email.';
                            
                            // Commit the transaction before redirect
                            $pdo->commit();
                            
                            // Redirect to OTP verification page
                            header('Location: otp.php');
                            exit();
                        } else {
                            // Rollback if email sending fails
                            $pdo->rollBack();
                            error_log("Failed to send OTP to: " . $email);
                            $message = 'Failed to send verification email. Please try again.';
                        }
                    } else {
                        // Rollback if OTP generation fails
                        $pdo->rollBack();
                        $message = 'Failed to generate OTP. Please try again.';
                    }

                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
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
    <title>Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/signup.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <div class="container">
        <!-- Left Section -->
        <div class="left-section">
            <img src="images/logo.png" alt="Book King Logo">
            <p class="signin-text">Already have Account? Sign In now.</p>
            <button class="signin-btn" onclick="window.location.href='index.php'">
                <span class="signin-btn-text">SIGN IN</span>
            </button>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <h1 class="title">Sign Up</h1>
            <p class="subtitle">Please provide your information to sign up.</p>

            <?php if (!empty($message)): ?>
                <p class="message"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST" action="" id="signup-form" autocomplete="off">
                <div class="input-container">
                    <input type="text" name="firstName" class="input-field" placeholder="First Name" required autocomplete="off">
                </div>

                <div class="input-container">
                    <input type="text" name="lastName" class="input-field" placeholder="Last Name" required autocomplete="off">
                </div>

                <div class="input-container contact">
                    <div style="position: relative;">
                        <input type="text" name="contactNo" class="input-field" placeholder="09XXXXXXXXX" required pattern="[0-9]{11}" maxlength="11" oninput="handleContactInput(this)" onkeyup="validateContact(this)">
                    </div>
                    <div class="contact-message" style="font-size: 11px; color: #ff4444; text-align: left; margin-top: 4px; min-height: 15px;"></div>
                </div>

                <div class="input-container email">
                    <input type="email" name="email" class="input-field" placeholder="Email" required autocomplete="off">
                    <div class="email-message"></div>
                </div>

                <div class="input-container">
                    <input type="text" name="username" class="input-field" placeholder="Username" required autocomplete="new-username">
                    <div class="username-message"></div>
                </div>

                <div class="input-container">
                    <div class="password-container">
                        <input type="password" name="password" id="password" class="input-field" placeholder="Password" required autocomplete="new-password">
                        <span class="password-toggle">
                            <i class="fas fa-eye-slash"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="signup-btn">
                    <span class="signup-btn-text">SIGN UP</span>
                </button>
            </form>
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

            // Username check functionality
            const usernameInput = document.querySelector('input[name="username"]');
            const usernameMessage = document.querySelector('.username-message');
            let typingTimer;

            usernameInput.addEventListener('input', function() {
                clearTimeout(typingTimer);
                
                // Remove any existing classes
                usernameInput.classList.remove('error', 'success');
                usernameMessage.classList.remove('error', 'success');
                usernameMessage.textContent = '';

                // Wait for user to stop typing for 500ms
                typingTimer = setTimeout(function() {
                    const username = usernameInput.value.trim();
                    
                    if (username.length < 3) {
                        usernameInput.classList.add('error');
                        usernameMessage.classList.add('error');
                        usernameMessage.textContent = 'Username must be at least 3 characters';
                        return;
                    }

                    // Check username availability
                    fetch('check_username.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'username=' + encodeURIComponent(username)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            usernameInput.classList.add('success');
                            usernameMessage.classList.add('success');
                            usernameMessage.textContent = 'Username is available';
                        } else {
                            usernameInput.classList.add('error');
                            usernameMessage.classList.add('error');
                            usernameMessage.textContent = 'Username is already taken';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }, 500);
            });

            // Email validation
            const emailInput = document.querySelector('input[name="email"]');
            const emailMessage = document.querySelector('.email-message');
            let emailTypingTimer;

            emailInput.addEventListener('input', function() {
                clearTimeout(emailTypingTimer);
                
                // Remove any existing classes
                emailInput.classList.remove('error', 'success');
                emailMessage.classList.remove('error', 'success');
                emailMessage.textContent = '';

                // Wait for user to stop typing for 500ms
                emailTypingTimer = setTimeout(function() {
                    const email = emailInput.value.trim();
                    
                    // Basic email format validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        emailInput.classList.add('error');
                        emailMessage.classList.add('error');
                        emailMessage.textContent = 'Please enter a valid email address';
                        return;
                    }

                    // Check email availability
                    fetch('check_email.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'email=' + encodeURIComponent(email)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.valid) {
                            emailInput.classList.add('success');
                            emailMessage.classList.add('success');
                            emailMessage.textContent = data.message;
                        } else {
                            emailInput.classList.add('error');
                            emailMessage.classList.add('error');
                            emailMessage.textContent = data.message;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }, 500);
            });

            // Simple Password strength checker
            const passwordInput = document.querySelector('input[name="password"]');
            const passwordContainer = passwordInput.closest('.password-container');
            
            // Create and append password strength elements
            const strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            strengthIndicator.innerHTML = `
                <div class="strength-meter">
                    <span></span>
                </div>
                <div class="strength-text"></div>
            `;
            passwordContainer.appendChild(strengthIndicator);

            function checkPasswordStrength(password) {
                // Simple strength calculation
                let strength = 0;
                
                // Length check
                if (password.length > 0) strength += 1;
                if (password.length >= 6) strength += 1;
                
                // Character variety check
                if (/[A-Z]/.test(password)) strength += 1;
                if (/[0-9]/.test(password)) strength += 1;
                if (/[^A-Za-z0-9]/.test(password)) strength += 1;

                // Determine strength level
                let strengthLevel = '';
                let strengthText = '';

                if (password.length === 0) {
                    strengthLevel = '';
                    strengthText = '';
                } else if (strength <= 2) {
                    strengthLevel = 'weak';
                    strengthText = 'Weak';
                } else if (strength <= 3) {
                    strengthLevel = 'medium';
                    strengthText = 'Medium';
                } else {
                    strengthLevel = 'strong';
                    strengthText = 'Strong';
                }

                // Update strength indicator
                strengthIndicator.className = `password-strength ${strengthLevel}`;
                strengthIndicator.querySelector('.strength-text').textContent = strengthText;
            }

            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });

            function handleContactInput(input) {
                let value = input.value.replace(/[^0-9]/g, '');
                
                // Ensure the value starts with '09'
                if (!value.startsWith('09')) {
                    value = '09' + value;
                }
                
                // Limit to 11 digits total
                value = value.substring(0, 11);
                
                // Update input value
                input.value = value;
            }

            function validateContact(input) {
                const contactMessage = input.parentElement.parentElement.querySelector('.contact-message');
                const value = input.value;
                
                // Clear previous message
                contactMessage.textContent = '';
                input.style.borderColor = '';
                
                if (value.length > 0) {
                    if (!/^[0-9]+$/.test(value)) {
                        contactMessage.textContent = 'Please enter numbers only';
                        input.style.borderColor = '#ff4444';
                        return false;
                    }
                    
                    if (value.length !== 11) {
                        contactMessage.textContent = 'Contact number must be 11 digits';
                        input.style.borderColor = '#ff4444';
                        return false;
                    }

                    if (!value.startsWith('09')) {
                        contactMessage.textContent = 'Number must start with 09';
                        input.style.borderColor = '#ff4444';
                        return false;
                    }
                    
                    // Check for valid PH mobile prefixes
                    const validPrefixes = [
                        // Globe/TM
                        '0905', '0906', '0915', '0916', '0917', '0926', '0927', '0935', '0936', '0937',
                        '0945', '0953', '0954', '0955', '0956', '0965', '0966', '0967', '0975', '0976',
                        '0977', '0978', '0979', '0995', '0996', '0997',
                        // Smart/TNT/Sun
                        '0908', '0918', '0919', '0920', '0921', '0928', '0929', '0939', '0946', '0947',
                        '0949', '0951', '0961', '0998', '0999',
                        // DITO
                        '0895', '0896', '0897', '0898', '0991', '0992', '0993', '0994',
                        // Others
                        '0922', '0923', '0925', '0931', '0932', '0933', '0934', '0940', '0941', '0942',
                        '0943', '0973', '0974'
                    ];

                    const prefix = value.substring(0, 4);
                    if (!validPrefixes.includes(prefix)) {
                        contactMessage.textContent = 'Please enter a valid PH mobile number';
                        input.style.borderColor = '#ff4444';
                        return false;
                    }
                }
                
                input.style.borderColor = '#B07154';
                return true;
            }

            // Add form submission validation
            document.getElementById('signup-form').addEventListener('submit', function(e) {
                const contactInput = this.querySelector('input[name="contactNo"]');
                if (!validateContact(contactInput)) {
                    e.preventDefault();
                    contactInput.focus();
                }
            });
        });
    </script>
</body>

</html>