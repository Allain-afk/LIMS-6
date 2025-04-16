<?php
// Include the database connection
$pdo = require 'database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    
    if (!empty($username)) {
        try {
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = :username');
            $stmt->execute(['username' => $username]);
            
            $response = [
                'available' => ($stmt->rowCount() === 0)
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
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
?>