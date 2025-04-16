<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$pdo = require 'database/db_connection.php';

try {
    $currentPassword = $_POST['currentPassword'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Check both plain text (legacy) and hashed (new) passwords
    $verified = ($currentPassword === $user['password']) || 
                password_verify($currentPassword, $user['password']);

    if ($verified) {
        $_SESSION['password_verified'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }

} catch (PDOException $e) {
    error_log('Password verification error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}