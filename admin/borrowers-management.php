<?php
// Start session at the very beginning of the file
session_start();

// At the top of the file, after session_start()
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/admin-login.php');
    exit();
}

// Get admin name from session
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';
$adminLastName = $_SESSION['admin_last_name'] ?? '';

// Include the database connection
$pdo = require '../database/db_connection.php';

// Fetch borrowed books from database outside of the HTML
try {
    $stmt = $pdo->query('
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
        ORDER BY 
            bb.due_date ASC
    ');
    $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching borrowers: " . $e->getMessage());
    $borrowers = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowers Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/book-management.css">
    <style>
        .status-overdue {
            color: #FF4D4F;
            font-weight: 600;
        }

        .status-active {
            color: rgb(196, 26, 26);
            font-weight: 600;
        }

        .status-returned {
            color: #52C41A;
            font-weight: 600;
        }

        /* Borrower details modal styles */
        .borrower-details {
            padding: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .detail-label {
            font-weight: 600;
            width: 150px;
            color: #555;
        }

        .detail-value {
            flex: 1;
        }

        .close-btn {
            background-color: #f0f0f0;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .close-btn:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>

<body>
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
            <a href="../admin/catalog.php" class="nav-item">
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
            <a href="../admin/borrowers-management.php" class="nav-item active">
                <div class="icon">
                    <img src="../images/user.png" alt="Borrowers" width="24" height="24">
                </div>
                <div class="text">Borrowers</div>
            </a>

        </div>
        <a href="../admin/admin-logout.php" class="nav-item logout">
            <div class="icon">
                <img src="../images/logout 3.png" alt="Log Out" width="24" height="24">
            </div>
            <div class="text">Log Out</div>
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <div class="user-profile">
                    <div class="user-icon">
                        <img src="../images/user.png" alt="User Icon">
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($adminFirstName . ' ' . $adminLastName) ?></div>
                        <div class="user-role">Admin</div>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="datetime-profile">
                    <div class="datetime">
                        <div class="time" id="current-time"></div>
                        <div class="date" id="current-date"></div>
                    </div>
                    <div class="vertical-line"></div>
                    <div class="settings-icon">
                        <img src="../images/Vector.jpg" alt="Settings">
                    </div>
                </div>
            </div>
        </div>

        <div class="page-title">Borrowers Management</div>

        <div class="actions-container">
            <button class="add-book-btn">
                <div class="add-book-icon"></div>
                <div class="add-book-text">Add Borrower</div>
            </button>
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search by ID or Name">
                </div>
            </div>
        </div>

        <div class="content-table">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Borrow ID</th>
                            <th>Borrower Name</th>
                            <th>Book Title</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowers as $borrower):
                            // Check if book is overdue
                            $dueDate = new DateTime($borrower['due_date']);
                            $today = new DateTime();
                            $isOverdue = $dueDate < $today && $borrower['return_date'] === null;

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
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($borrower['id']) ?></td>
                                <td><?= htmlspecialchars($borrower['FirstName'] . ' ' . $borrower['LastName']) ?></td>
                                <td><?= htmlspecialchars($borrower['title']) ?></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($borrower['borrow_date']))) ?></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($borrower['due_date']))) ?></td>
                                <td class="<?= $statusClass ?>"><?= $status ?></td>
                                <td class="action-cell">
                                    <button class="action-btn view-btn" data-borrow-id="<?= $borrower['id'] ?>">
                                        <img src="../images/btn view.svg" alt="View">
                                    </button>
                                    <?php if ($borrower['return_date'] === null): ?>
                                        <button class="action-btn return-btn" data-borrow-id="<?= $borrower['id'] ?>">
                                            <img src="../images/btn edit.png" alt="Return">
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($borrowers)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No borrowers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Borrower Modal -->
    <div id="addBorrowerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Borrower</h2>
                <span class="close">&times;</span>
            </div>
            <form id="addBorrowerForm" method="POST">
                <div class="form-group">
                    <label for="user_id">Select User</label>
                    <select id="user_id" name="user_id" required>
                        <option value="">Select User</option>
                        <?php
                        try {
                            $userStmt = $pdo->query('SELECT user_id, FirstName, LastName FROM users ORDER BY FirstName');
                            $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users as $user) {
                                echo '<option value="' . $user['user_id'] . '">' .
                                    htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) . '</option>';
                            }
                        } catch (PDOException $e) {
                            error_log("Error fetching users: " . $e->getMessage());
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="book_id">Select Book</label>
                    <select id="book_id" name="book_id" required>
                        <option value="">Select Book</option>
                        <?php
                        try {
                            $bookStmt = $pdo->query('SELECT book_id, title FROM books WHERE availability = "Available" ORDER BY title');
                            $books = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($books as $book) {
                                echo '<option value="' . $book['book_id'] . '">' .
                                    htmlspecialchars($book['title']) . '</option>';
                            }
                        } catch (PDOException $e) {
                            error_log("Error fetching books: " . $e->getMessage());
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="borrow_date">Borrow Date</label>
                    <input type="date" id="borrow_date" name="borrow_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="submit-btn">Add Borrower</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return Book Modal -->
    <div id="returnBookModal" class="modal">
        <div class="modal-content">
            <span class="close close-return">&times;</span>
            <h2>Return Book</h2>
            <form id="returnBookForm">
                <input type="hidden" id="return_borrow_id" name="borrow_id">
                <input type="hidden" name="return_date" value="<?php echo date('Y-m-d'); ?>">
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Confirm Return</button>
                    <button type="button" class="cancel-btn close-return">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Borrower Modal -->
    <div id="viewBorrowerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Borrower Details</h2>
                <span class="close-view">&times;</span>
            </div>
            <div class="borrower-details">
                <div class="detail-row">
                    <div class="detail-label">Borrow ID:</div>
                    <div class="detail-value" id="view-borrow-id"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Borrower Name:</div>
                    <div class="detail-value" id="view-borrower-name"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Book Title:</div>
                    <div class="detail-value" id="view-book-title"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Borrow Date:</div>
                    <div class="detail-value" id="view-borrow-date"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Due Date:</div>
                    <div class="detail-value" id="view-due-date"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value" id="view-status"></div>
                </div>
                <div class="detail-row" id="return-date-row">
                    <div class="detail-label">Return Date:</div>
                    <div class="detail-value" id="view-return-date"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="close-btn">Close</button>
            </div>
        </div>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();

            // Update time
            const timeDiv = document.getElementById('current-time');
            timeDiv.textContent = now.toLocaleString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            // Update date
            const dateDiv = document.getElementById('current-date');
            dateDiv.textContent = now.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
        }

        // Update immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Get modal elements
        const modal = document.getElementById('addBorrowerModal');
        const addBorrowerBtn = document.querySelector('.add-book-btn');
        const closeBtn = document.querySelector('.close');
        const cancelBtn = document.querySelector('.cancel-btn');
        const form = document.getElementById('addBorrowerForm');

        // View modal elements
        const viewModal = document.getElementById('viewBorrowerModal');
        const closeViewBtn = document.querySelector('.close-view');
        const closeModalBtn = document.querySelector('.close-btn');

        // Open modal
        addBorrowerBtn.onclick = function() {
            modal.style.display = 'block';
        }

        // Close modal
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        cancelBtn.onclick = function() {
            modal.style.display = 'none';
        }

        // Close view modal
        closeViewBtn.onclick = function() {
            viewModal.style.display = 'none';
        }

        closeModalBtn.onclick = function() {
            viewModal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
            if (event.target == returnModal) {
                returnModal.style.display = 'none';
            }
            if (event.target == viewModal) {
                viewModal.style.display = 'none';
            }
        }

        // View button functionality
        document.querySelectorAll('.view-btn').forEach(button => {
            button.onclick = async function() {
                const borrowId = this.dataset.borrowId;

                try {
                    const response = await fetch(`get-borrower.php?id=${borrowId}`);
                    const borrower = await response.json();

                    if (borrower) {
                        // Populate the modal with borrower details
                        document.getElementById('view-borrow-id').textContent = borrower.id;
                        document.getElementById('view-borrower-name').textContent = borrower.borrower_name;
                        document.getElementById('view-book-title').textContent = borrower.book_title;
                        document.getElementById('view-borrow-date').textContent = borrower.borrow_date;
                        document.getElementById('view-due-date').textContent = borrower.due_date;

                        // Set status with appropriate class
                        const statusElement = document.getElementById('view-status');
                        statusElement.textContent = borrower.status;
                        statusElement.className = 'detail-value ' + borrower.status_class;

                        // Handle return date display
                        const returnDateRow = document.getElementById('return-date-row');
                        const returnDateValue = document.getElementById('view-return-date');

                        if (borrower.return_date) {
                            returnDateRow.style.display = '';
                            returnDateValue.textContent = borrower.return_date;
                        } else {
                            returnDateRow.style.display = 'none';
                        }

                        // Show the modal
                        viewModal.style.display = 'block';
                    } else {
                        alert('Error: Borrower details not found');
                    }
                } catch (error) {
                    alert('Error fetching borrower details: ' + error.message);
                }
            };
        });

        // Handle form submission
        form.onsubmit = async function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            try {
                const response = await fetch('add-borrower.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Show success popup
                    const successPopup = document.createElement('div');
                    successPopup.className = 'modal success-modal';
                    successPopup.innerHTML = `
                        <div class="modal-content success-content">
                            <div class="success-header">
                                <img src="images/image 1.png" alt="Logo" class="success-logo">
                                <h2>Success!</h2>
                            </div>
                            <p>Borrower added successfully!</p>
                            <div class="success-footer">
                                <img src="images/logo3.png" alt="Footer Logo" class="footer-logo">
                            </div>
                        </div>
                    `;
                    document.body.appendChild(successPopup);

                    // Close add borrower modal
                    modal.style.display = 'none';

                    // Clear form
                    form.reset();

                    // Remove success popup and refresh page after delay
                    setTimeout(() => {
                        successPopup.remove();
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error adding borrower: ' + error.message);
            }
        }

        // Return book functionality
        const returnModal = document.getElementById('returnBookModal');
        const returnForm = document.getElementById('returnBookForm');
        const closeReturnBtns = document.querySelectorAll('.close-return');

        // Return button functionality
        document.querySelectorAll('.return-btn').forEach(button => {
            button.onclick = function() {
                const borrowId = this.dataset.borrowId;
                document.getElementById('return_borrow_id').value = borrowId;
                returnModal.style.display = 'block';
            };
        });

        // Close return modal
        closeReturnBtns.forEach(btn => {
            btn.onclick = function() {
                returnModal.style.display = 'none';
            };
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == returnModal) {
                returnModal.style.display = 'none';
            }
        };

        // Handle return form submission
        returnForm.onsubmit = async function(e) {
            e.preventDefault();
            const formData = new FormData(returnForm);

            try {
                const response = await fetch('../return-book.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                if (result.success) {
                    // Show success message
                    const successPopup = document.createElement('div');
                    successPopup.className = 'modal success-modal';
                    successPopup.innerHTML = `
                        <div class="modal-content success-content">
                            <div class="success-header">
                                <img src="../images/image 1.png" alt="Logo" class="success-logo">
                                <h2>Success!</h2>
                            </div>
                            <p>Book returned successfully!</p>
                            <div class="success-footer">
                                <img src="../images/logo3.png" alt="Footer Logo" class="footer-logo">
                            </div>
                        </div>
                    `;
                    document.body.appendChild(successPopup);

                    // Close return modal
                    returnModal.style.display = 'none';

                    // Remove success popup and refresh page after delay
                    setTimeout(() => {
                        successPopup.remove();
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + (result.message || 'Failed to return book'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error returning book: ' + error.message);
            }
        };

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.querySelector('tbody');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');
            let hasVisibleRows = false;

            // Remove existing "no results" row if it exists
            const existingNoResults = document.querySelector('.no-results');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            // Filter rows
            Array.from(rows).forEach(row => {
                // Skip the "no results" row if it exists
                if (row.classList.contains('no-results')) return;

                const cells = row.getElementsByTagName('td');
                const rowText = Array.from(cells).reduce((text, cell) => {
                    return text + ' ' + cell.textContent.toLowerCase();
                }, '');

                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show "No results" message if no matches
            if (!hasVisibleRows) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                noResultsRow.innerHTML = '<td colspan="7" style="text-align: center;">No matching borrowers found</td>';
                tableBody.appendChild(noResultsRow);
            }
        });
    </script>
</body>

</html>