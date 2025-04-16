<?php
// Start session at the very beginning of the file
session_start();

// At the top of the file, after session_start()
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Get admin name from session
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';
$adminLastName = $_SESSION['admin_last_name'] ?? '';
$adminName = $adminFirstName . ' ' . $adminLastName;

// Include the database connection
$pdo = require '../database/db_connection.php';

// Include the activity logger
require '../helpers/activity_logger.php';

// Replace the existing confirmation handling code
if (isset($_POST['confirm_return'])) {
    $log_id = $_POST['log_id'];
    $book_id = $_POST['book_id'];
    $user_id = $_POST['user_id'];

    try {
        $pdo->beginTransaction();

        // Update borrowed_books table
        $stmt = $pdo->prepare("
            UPDATE borrowed_books 
            SET return_date = CURRENT_TIMESTAMP 
            WHERE book_id = ? AND user_id = ? AND return_date IS NULL
        ");
        $stmt->execute([$book_id, $user_id]);

        // Update log status
        $stmt = $pdo->prepare("
            UPDATE activity_logs 
            SET status = 'completed' 
            WHERE log_id = ?
        ");
        $stmt->execute([$log_id]);

        $pdo->commit();

        // Set success message in session instead of using alert
        $_SESSION['return_success'] = true;
        header('Location: catalog.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error confirming return: " . $e->getMessage());
        $_SESSION['return_error'] = true;
        header('Location: ../catalog.php');
        exit();
    }
}

// Fetch logs from database outside of the HTML
try {
    $stmt = $pdo->query('
        SELECT 
            l.log_id,
            l.action_type,
            l.description,
            l.performed_by,
            l.timestamp,
            l.status,
            l.related_id,
            CASE 
                WHEN l.action_type IN ("RETURN_REQUEST", "BOOK_RETURN", "BORROW") 
                THEN (
                    SELECT user_id 
                    FROM borrowed_books 
                    WHERE book_id = l.related_id 
                    ORDER BY borrow_date DESC 
                    LIMIT 1
                )
                ELSE NULL
            END as user_id
        FROM activity_logs l
        ORDER BY l.timestamp DESC
        LIMIT 100
    ');
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching logs: " . $e->getMessage());
    $logs = [];
}

// Add this after your existing query
try {
    $debug_stmt = $pdo->query("
        SELECT * FROM activity_logs 
        WHERE action_type = 'RETURN_REQUEST' 
        AND status = 'pending' 
        LIMIT 1
    ");
    $debug_result = $debug_stmt->fetch(PDO::FETCH_ASSOC);
    if ($debug_result) {
        error_log("Found pending return request: " . print_r($debug_result, true));
    } else {
        error_log("No pending return requests found");
    }
} catch (PDOException $e) {
    error_log("Debug query error: " . $e->getMessage());
}

// Get total books count and fetch all books
try {
    $bookCount = $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
    
    // Fetch all books - FIXED QUERY to remove categories join
    $booksQuery = $pdo->query('
        SELECT * FROM books ORDER BY book_id DESC
    ');
    $books = $booksQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching books: " . $e->getMessage());
    $bookCount = 0;
    $books = [];
}

// Function to get random cover URL if not provided
function getRandomCover($title, $author) {
    // List of background colors (pastel colors)
    $colors = [
        'F8B195', 'F67280', 'C06C84', '6C5B7B', '355C7D', // warm to cool
        'A8E6CF', 'DCEDC1', 'FFD3B6', 'FFAAA5', 'FF8B94', // nature
        'B5EAD7', 'C7CEEA', 'E2F0CB', 'FFDAC1', 'FFB7B2', // soft
        'E7D3EA', 'DCD3FF', 'B5D8EB', 'BBE1FA', 'D6E5FA'  // pastel
    ];
    
    // Get random color
    $bgColor = $colors[array_rand($colors)];
    
    // Format title and author for URL
    $text = urlencode($title . "\nby " . $author);
    
    return "https://placehold.co/400x600/${bgColor}/333333/png?text=${text}";
}

// Update books with cover images if needed
foreach ($books as &$book) {
    if (empty($book['cover_image_url'])) {
        $book['cover_image_url'] = getRandomCover($book['title'], $book['author']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Book Catalog - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <style>
        .catalog-container {
            padding: 20px;
            background: #FEF3E8;
        }

        .catalog-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .catalog-title {
            color: #B07154;
            font-size: 24px;
            font-weight: 600;
        }

        .search-add-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 300px;
            padding: 10px 15px;
            border: 2px solid #B07154;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
        }

        .add-book-btn {
            padding: 10px 20px;
            background: #B07154;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .add-book-btn:hover {
            background: #8B5B43;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .book-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .book-cover {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .book-title {
            color: #B07154;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .book-author {
            color: #8B5B43;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .book-type {
            display: inline-block;
            padding: 4px 8px;
            background: #F4DECB;
            color: #B07154;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .book-language {
            display: inline-block;
            padding: 4px 8px;
            background: #E7D3EA;
            color: #6C5B7B;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 5px;
        }

        .book-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 6px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .edit-btn {
            background: #F4DECB;
            color: #B07154;
        }

        .edit-btn:hover {
            background: #E4C4A9;
        }

        .delete-btn {
            background: #FFE5E5;
            color: #FF4D4D;
        }

        .delete-btn:hover {
            background: #FFD1D1;
        }

        .no-books-message {
            text-align: center;
            padding: 40px;
            color: #8B5B43;
            font-size: 18px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .catalog-header {
                flex-direction: column;
                align-items: stretch;
            }

            .search-add-container {
                flex-direction: column;
            }

            .search-input {
                width: 100%;
            }

            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .catalog-container {
                padding: 15px;
            }

            .catalog-title {
                font-size: 20px;
            }

            .book-card {
                padding: 15px;
            }

            .book-cover {
                height: 150px;
            }

            .book-title {
                font-size: 14px;
            }

            .book-author {
                font-size: 12px;
            }

            .action-btn {
                padding: 6px;
                font-size: 11px;
            }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal h2 {
            color: #B07154;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #F4DECB;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: #B07154;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .form-buttons button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            transition: background-color 0.3s;
        }

        .delete-btn {
            background: #FFE5E5;
            color: #FF4D4D;
        }

        .delete-btn:hover {
            background: #FFD1D1;
        }

        .add-btn {
            background: #B07154;
            color: white;
        }

        .add-btn:hover {
            background: #95604A;
        }

        #deleteModal .modal-content {
            text-align: center;
            padding: 40px;
        }

        #deleteModal p {
            color: #666;
            margin: 20px 0 30px;
            font-size: 16px;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .cancel-btn, .confirm-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            transition: background-color 0.3s;
        }

        .cancel-btn {
            background: #F4DECB;
            color: #B07154;
        }

        .cancel-btn:hover {
            background: #E8C5B0;
        }

        .confirm-btn {
            background: #FF4D4D;
            color: white;
        }

        .confirm-btn:hover {
            background: #E63939;
        }

        #notificationContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }

        .notification {
            background: #FEF3E8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(176, 113, 84, 0.15);
            display: flex;
            align-items: center;
            gap: 15px;
            transform: translateX(120%);
            transition: transform 0.4s ease;
            min-width: 320px;
            border: 2px solid #B07154;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification-success {
            background: #FEF3E8;
            border-color: #B07154;
        }

        .notification-icon {
            font-size: 24px;
            color: #B07154;
        }

        .notification-text {
            color: #B07154;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            flex-grow: 1;
        }

        .notification-close {
            cursor: pointer;
            color: #B07154;
            font-size: 24px;
            padding: 0 5px;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .notification-close:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .notification {
                width: 90%;
                min-width: auto;
                margin: 0 auto 10px;
            }

            #notificationContainer {
                left: 0;
                right: 0;
                padding: 0 10px;
            }
        }

        .modal-header h2 {
            color: #B07154;
            font-size: 24px;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-btn {
            color: #B07154;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            padding: 0 8px;
            line-height: 1;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #95604A;
        }

        .warning-icon {
            width: 64px;
            height: 64px;
            background: #FEF3E8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            color: #B07154;
            font-size: 40px;
            font-weight: bold;
            font-family: 'Montserrat', sans-serif;
        }

        .delete-message {
            color: #4B5563;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            margin: 20px 0;
            font-family: 'Montserrat', sans-serif;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .cancel-btn {
            padding: 12px 32px;
            background: #F4DECB;
            color: #B07154;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cancel-btn:hover {
            background: #E8C5B0;
        }

        .delete-confirm-btn {
            padding: 12px 32px;
            background: #B07154;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .delete-confirm-btn:hover {
            background: #95604A;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(176, 113, 84, 0.2);
        }

        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
                gap: 12px;
                padding: 0 20px;
            }

            .cancel-btn,
            .delete-confirm-btn {
                width: 100%;
                padding: 14px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="mobile-menu-btn">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="sidebar">
        <div class="logo">
            <img src="../images/logo.png" alt="Book King Logo">
        </div>
        <div class="nav-group">
            <a href="../admin/admin-dashboard.php" class="nav-item">
                <div class="icon">
                    <img src="../images/element-2 2.svg" alt="Dashboard" width="24" height="24">
                </div>
                <div class="text">Dashboard</div>
            </a>
            <a href="../admin/catalog.php" class="nav-item active">
                <div class="icon">
                    <img src="../images/Vector.svg" alt="Catalog" width="20" height="20">
                </div>
                <div class="text">Catalog</div>
            </a>
            <a href="../admin/book-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/book.png" alt="Books" width="24" height="24">
                </div>
                <div class="text">Books</div>
            </a>
            <a href="../admin/user-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/people 3.png" alt="Users" width="24" height="24">
                </div>
                <div class="text">Users</div>
            </a>
            <a href="../admin/branch-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/buildings-2 1.png" alt="Branches" width="24" height="24">
                </div>
                <div class="text">Branches</div>
            </a>
            <a href="../admin/borrowers-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/user.png" alt="Borrowers" width="24" height="24">
                </div>
                <div class="text">Borrowers</div>
            </a>
        </div>
        <a href="../admin/admin-logout.php" class="nav-item">
            <div class="icon">
                <img src="../images/logout 3.png" alt="Log Out" width="24" height="24">
            </div>
            <div class="text">Log Out</div>
        </a>
    </div>
    <div class="content">
        <div class="header">
            <div class="admin-profile">
                <div class="admin-info">
                    <span class="admin-name-1">Welcome, <?= htmlspecialchars($adminFirstName) ?></span>
                </div>
            </div>
        </div>
        <div class="catalog-container">
            <div class="catalog-header">
                <h1 class="catalog-title">Book Catalog</h1>
                <div class="search-add-container">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Search books...">
                    </div>
                </div>
            </div>
            <div class="books-grid">
                <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <img src="<?= htmlspecialchars($book['cover_image_url'] ?? '../images/default-book-cover.jpg') ?>" 
                         alt="<?= htmlspecialchars($book['title']) ?>" 
                         class="book-cover">
                    <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                    <p class="book-author"><?= htmlspecialchars($book['author']) ?></p>
                    <span class="book-category"><?= htmlspecialchars($book['category_name'] ?? 'Uncategorized') ?></span>
                    <div class="book-actions">
                        <button class="action-btn edit-btn" onclick="editBook(<?= $book['book_id'] ?>)">Edit</button>
                        <button class="action-btn delete-btn" onclick="deleteBook(<?= $book['book_id'] ?>)">Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Book</h2>
            <form id="editBookForm">
                <input type="hidden" id="edit_book_id" name="book_id">
                <div class="form-group">
                    <label for="edit_title">Title</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit_author">Author</label>
                    <input type="text" id="edit_author" name="author" required>
                </div>
                <div class="form-group">
                    <label for="edit_type">Type</label>
                    <input type="text" id="edit_type" name="type" required>
                </div>
                <div class="form-group">
                    <label for="edit_language">Language</label>
                    <input type="text" id="edit_language" name="language" required>
                </div>
                <div class="form-buttons">
                    <button type="button" class="delete-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="add-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Book</h2>
                <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="warning-icon">!</div>
                <p class="delete-message">Are you sure you want to delete this book?</p>
                <div class="button-group">
                    <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button class="delete-confirm-btn" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div id="notificationContainer"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            const body = document.body;

            // Create overlay element
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);

            function toggleMenu() {
                mobileMenuBtn.classList.toggle('active');
                sidebar.classList.toggle('active');
                content.classList.toggle('sidebar-active');
                overlay.classList.toggle('active');
                body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            mobileMenuBtn.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu);

            // Close menu when clicking a nav item on mobile
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', () => {
                    if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                        toggleMenu();
                    }
                });
            });

            // Handle resize events
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (window.innerWidth > 768) {
                        mobileMenuBtn.classList.remove('active');
                        sidebar.classList.remove('active');
                        content.classList.remove('sidebar-active');
                        overlay.classList.remove('active');
                        body.style.overflow = '';
                    }
                }, 250);
            });

            // Search functionality
            const searchInput = document.querySelector('.search-input');
            const bookCards = document.querySelectorAll('.book-card');

            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                
                bookCards.forEach(card => {
                    const title = card.querySelector('.book-title').textContent.toLowerCase();
                    const author = card.querySelector('.book-author').textContent.toLowerCase();
                    const category = card.querySelector('.book-category').textContent.toLowerCase();
                    
                    if (title.includes(searchTerm) || 
                        author.includes(searchTerm) || 
                        category.includes(searchTerm)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        let currentBookId = null;

        function editBook(bookId) {
            currentBookId = bookId;
            fetch(`get_book.php?id=${bookId}`)
                .then(response => response.json())
                .then(book => {
                    document.getElementById('edit_book_id').value = book.book_id;
                    document.getElementById('edit_title').value = book.title;
                    document.getElementById('edit_author').value = book.author;
                    document.getElementById('edit_type').value = book.type;
                    document.getElementById('edit_language').value = book.language;
                    document.getElementById('editModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching book details');
                });
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            currentBookId = null;
        }

        function deleteBook(bookId) {
            currentBookId = bookId;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            currentBookId = null;
        }

        function confirmDelete() {
            if (!currentBookId) return;

            const formData = new FormData();
            formData.append('book_id', currentBookId);

            fetch('delete_book.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Book deleted successfully', '📚');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('error', data.message || 'Error deleting book', '❌');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Error deleting book', '❌');
            })
            .finally(() => {
                closeDeleteModal();
            });
        }

        // Handle edit form submission
        document.getElementById('editBookForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('edit_book.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Book updated successfully', '✅');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('error', data.message || 'Error updating book', '❌');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Error updating book', '❌');
            })
            .finally(() => {
                closeModal();
            });
        });

        function showNotification(type, message, icon) {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-icon">📚</div>
                <div class="notification-text">${message}</div>
                <div class="notification-close">×</div>
            `;
            
            container.appendChild(notification);
            
            // Trigger reflow for animation
            notification.offsetHeight;
            notification.classList.add('show');
            
            // Close button handler
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.classList.remove('show');
                setTimeout(() => {
                    container.removeChild(notification);
                }, 400);
            });
            
            // Auto close after 3 seconds
            setTimeout(() => {
                if (container.contains(notification)) {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (container.contains(notification)) {
                            container.removeChild(notification);
                        }
                    }, 400);
                }
            }, 3000);
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == editModal) {
                closeModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>

</html>