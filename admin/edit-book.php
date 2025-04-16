<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once '../helpers/activity_logger.php';

// Set JSON content type header
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pdo = require '../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Sanitize and validate inputs
$title = trim($_POST['title'] ?? '');
$author = trim($_POST['author'] ?? '');
$type = trim($_POST['type'] ?? '');
$language = trim($_POST['language'] ?? '');
$bookId = filter_var($_POST['book_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$bookId || empty($title) || empty($author) || empty($type) || empty($language)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE books 
        SET title = :title, 
            author = :author, 
            type = :type, 
            language = :language 
        WHERE book_id = :book_id
    ");

    $result = $stmt->execute([
        'title' => $title,
        'author' => $author,
        'type' => $type,
        'language' => $language,
        'book_id' => $bookId
    ]);

    if ($result) {
        logActivity(
            $pdo,
            'UPDATE',
            "Updated book: {$title}",
            $_SESSION['admin_first_name'] . ' ' . $_SESSION['admin_last_name'],
            $bookId
        );
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
    } else {
        throw new Exception('Failed to update book');
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating book. Please try again.']);
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
    exit();
}
