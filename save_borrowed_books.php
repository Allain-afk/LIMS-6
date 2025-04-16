<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$pdo = require 'database/db_connection.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$books = $data['books'] ?? [];
$dueDate = $data['dueDate'] ?? null;

if (empty($books) || !$dueDate) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO borrowed_books (user_id, book_id, due_date) 
        VALUES (:user_id, :book_id, :due_date)
    ");

    foreach ($books as $book) {
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':book_id' => $book['id'],
            ':due_date' => $dueDate
        ]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Error saving borrowed books: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
