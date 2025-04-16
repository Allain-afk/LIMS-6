<?php
// Start the session
session_start();

// Include the database connection
$pdo = require '../database/db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit();
}

// Fetch admin details
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';

error_log('Admin First Name in Dashboard: ' . ($_SESSION['admin_first_name'] ?? 'Not Set'));

// Get counts from database
try {
    $counts = [
        'users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'books' => $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn(),
        'branches' => $pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn()
    ];
    
    // Debug book information
    $debugBooks = $pdo->query('SELECT book_id, title FROM books ORDER BY book_id')->fetchAll(PDO::FETCH_ASSOC);
    error_log("Book details:");
    foreach ($debugBooks as $book) {
        error_log("ID: {$book['book_id']} - Title: {$book['title']}");
    }
    
} catch (PDOException $e) {
    error_log("Error fetching counts: " . $e->getMessage());
    $counts = ['users' => 0, 'books' => 0, 'branches' => 0];
}

// Function to get admins
function getAdmins($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM admin');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching admins: " . $e->getMessage());
        return [];
    }
}

// Function to get branches
function getBranches($pdo) {
    try {
        $stmt = $pdo->query('SELECT branch_id, branch_name, branch_location FROM branches');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching branches: " . $e->getMessage());
        return [];
    }
}

// Function to get borrowers
function getBorrowers($pdo) {
    try {
        $borrowersQuery = $pdo->query("
            SELECT bb.id, bb.user_id, bb.book_id, bb.borrow_date, bb.due_date, 
                   u.FirstName, u.LastName, b.title
            FROM borrowed_books bb
            JOIN users u ON bb.user_id = u.user_id
            JOIN books b ON bb.book_id = b.book_id
            WHERE bb.return_date IS NULL
            ORDER BY bb.due_date ASC
            LIMIT 5
        ");
        return $borrowersQuery->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching borrowers: " . $e->getMessage());
        return [];
    }
}

$admins = getAdmins($pdo);
$branches = getBranches($pdo);
$borrowers = getBorrowers($pdo);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
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
            <a href="../admin/admin-dashboard.php" class="nav-item active">
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
                    <!-- Display the admin's first name -->
                    <span class="admin-name-1">Welcome, <?= htmlspecialchars($adminFirstName) ?></span>
                </div>
            </div>
        </div>
        <div class="main-content">
            <div class="stats-container">
                <div class="stats-card">
                    <div class="stats-icon-container">
                        <img src="../images/user.png" alt="Total Users" class="stats-icon">
                    </div>
                    <div class="stats-value"><?= htmlspecialchars($counts['users']) ?></div>
                    <div class="stats-label">Total Users</div>
                </div>
                <div class="stats-card">
                    <div class="stats-icon-container">
                        <img src="../images/book.png" alt="Total Books" class="stats-icon">
                    </div>
                    <div class="stats-value"><?= htmlspecialchars($counts['books']) ?></div>
                    <div class="stats-label">Total Book Count</div>
                </div>
                <div class="stats-card">
                    <div class="stats-icon-container">
                        <img src="../images/buildings-2 1.png" alt="Branch Count" class="stats-icon">
                    </div>
                    <div class="stats-value"><?= htmlspecialchars($counts['branches']) ?></div>
                    <div class="stats-label">Branch Count</div>
                </div>
            </div>
            <div class="cards-column">
                <div class="card">
                    <h2 class="card-title">Overdue Borrowers</h2>
                    <div class="borrower-list">
                        <?php if (!empty($borrowers)): ?>
                            <?php foreach ($borrowers as $borrower): ?>
                                <?php
                                $dueDate = new DateTime($borrower['due_date']);
                                $today = new DateTime();
                                $isOverdue = $dueDate < $today;
                                ?>
                                <div class="borrower-item">
                                    <div class="borrower-icon">
                                        <img src="../images/user.png" alt="User" class="borrower-icon-img">
                                    </div>
                                    <div class="borrower-info">
                                        <span class="borrower-name"><?= htmlspecialchars($borrower['FirstName'] . ' ' . $borrower['LastName']) ?></span>
                                        <span class="borrower-id">Borrowed ID: <?= htmlspecialchars($borrower['id']) ?></span>
                                        <?php if ($isOverdue): ?>
                                            <span class="overdue-status">Overdue</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="action-icon">
                                        <img src="../images/btn view.svg" alt="View" class="action-icon-img">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-borrowers-message">
                                <p>No Books Borrowed</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bottom-row">
                    <div class="admins-section">
                        <div class="admins-title">Book King Admins</div>
                        <?php foreach ($admins as $admin): ?>
                            <div class="admin-card">
                                <!-- Left Icon -->
                                <div class="admin-action-icon-left">
                                    <img src="../images/security-user 1.png" alt="Admin Action Left" class="admin-icon-img">
                                </div>

                                <!-- Admin Content -->
                                <div class="admin-content">
                                    <div class="admin-name"><?= htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']) ?></div>
                                    <div class="admin-id">Admin ID : <?= htmlspecialchars($admin['admin_id']) ?></div>
                                    <div class="admin-status">Active</div>
                                    <div class="admin-status-dot"></div>
                                    <div class="admin-divider"></div>
                                </div>

                                <!-- Right Icon -->
                                <div class="admin-action-icon-right">
                                    <img src="../images/maximize-circle 1 (1).png" alt="Admin Action Right" class="admin-icon-img">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="branch-card">
                        <h2 class="branch-title">Branch Network</h2>
                        <div class="branch-list">
                            <?php if (!empty($branches)): ?>
                                <?php foreach ($branches as $branch): ?>
                                    <div class="branch-item">
                                        <div class="branch-icon">
                                            <img src="../images/buildings-2 1.png" alt="Branch Building" class="branch-icon-img">
                                        </div>
                                        <div class="branch-info">
                                            <div class="branch-name"><?= htmlspecialchars($branch['branch_name']) ?></div>
                                            <div class="branch-id"><?= htmlspecialchars($branch['branch_location']) ?></div>
                                        </div>
                                        <div class="maximize-icon">
                                            <img src="../images/maximize-circle 1 (1).png" alt="Maximize">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-branches">No branches registered</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="admins-section">
                <div class="admins-title">Book King Admins</div>
                <?php foreach ($admins as $admin): ?>
                    <div class="admin-card">
                        <!-- Left Icon -->
                        <div class="admin-action-icon-left">
                            <img src="../images/security-user 1.png" alt="Admin Action Left" class="admin-icon-img">
                        </div>

                        <!-- Admin Content -->
                        <div class="admin-content">
                            <div class="admin-name"><?= htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']) ?></div>
                            <div class="admin-id">Admin ID : <?= htmlspecialchars($admin['admin_id']) ?></div>
                            <div class="admin-status">Active</div>
                            <div class="admin-status-dot"></div>
                            <div class="admin-divider"></div>
                        </div>

                        <!-- Right Icon -->
                        <div class="admin-action-icon-right">
                            <img src="../images/maximize-circle 1 (1).png" alt="Admin Action Right" class="admin-icon-img">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
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
        });
    </script>
</body>

</html>