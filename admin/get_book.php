<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Book ID is required']);
    exit();
}

try {
    $pdo = require '../database/db_connection.php';
    
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->execute([$_GET['id']]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
        echo json_encode($book);
    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
} 