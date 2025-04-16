<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
$pdo = require 'database/db_connection.php';

try {
    // Validate user_id
    if (empty($_POST['user_id'])) {
        throw new Exception('User ID is required');
    }

    $userId = $_POST['user_id'];

    // Check if user exists
    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE user_id = ?');
    $checkStmt->execute([$userId]);
    if ($checkStmt->fetchColumn() == 0) {
        throw new Exception('User not found');
    }

    // Delete user
    $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
    $success = $stmt->execute([$userId]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        throw new Exception('Failed to delete user');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
