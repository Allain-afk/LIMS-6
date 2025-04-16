<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $pdo = require '../database/db_connection.php';

    // Validate inputs
    $book_id = filter_var($_POST['book_id'], FILTER_VALIDATE_INT);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $language = trim($_POST['language'] ?? '');

    if (!$book_id || empty($title) || empty($author) || empty($type) || empty($language)) {
        throw new Exception('All fields are required');
    }

    $stmt = $pdo->prepare("
        UPDATE books 
        SET title = ?, author = ?, type = ?, language = ? 
        WHERE book_id = ?
    ");

    $result = $stmt->execute([$title, $author, $type, $language, $book_id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
    } else {
        throw new Exception('Failed to update book');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 