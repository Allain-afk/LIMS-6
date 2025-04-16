<?php
session_start();
require '../helpers/activity_logger.php';
$pdo = require '../database/db_connection.php';

try {
    $pdo->beginTransaction();

    // Your existing borrower addition code
    $stmt = $pdo->prepare("INSERT INTO borrowed_books (user_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['user_id'], $_POST['book_id'], $_POST['borrow_date'], $_POST['due_date']]);

    // Get the borrower and book details for the log
    $userStmt = $pdo->prepare("SELECT FirstName, LastName FROM users WHERE user_id = ?");
    $userStmt->execute([$_POST['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    $bookStmt = $pdo->prepare("SELECT title FROM books WHERE book_id = ?");
    $bookStmt->execute([$_POST['book_id']]);
    $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

    // Log the borrow activity
    $description = sprintf(
        "Book '%s' borrowed by %s %s",
        $book['title'],
        $user['FirstName'],
        $user['LastName']
    );

    logActivity(
        $pdo,
        'BORROW',
        $description,
        $_SESSION['admin_first_name'] . ' ' . $_SESSION['admin_last_name'],
        $_POST['book_id']
    );

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error adding borrower: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
