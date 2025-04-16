<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
$pdo = require '../database/db_connection.php';

try {
    // Validate required fields
    $required_fields = ['firstName', 'lastName', 'email', 'username', 'password', 'contactNo'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("All fields are required");
        }
    }

    // Check if username or email already exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$_POST['username'], $_POST['email']]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Username or email already exists');
    }

    // Insert new user
    $stmt = $pdo->prepare('
        INSERT INTO users (FirstName, LastName, email, username, password, contactNo)
        VALUES (?, ?, ?, ?, ?, ?)
    ');

    $success = $stmt->execute([
        $_POST['firstName'],
        $_POST['lastName'],
        $_POST['email'],
        $_POST['username'],
        password_hash($_POST['password'], PASSWORD_DEFAULT), // Hash the password
        $_POST['contactNo']
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        throw new Exception('Failed to add user');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
