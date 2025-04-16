<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session and set JSON header first
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Include database connection after headers
    $pdo = require '../database/db_connection.php';

    // Validate ID parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Borrow ID is required');
    }

    $borrowId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($borrowId === false) {
        throw new Exception('Invalid borrow ID');
    }

    // Fetch borrower details
    $stmt = $pdo->prepare('
        SELECT 
            bb.id, 
            bb.user_id, 
            bb.book_id, 
            bb.borrow_date, 
            bb.due_date, 
            bb.return_date,
            u.FirstName, 
            u.LastName, 
            b.title
        FROM 
            borrowed_books bb
        JOIN 
            users u ON bb.user_id = u.user_id
        JOIN 
            books b ON bb.book_id = b.book_id
        WHERE 
            bb.id = ?
    ');

    if (!$stmt->execute([$borrowId])) {
        throw new Exception('Failed to execute query');
    }

    $borrower = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$borrower) {
        throw new Exception('Borrower not found');
    }

    // Format dates
    $borrowDate = date('M d, Y', strtotime($borrower['borrow_date']));
    $dueDate = date('M d, Y', strtotime($borrower['due_date']));
    $returnDate = $borrower['return_date'] ? date('M d, Y', strtotime($borrower['return_date'])) : null;

    // Check if book is overdue
    $dueDateTime = new DateTime($borrower['due_date']);
    $today = new DateTime();
    $isOverdue = $dueDateTime < $today && $borrower['return_date'] === null;
    
    // Determine status
    $status = 'Active';
    $statusClass = 'status-active';
    
    if ($isOverdue) {
        $status = 'Overdue';
        $statusClass = 'status-overdue';
    } elseif ($borrower['return_date'] !== null) {
        $status = 'Returned';
        $statusClass = 'status-returned';
    }

    // Prepare response
    $response = [
        'success' => true,
        'id' => $borrower['id'],
        'borrower_name' => $borrower['FirstName'] . ' ' . $borrower['LastName'],
        'book_title' => $borrower['title'],
        'borrow_date' => $borrowDate,
        'due_date' => $dueDate,
        'return_date' => $returnDate,
        'status' => $status,
        'status_class' => $statusClass
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}