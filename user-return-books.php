<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include the database connection
$pdo = require 'database/db_connection.php';

// Get user's full name from session
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>User Returned Books Form</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/user-return-books.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="images/logo.png" alt="Book King Logo">
        </div>
        <div class="sidebar-item home" onclick="window.location.href='user-dashboard.php'">
            <img src="images/element-2 2.svg" alt="Home" class="icon-image">
        </div>
        <div class="sidebar-item list" onclick="window.location.href='user-return-books.php'">
            <img src="images/Vector.svg" alt="List" class="icon-image">
        </div>
        <div class="sidebar-item book" onclick="window.location.href='user-borrow-books.php'">
            <img src="images/book.png" alt="Book" class="icon-image">
        </div>
        <div class="sidebar-item logout" onclick="handleLogout()">
            <img src="images/logout 3.png" alt="Logout" class="icon-image">
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="user-info">
                <div class="user-icon">
                    <?php
                    $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== 'default.jpg' 
                        ? 'uploads/profile_pictures/' . $_SESSION['profile_picture'] 
                        : 'images/user.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="User" class="profile-picture">
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($userFullName); ?></div>
                    <div class="user-role">User</div>
                </div>
            </div>
            <div class="time">
                <div class="current-time">12:29 PM</div>
                <div class="current-date">Sep 02, 2023</div>
            </div>
            
            <div class="settings-icon">
                <a href="user-settings.php">
                    <img src="images/Vector.png" alt="Settings">
                </a>
            </div>
        </div>

        <div class="borrowed-books-header">
            <div class="borrowed-books-title">Borrowed Books</div>
        </div>

        <div class="tabs">
            <div class="tab active">Borrowed Books</div>
            <div class="tab inactive">Return Books</div>
        </div>

        <div class="search-container">
            <div class="search-icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.58317 17.5C13.9554 17.5 17.4998 13.9555 17.4998 9.58329C17.4998 5.21104 13.9554 1.66663 9.58317 1.66663C5.21092 1.66663 1.6665 5.21104 1.6665 9.58329C1.6665 13.9555 5.21092 17.5 9.58317 17.5Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M18.3332 18.3333L16.6665 16.6666" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <input type="text" id="searchInput" class="search-input" placeholder="Search by ID, Name, or Type">
        </div>

        <div class="content-card">
            <div class="table-container" id="books-table">
                <div class="table-wrapper">
                    <div class="table-header">
                        <div class="header-cell">ID</div>
                        <div class="header-cell">User ID</div>
                        <div class="header-cell">Amount</div>
                        <div class="header-cell">Due Date</div>
                        <div class="header-cell">Date & Time</div>
                        <div class="header-cell">Action</div>
                    </div>
                    <div id="table-content">
                        <?php
                        // Function to display books
                        function displayBooks($books, $type) {
                            if (empty($books)) {
                                echo "<div class='no-results'>No " . $type . " books found</div>";
                            } else {
                                // Count total borrowed books for the user
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

                        // Fetch books based on initial active tab (Borrowed Books)
                        $user_id = $_SESSION['user_id'];
                        try {
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
                                echo "<div class='no-results'>No borrowed books found</div>";
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
                        } catch (PDOException $e) {
                            echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="book-king-sidebar">
        <div>B</div>
        <div>O</div>
        <div>O</div>
        <div>K</div>
        <div>&nbsp;</div>
        <div>K</div>
        <div>I</div>
        <div>N</div>
        <div>G</div>
    </div>

    <!-- Profile Picture Upload Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProfileModal()">&times;</span>
            <h2>Update Profile Picture</h2>
            <div class="profile-picture-container">
                <?php
                $modalProfilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== 'default.jpg' 
                    ? 'uploads/profile_pictures/' . $_SESSION['profile_picture'] 
                    : 'images/user.png';
                ?>
                <img src="<?php echo htmlspecialchars($modalProfilePicture); ?>" alt="Profile Picture" class="profile-picture">
                <div class="profile-picture-actions">
                    <label for="profile-picture-input" class="btn btn-primary">Change Picture</label>
                    <input type="file" id="profile-picture-input" accept="image/*" style="display: none;">
                    <button class="btn btn-danger" onclick="removeProfilePicture()">Remove Picture</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            max-width: 500px;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .profile-picture-container {
            text-align: center;
            margin: 20px 0;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .profile-picture-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get tab parameter from URL
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            // Get tab elements
            const borrowedTab = document.querySelector('.tab:first-child');
            const returnedTab = document.querySelector('.tab:last-child');
            
            // Set active tab based on URL parameter
            if (tabParam === 'returned') {
                returnedTab.classList.remove('inactive');
                returnedTab.classList.add('active');
                borrowedTab.classList.remove('active');
                borrowedTab.classList.add('inactive');
                loadBooks('returned');
            } else {
                // Default to borrowed tab
                borrowedTab.classList.remove('inactive');
                borrowedTab.classList.add('active');
                returnedTab.classList.remove('active');
                returnedTab.classList.add('inactive');
                loadBooks('borrowed');
            }

            // Add click handlers for tabs
            borrowedTab.addEventListener('click', function() {
                borrowedTab.classList.remove('inactive');
                borrowedTab.classList.add('active');
                returnedTab.classList.remove('active');
                returnedTab.classList.add('inactive');
                loadBooks('borrowed');
                // Update URL without reloading
                history.pushState({}, '', 'user-return-books.php?tab=borrowed');
            });

            returnedTab.addEventListener('click', function() {
                returnedTab.classList.remove('inactive');
                returnedTab.classList.add('active');
                borrowedTab.classList.remove('active');
                borrowedTab.classList.add('inactive');
                loadBooks('returned');
                // Update URL without reloading
                history.pushState({}, '', 'user-return-books.php?tab=returned');
            });

            // Initialize search functionality
            const searchInput = document.querySelector('.search-input');
            searchInput.addEventListener('input', function(e) {
                const searchValue = e.target.value.toLowerCase();
                const activeTab = document.querySelector('.tab.active').textContent.trim();
                updateSearchResults(activeTab === 'Borrowed Books' ? 'borrowed' : 'returned', searchValue);
            });

            // Add profile picture upload handler
            const profilePictureInput = document.getElementById('profile-picture-input');
            if (profilePictureInput) {
                profilePictureInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const formData = new FormData();
                        formData.append('profile_picture', file);

                        fetch('update_user_settings.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('Failed to upload profile picture: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while uploading the profile picture');
                        });
                    }
                });
            }
        });

        function loadBooks(type) {
            fetch(`get-books.php?type=${type}&user_id=<?php echo $user_id; ?>`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('table-content').innerHTML = html;
                    updateSearchResults(type);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('table-content').innerHTML = "<div class='error-message'>Error loading books</div>";
                });
        }

        function updateSearchResults(type, searchValue = '') {
            const tableRows = document.querySelectorAll('.table-row');
            let visibleRows = 0;
            
            // Remove existing no-results message
            const existingNoResults = document.querySelector('.no-results');
            if (existingNoResults) {
                existingNoResults.remove();
            }
            
            if (tableRows.length === 0) {
                const noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-results';
                noResultsMsg.textContent = `No ${type} books found`;
                document.getElementById('table-content').appendChild(noResultsMsg);
                return;
            }
            
            tableRows.forEach(row => {
                const cells = Array.from(row.children).map(cell => cell.textContent.toLowerCase());
                const shouldShow = searchValue === '' || cells.some(cell => cell.includes(searchValue));
                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleRows++;
            });

            if (visibleRows === 0) {
                const noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-results';
                noResultsMsg.textContent = `No ${type} books found matching your search`;
                document.getElementById('table-content').appendChild(noResultsMsg);
            }
        }

        function updateTime() {
            const now = new Date();
            const timeElement = document.querySelector('.current-time');
            const dateElement = document.querySelector('.current-date');

            timeElement.textContent = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });

            dateElement.textContent = now.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
        }

        setInterval(updateTime, 1000);
        updateTime();

        function openReturnConfirm(bookId) {
            const popup = window.open(`user-return-book-confirm.php?id=${bookId}`, 'ReturnBookConfirm',
                'width=1200,height=700,resizable=no');

            const left = (screen.width - 1200) / 2;
            const top = (screen.height - 700) / 2;
            popup.moveTo(left, top);

            window.addEventListener('storage', function(e) {
                if (e.key === 'bookReturned' && e.newValue === 'true') {
                    location.reload();
                    sessionStorage.removeItem('bookReturned');
                }
            });
        }

        function handleLogout() {
            window.location.href = 'logout.php';
        }

        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'block';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }

        function removeProfilePicture() {
            if (confirm('Are you sure you want to remove your profile picture?')) {
                fetch('update_user_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'remove_picture=1'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    // Refresh the page to update all profile picture elements
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the profile picture');
                });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('profileModal');
            if (event.target == modal) {
                closeProfileModal();
            }
        }
    </script>
</body>

</html>