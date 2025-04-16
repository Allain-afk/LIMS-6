<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$pdo = require '../database/db_connection.php';

try {
    $userId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if (!$userId) {
        throw new Exception('Invalid user ID');
    }

    $stmt = $pdo->prepare("SELECT 
        user_id, 
        email, 
        firstname,
        lastname,
        phone, 
        is_active 
        FROM users 
        WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    header('Content-Type: application/json');
    echo json_encode($user);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}