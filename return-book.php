<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session and set JSON header first
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Include files after headers are set
    require_once 'helpers/activity_logger.php';
    $pdo = require 'database/db_connection.php';

    // Validate input parameters
    if (!isset($_POST['borrow_id']) || !isset($_POST['return_date'])) {
        throw new Exception('Missing required parameters');
    }

    $pdo->beginTransaction();

    // Get borrow details before updating
    $stmt = $pdo->prepare("
        SELECT 
            bb.user_id,
            bb.book_id,
            u.FirstName,
            u.LastName,
            b.title
        FROM borrowed_books bb
        JOIN users u ON bb.user_id = u.user_id
        JOIN books b ON bb.book_id = b.book_id
        WHERE bb.id = ?
    ");
    
    if (!$stmt->execute([$_POST['borrow_id']])) {
        throw new Exception('Failed to fetch borrow details');
    }
    
    $borrowDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$borrowDetails) {
        throw new Exception('Borrow record not found');
    }

    // Update return date
    $returnStmt = $pdo->prepare("UPDATE borrowed_books SET return_date = ? WHERE id = ?");
    if (!$returnStmt->execute([$_POST['return_date'], $_POST['borrow_id']])) {
        throw new Exception('Failed to update return date');
    }

    // Update book availability
    $updateBookStmt = $pdo->prepare("UPDATE books SET availability = 'Available' WHERE book_id = ?");
    if (!$updateBookStmt->execute([$borrowDetails['book_id']])) {
        throw new Exception('Failed to update book availability');
    }

    // Log the return activity
    $description = sprintf(
        "Book '%s' returned by %s %s", 
        $borrowDetails['title'],
        $borrowDetails['FirstName'],
        $borrowDetails['LastName']
    );
    
    logActivity(
        $pdo,
        'BOOK_RETURN',
        $description,
        $_SESSION['admin_first_name'] . ' ' . $_SESSION['admin_last_name'],
        $borrowDetails['book_id']
    );

    $pdo->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Book returned successfully'
    ]);

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
