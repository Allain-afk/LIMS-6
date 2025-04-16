<?php
session_start();
require_once 'helpers/activity_logger.php';

// Include the database connection
$pdo = require 'database/db_connection.php';

function saveBorrowedBooks($pdo, $userId, $books, $dueDate)
{
    try {
        // Begin transaction
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO borrowed_books (user_id, book_id, due_date) 
            VALUES (:user_id, :book_id, :due_date)
        ");

        foreach ($books as $book) {
            $stmt->execute([
                ':user_id' => $userId,
                ':book_id' => $book['id'],
                ':due_date' => $dueDate
            ]);

            // Log each book borrow
            logActivity(
                $pdo,
                'BORROW',
                "Book '{$book['title']}' borrowed by user ID: {$userId}",
                $_SESSION['username'],
                $book['id'],
                'completed'
            );
        }

        // Commit transaction
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error saving borrowed books: " . $e->getMessage());
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Book Confirmation</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/user-borrow-confirm.css">
</head>

<body>
    <div class="container">
        <div class="table-container">
            <div class="table-header">
                <div>Book ID</div>
                <div>Name</div>
                <div>Type</div>
                <div>Language</div>
                <div>Action</div>
            </div>
            <div id="selected-books"></div>
        </div>

        <div class="summary-section">
            <div class="id-section">
                <div class="id-label">ID</div>
                <div class="id-value" id="total-items">0</div>
            </div>
            <div class="details-section">
                <div class="detail-row">
                    <div class="detail-label">Total Books :</div>
                    <div class="detail-value" id="total-books">00 Books</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Due Date :</div>
                    <div class="detail-value" id="due-date"></div>
                </div>
            </div>
        </div>

        <div class="button-group">
            <button class="cancel-button" onclick="window.location.href='user-borrow-books.php'">CANCEL</button>
            <button class="confirm-button" onclick="confirmBorrow()">CONFIRM</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get selected books from session storage
            const selectedBooks = JSON.parse(sessionStorage.getItem('selectedBooks') || '[]');
            const tableContainer = document.getElementById('selected-books');

            // Display selected books
            selectedBooks.forEach(book => {
                const row = document.createElement('div');
                row.className = 'table-row';
                row.innerHTML = `
                <div>${book.id}</div>
                <div class="book-name">${book.title}</div>
                <div>${book.type}</div>
                <div>${book.language}</div>
                <div>
                    <div class="action-icon" onclick="removeBook(${book.id})">
                        <img src="images/btn Delete.png" alt="Delete">
                    </div>
                </div>
            `;
                tableContainer.appendChild(row);
            });

            // Update summary
            document.getElementById('total-items').textContent = selectedBooks.length;
            document.getElementById('total-books').textContent =
                `${String(selectedBooks.length).padStart(2, '0')} Books`;

            // Set due date (7 days from now)
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + 7);

            // Format date as "Month Day, Year"
            const formattedDate = dueDate.toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });

            document.getElementById('due-date').textContent = formattedDate;
        });

        function removeBook(bookId) {
            const selectedBooks = JSON.parse(sessionStorage.getItem('selectedBooks') || '[]');
            const updatedBooks = selectedBooks.filter(book => book.id !== bookId.toString());
            sessionStorage.setItem('selectedBooks', JSON.stringify(updatedBooks));
            location.reload();
        }

        // Replace the existing confirmBorrow function
        function confirmBorrow() {
            const selectedBooks = JSON.parse(sessionStorage.getItem('selectedBooks') || '[]');
            if (selectedBooks.length === 0) {
                alert('No books selected');
                return;
            }

            // Get the due date
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + 7);
            const formattedDueDate = dueDate.toISOString().slice(0, 19).replace('T', ' ');

            // Send the data to PHP
            fetch('save_borrowed_books.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        books: selectedBooks,
                        dueDate: formattedDueDate
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear the cart and redirect
                        sessionStorage.removeItem('selectedBooks');
                        window.location.href = 'user-dashboard.php';
                    } else {
                        alert('Error saving borrowed books. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving borrowed books. Please try again.');
                });
        }
    </script>
</body>

</html>