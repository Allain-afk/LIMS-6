<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

// Include the database connection
$pdo = require 'database/db_connection.php';

// Get parameters
$type = $_GET['type'] ?? 'borrowed';
$user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

// Function to display books
function displayBooks($books, $type) {
    if (empty($books)) {
        echo "<div class='no-results'>No " . $type . " books found</div>";
    } else {
        // Count total books
        $totalBooks = count($books);
        foreach ($books as $book) {
            echo "<div class='table-row'>";
            echo "<div class='table-cell'>{$book['book_id']}</div>";
            echo "<div class='table-cell'>{$book['user_id']}</div>";
            echo "<div class='table-cell'>{$totalBooks} Books</div>";
            echo "<div class='table-cell'>" . date('d-m-Y', strtotime($book['due_date'])) . "</div>";
            echo "<div class='table-cell'>" . date('d-m-Y h:i A', strtotime($book['borrow_date'])) . "</div>";
            echo "<div class='table-cell'>";
            if ($type === 'borrowed') {
                echo "<button class='return-btn' onclick='openReturnConfirm({$book['book_id']})'>";
                echo "<span class='return-icon'></span>";
                echo "<span>Return</span>";
                echo "</button>";
            }
            echo "</div>";
            echo "</div>";
        }
    }
}

try {
    // Prepare the query based on type
    if ($type === 'borrowed') {
        $stmt = $pdo->prepare("
            SELECT b.*, bb.borrow_date, bb.due_date, bb.return_date, bb.user_id,
                   1 as book_count
            FROM borrowed_books bb 
            JOIN books b ON bb.book_id = b.book_id 
            WHERE bb.user_id = ? AND bb.return_date IS NULL
            ORDER BY bb.borrow_date DESC
        ");
        $stmt->execute([$user_id]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($books)) {
            echo "<div class='table-container'>";
            echo "<div class='no-results'>No borrowed books found</div>";
            echo "</div>";
        } else {
            foreach ($books as $book) {
                echo "<div class='table-row'>";
                echo "<div class='table-cell'>{$book['book_id']}</div>";
                echo "<div class='table-cell'>{$book['user_id']}</div>";
                echo "<div class='table-cell'>{$book['book_count']} Book</div>";
                echo "<div class='table-cell'>" . date('d-m-Y', strtotime($book['due_date'])) . "</div>";
                echo "<div class='table-cell'>" . date('d-m-Y h:i A', strtotime($book['borrow_date'])) . "</div>";
                echo "<div class='table-cell'>";
                echo "<button class='return-btn' onclick='openReturnConfirm({$book['book_id']})'>";
                echo "<img src='images/redo 1.png' alt='Return' class='return-icon'>";
                echo "<span>Return</span>";
                echo "</button>";
                echo "</div>";
                echo "</div>";
            }
        }
    } else {
        // Query for returned books
        $stmt = $pdo->prepare("
            SELECT b.*, bb.borrow_date, bb.due_date, bb.return_date, bb.user_id,
                   1 as book_count
            FROM borrowed_books bb 
            JOIN books b ON bb.book_id = b.book_id 
            WHERE bb.user_id = ? AND bb.return_date IS NOT NULL
            ORDER BY bb.return_date DESC
        ");
        $stmt->execute([$user_id]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($books)) {
            echo "<div class='table-container'>";
            echo "<div class='no-results'>No returned books found</div>";
            echo "</div>";
        } else {
            foreach ($books as $book) {
                echo "<div class='table-row'>";
                echo "<div class='table-cell'>{$book['book_id']}</div>";
                echo "<div class='table-cell'>{$book['user_id']}</div>";
                echo "<div class='table-cell'>{$book['book_count']} Book</div>";
                echo "<div class='table-cell'>" . date('d-m-Y', strtotime($book['due_date'])) . "</div>";
                echo "<div class='table-cell'>" . date('d-m-Y h:i A', strtotime($book['return_date'])) . "</div>";
                echo "<div class='table-cell'>Returned</div>";
                echo "</div>";
            }
        }
    }
} catch (PDOException $e) {
    echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
} 