<?php
session_start();

// Include the database connection
$pdo = require 'database/db_connection.php';

// Fetch books data from the database
try {
    $stmt = $pdo->query('SELECT * FROM books ORDER BY book_id');
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalBooks = count($books);
} catch (PDOException $e) {
    die('Error fetching books: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books view</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/books-view.css">
</head>

<body>
    <div class="container">
        <div class="books-table">
            <div class="table-header-divider"></div>
            <div class="header-text header-language">Language</div>
            <div class="header-text header-bookid">Book ID</div>
            <div class="header-text header-title">Title</div>
            <div class="header-text header-type">Type</div>

            <?php foreach ($books as $index => $book): ?>
                <div class="book-row row-<?= $index + 1 ?>">
                    <div class="book-text book-id"><?= htmlspecialchars($book['book_id']) ?></div>
                    <div class="book-text book-title"><?= htmlspecialchars($book['title']) ?></div>
                    <div class="book-text book-language"><?= htmlspecialchars($book['language']) ?></div>
                    <div class="book-text book-type"><?= htmlspecialchars($book['type']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="book-summary">
            <div class="total-books-text"><?= sprintf('%02d', $totalBooks) ?> Books</div>
            <div class="book-count"><?= $totalBooks ?></div>
            <div class="due-date">13 - 12 - 2024</div>
            <div class="total-books-label">Total Books :</div>
            <div class="id-label">ID</div>
            <div class="due-date-label">Due Date :</div>
            <div class="vertical-divider"></div>
        </div>

        <div class="close-button" onclick="window.location.href='user-dashboard.php'">
            <div class="close-text">CLOSE</div>
        </div>
    </div>
</body>

</html>