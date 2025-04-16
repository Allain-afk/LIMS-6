<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = require '../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        $firstName = trim($_POST['firstname'] ?? '');
        $lastName = trim($_POST['lastname'] ?? '');
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (!$userId || !$email) {
            throw new Exception('Invalid input data');
        }

        // Check if email already exists for other users
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
        $checkStmt->execute([$email, $userId]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception('Email already exists');
        }

        $stmt = $pdo->prepare("UPDATE users SET 
            firstname = ?, 
            lastname = ?, 
            email = ?, 
            is_active = ? 
            WHERE user_id = ?");

        $result = $stmt->execute([$firstName, $lastName, $email, $isActive, $userId]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update user');
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
