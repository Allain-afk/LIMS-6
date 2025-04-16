<?php
session_start();
require 'database/db_connection.php';

// Debug: Log the start of the process
error_log("Login process started");
error_log("POST data: " . print_r($_POST, true));

// Simple login check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    error_log("Username: " . $username);
    error_log("Password received: " . $password);

    // Simple query to check user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Verify password with password_verify()
    if ($user && password_verify($password, $user['password'])) {
        error_log("User found: " . print_r($user, true));

        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['FirstName'];
        $_SESSION['last_name'] = $user['LastName'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        $_SESSION['verified'] = true;

        error_log("Session data set: " . print_r($_SESSION, true));

        // Clear any output buffering
        if (ob_get_length()) ob_end_clean();
        
        // Redirect to dashboard
        header("Location: user-dashboard.php");
        exit();
    } else {
        error_log("User not found");
        $_SESSION['error'] = "Invalid username or password";
        header("Location: index.php");
        exit();
    }
} else {
    error_log("Not a POST request");
    header("Location: index.php");
    exit();
}
?> 