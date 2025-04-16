<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Make sure book_id is provided
if (!isset($_POST['book_id'])) {
    echo json_encode(['success' => false, 'message' => 'Book ID is required']);
    exit();
}

try {
    // Database connection
    $pdo = require '../database/db_connection.php';
    
    // Validate book_id
    $book_id = filter_var($_POST['book_id'], FILTER_VALIDATE_INT);
    if (!$book_id) {
        throw new Exception('Invalid book ID');
    }
    
    // First check if the book exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE book_id = ?");
    $checkStmt->execute([$book_id]);
    if ($checkStmt->fetchColumn() == 0) {
        throw new Exception('Book not found');
    }

    // Then check if book is borrowed
    $borrowStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM borrowed_books 
        WHERE book_id = ? AND return_date IS NULL
    ");
    $borrowStmt->execute([$book_id]);
    if ($borrowStmt->fetchColumn() > 0) {
        throw new Exception('Cannot delete book because it is currently borrowed');
    }

    // Get book details for logging
    $bookStmt = $pdo->prepare("SELECT title FROM books WHERE book_id = ?");
    $bookStmt->execute([$book_id]);
    $title = $bookStmt->fetchColumn();

    // Begin transaction
    $pdo->beginTransaction();
    
    // Delete the book
    $deleteStmt = $pdo->prepare("DELETE FROM books WHERE book_id = ?");
    $result = $deleteStmt->execute([$book_id]);

    if ($result) {
        // Log the activity if the helper exists
        if (file_exists('../helpers/activity_logger.php')) {
            require_once '../helpers/activity_logger.php';
            
            // Check if the function exists before calling it
            if (function_exists('logActivity')) {
                $adminName = isset($_SESSION['admin_first_name']) && isset($_SESSION['admin_last_name']) 
                    ? $_SESSION['admin_first_name'] . ' ' . $_SESSION['admin_last_name'] 
                    : 'Administrator';
                
                try {
                    logActivity(
                        $pdo,
                        'DELETE',
                        "Deleted book: {$title}",
                        $adminName,
                        $book_id
                    );
                } catch (Exception $logException) {
                    // Just log the error but don't stop the deletion
                    error_log("Error logging activity: " . $logException->getMessage());
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
    } else {
        throw new Exception('Failed to delete book');
    }
} catch (PDOException $e) {
    // Database error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // General error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 