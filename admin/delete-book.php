<?php
// Prevent any output before our JSON response
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error display for debugging

// Start session and include required files
session_start();
require_once '../helpers/activity_logger.php';

// Set JSON content type header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $pdo = require '../database/db_connection.php';

    // Check if book_id is provided in POST data
    if (!isset($_POST['book_id'])) {
        throw new Exception('Book ID is required');
    }

    $book_id = filter_var($_POST['book_id'], FILTER_VALIDATE_INT);
    if ($book_id === false) {
        throw new Exception('Invalid book ID');
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Check if book exists and get its title
    $stmt = $pdo->prepare("SELECT title FROM books WHERE book_id = ?");
    $stmt->execute([$book_id]);
    $bookTitle = $stmt->fetchColumn();

    if (!$bookTitle) {
        throw new Exception('Book not found');
    }

    // Check if book is currently borrowed
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM borrowed_books 
        WHERE book_id = ? AND return_date IS NULL
    ");
    $stmt->execute([$book_id]);
    $isBorrowed = $stmt->fetchColumn() > 0;

    if ($isBorrowed) {
        throw new Exception('Cannot delete book because it is currently borrowed');
    }

    // Delete the book
    $stmt = $pdo->prepare("DELETE FROM books WHERE book_id = ?");
    $result = $stmt->execute([$book_id]);

    if ($result) {
        // Log the activity
        logActivity(
            $pdo,
            'DELETE',
            "Deleted book: {$bookTitle}",
            $_SESSION['admin_first_name'] . ' ' . $_SESSION['admin_last_name'],
            $book_id
        );

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
    } else {
        throw new Exception('Failed to delete book');
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in delete-book.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
