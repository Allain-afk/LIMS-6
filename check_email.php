<?php
// Include the database connection
$pdo = require 'database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (!empty($email)) {
        try {
            // First validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'valid' => false,
                    'message' => 'Invalid email format'
                ]);
                exit;
            }

            // Check if email exists in database
            $stmt = $pdo->prepare('SELECT Email FROM users WHERE Email = :email');
            $stmt->execute(['email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'valid' => false,
                    'message' => 'Email is already registered'
                ]);
            } else {
                echo json_encode([
                    'valid' => true,
                    'message' => 'Email is available'
                ]);
            }
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']); 