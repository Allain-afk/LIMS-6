<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in. Session data: " . print_r($_SESSION, true));
    header('Location: index.php');
    exit();
}

// Debug: Log successful dashboard access
error_log("Dashboard accessed by user: " . $_SESSION['username']);

// Include database connection
$pdo = require 'database/db_connection.php';

// Get the logged-in user's full name with null coalescing operator
$firstName = $_SESSION['first_name'] ?? 'User';
$lastName = $_SESSION['last_name'] ?? '';
$userFullName = trim($firstName . ' ' . $lastName);

// Get user's profile picture from database
try {
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profilePicture = $stmt->fetchColumn();
    $_SESSION['profile_picture'] = $profilePicture; // Update session with latest picture
} catch (PDOException $e) {
    error_log("Error fetching profile picture: " . $e->getMessage());
    $profilePicture = 'default.jpg';
}

// Get borrow and return counts
try {
    // Get total borrows count
    $stmtBorrows = $pdo->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ?");
    $stmtBorrows->execute([$_SESSION['user_id']]);
    $totalBorrows = $stmtBorrows->fetchColumn();

    // Get total returns count
    $stmtReturns = $pdo->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ? AND return_date IS NOT NULL");
    $stmtReturns->execute([$_SESSION['user_id']]);
    $totalReturns = $stmtReturns->fetchColumn();

    // Get total books count
    $stmtBooks = $pdo->prepare("SELECT COUNT(*) FROM books");
    $stmtBooks->execute();
    $totalBooks = $stmtBooks->fetchColumn();
} catch (PDOException $e) {
    $totalBorrows = 0;
    $totalReturns = 0;
    $totalBooks = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        function updateTime() {
            const now = new Date();

            // Update time
            const timeString = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('current-time').textContent = timeString;

            // Update date
            const dateString = now.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
            document.getElementById('current-date').textContent = dateString;
        }

        // Update time every second
        setInterval(updateTime, 1000);

        // Initial call to display time immediately
        document.addEventListener('DOMContentLoaded', updateTime);
    </script>
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
                <div class="user-icon" onclick="openSettingsModal()" style="cursor: pointer;">
                    <?php
                    $profilePicturePath = $profilePicture !== 'default.jpg' 
                        ? 'uploads/profile_pictures/' . $profilePicture 
                        : 'images/user.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($profilePicturePath); ?>" alt="User" class="profile-picture">
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($userFullName); ?></div>
                    <div class="user-role">User</div>
                </div>
            </div>
            <div class="quote-container" style="flex: 1; margin: 15px 0 0 20px; max-width: 550px; text-align: center;">
                <div id="quoteCarousel" class="quotes-carousel" style="height: 100%; display: flex; flex-direction: column; justify-content: center;">
                    <div class="quote-slide fade active" style="margin: 0 0 12px 0;">
                        <div class="quote-text" style="font-size: 16px; line-height: 1.4; margin-bottom: 6px; font-weight: 500;">"A book is a gateway to other worlds, a key to unlock imagination's door."</div>
                        <div class="quote-author" style="font-size: 13px; font-style: italic;">~ Neil Gaiman</div>
                    </div>
                    <div class="quote-slide fade" style="margin: 0 0 12px 0;">
                        <div class="quote-text" style="font-size: 16px; line-height: 1.4; margin-bottom: 6px; font-weight: 500;">"The more that you read, the more things you will know. The more that you learn, the more places you'll go."</div>
                        <div class="quote-author" style="font-size: 13px; font-style: italic;">~ Dr. Seuss</div>
                    </div>
                </div>
            </div>
            <div class="time">
                <div id="current-time" class="current-time"></div>
                <div id="current-date" class="current-date"></div>
            </div>
            <div class="settings-icon">
                <img src="images/Vector.png" alt="Settings" style="cursor: pointer;" onclick="openSettingsModal()">
            </div>
        </div>

        <div class="dashboard-grid">
            <a href="user-return-books.php?tab=borrowed" class="card-link">
                <div class="card">
                    <div class="card-icon">
                        <img src="images/book-square 2.png" alt="Borrowed Books">
                    </div>
                    <div class="card-content">
                        <h3>Your Borrowed Books</h3>
                        <p>Track and manage your currently borrowed books. Stay updated on due dates and book status.</p>
                        <div class="card-stats">
                            <span class="stat-number"><?php echo $totalBorrows - $totalReturns; ?></span>
                            <span class="stat-label">Currently Borrowed</span>
                        </div>
                    </div>
                </div>
            </a>

            <a href="user-return-books.php?tab=returned" class="card-link">
                <div class="card">
                    <div class="card-icon">
                        <img src="images/redo 1.png" alt="Returned Books">
                    </div>
                    <div class="card-content">
                        <h3>Your Return History</h3>
                        <p>View your complete book return history and past borrowing activities.</p>
                        <div class="card-stats">
                            <span class="stat-number"><?php echo $totalReturns; ?></span>
                            <span class="stat-label">Books Returned</span>
                        </div>
                    </div>
                </div>
            </a>

            <a href="user-borrow-books.php" class="card-link">
                <div class="card">
                    <div class="card-icon">
                        <img src="images/browse.png" alt="Browse Books">
                    </div>
                    <div class="card-content">
                        <h3>Browse Available Books</h3>
                        <p>Discover and borrow from our extensive collection of books and resources.</p>
                        <div class="card-stats">
                            <span class="stat-number"><?php echo $totalBooks; ?></span>
                            <span class="stat-label">Total Books</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div style="margin: 25px auto 0; padding: 25px; width: 100%; max-width: 98%; text-align: center; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(176, 113, 84, 0.1);">
            <h3 style="margin-bottom: 20px; color: #B07154; font-size: 1.4rem; font-weight: 600;">Books You Might Like</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; padding: 0 8px;" id="popular-books-container">
                <div style="grid-column: 1/-1; padding: 15px; color: #7f8c8d;">Loading recommendations...</div>
            </div>
        </div>

        <script>
        // Function to load books from cache or API
        async function loadBooks() {
            try {
                // Try to get cached books and timestamp
                const cachedData = localStorage.getItem('recommendedBooks');
                const lastUpdate = localStorage.getItem('lastBooksUpdate');
                const now = new Date().getTime();
                
                // Check if cache exists and is less than 24 hours old
                if (cachedData && lastUpdate && (now - parseInt(lastUpdate) < 24 * 60 * 60 * 1000)) {
                    // Use cached data
                    updateBooksDisplay(JSON.parse(cachedData));
                    return;
                }

                // If cache is old or doesn't exist, fetch new data
                const response = await fetch('https://www.googleapis.com/books/v1/volumes?q=subject:fiction&maxResults=24');
                const data = await response.json();
                
                // Cache the new data
                localStorage.setItem('recommendedBooks', JSON.stringify(data));
                localStorage.setItem('lastBooksUpdate', now.toString());
                
                // Display the books
                updateBooksDisplay(data);
            } catch (error) {
                document.getElementById('popular-books-container').innerHTML = 
                    '<div style="grid-column:1/-1; padding:20px; color:#B07154; font-size:14px;">Unable to load recommendations at the moment. Please try again later.</div>';
            }
        }

        // Function to display books
        function updateBooksDisplay(data) {
            const container = document.getElementById('popular-books-container');
            container.innerHTML = '';
            
            // Define an array of fallback cover colors
            const coverColors = [
                '#B07154', '#95604A', '#C88D75', '#A3634E', '#D4A08F'
            ];
            
            data.items?.forEach((book, index) => {
                // Try to get the best quality image available
                const imageLinks = book.volumeInfo.imageLinks || {};
                const bookCover = imageLinks.thumbnail || imageLinks.smallThumbnail;
                
                // Create a dynamic fallback cover if no image is available
                const fallbackCover = `data:image/svg+xml,${encodeURIComponent(`
                    <svg width="120" height="170" xmlns="http://www.w3.org/2000/svg">
                        <rect width="100%" height="100%" fill="${coverColors[index % coverColors.length]}"/>
                        <text x="50%" y="50%" font-family="Arial" font-size="14" fill="white" text-anchor="middle" dy=".3em">
                            ${book.volumeInfo.title.substring(0, 20)}
                        </text>
                    </svg>
                `)}`;

                const thumb = (bookCover ? bookCover.replace('http://', 'https://') : fallbackCover);
                
                container.innerHTML += `
                    <div style="transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <a href="book-details.php?title=${encodeURIComponent(book.volumeInfo.title)}" style="text-decoration:none;">
                            <div style="position: relative; width: 100%; padding-bottom: 142%; margin-bottom: 6px;">
                                <img src="${thumb}" 
                                     alt="${book.volumeInfo.title}"
                                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 15px rgba(176, 113, 84, 0.1);"
                                     onerror="this.onerror=null; this.src='${fallbackCover}';">
                            </div>
                            <div style="padding: 0 2px;">
                                <div style="font-size:11px; font-weight:600; color:#B07154; margin-bottom:2px; line-height:1.3; height:28px; overflow:hidden;">
                                    ${book.volumeInfo.title.substring(0,40)}${book.volumeInfo.title.length > 40 ? '...' : ''}
                                </div>
                                <div style="font-size:10px; color:#666; line-height:1.2; height:12px; overflow:hidden;">
                                    ${book.volumeInfo.authors ? book.volumeInfo.authors[0].substring(0,25) : 'Unknown Author'}
                                </div>
                            </div>
                        </a>
                    </div>`;
            });

            // Add last updated time indicator
            const lastUpdateTime = new Date(parseInt(localStorage.getItem('lastBooksUpdate')));
            container.insertAdjacentHTML('afterend', `
                <div style="text-align: right; font-size: 10px; color: #666; margin-top: 10px; padding-right: 10px;">
                    Last updated: ${lastUpdateTime.toLocaleDateString()} ${lastUpdateTime.toLocaleTimeString()}
                </div>
            `);
        }

        // Load books when page loads
        document.addEventListener('DOMContentLoaded', loadBooks);

        // Optional: Add a refresh button
        const refreshButton = document.createElement('button');
        refreshButton.innerHTML = 'Refresh Books';
        refreshButton.style.cssText = `
            background: #B07154;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            margin-bottom: 15px;
            transition: background 0.3s ease;
        `;
        refreshButton.onmouseover = () => refreshButton.style.background = '#95604A';
        refreshButton.onmouseout = () => refreshButton.style.background = '#B07154';
        refreshButton.onclick = () => {
            localStorage.removeItem('recommendedBooks');
            localStorage.removeItem('lastBooksUpdate');
            loadBooks();
        };
        document.querySelector('.books-section h3').after(refreshButton);
        </script>

        <style>
            .card-stats {
                margin-top: 12px;
                padding-top: 10px;
                border-top: 1px solid rgba(176, 113, 84, 0.1);
                text-align: center;
            }
            .stat-number {
                display: block;
                font-size: 20px;
                font-weight: 700;
                color: #B07154;
                margin-bottom: 2px;
            }
            .stat-label {
                font-size: 12px;
                color: #666;
            }
            .dashboard-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                padding: 20px;
                max-width: 98%;
                margin: 0 auto;
            }
            .card {
                background: #FFFFFF;
                border-radius: 12px;
                padding: 15px;
                box-shadow: 0 4px 15px rgba(176, 113, 84, 0.1);
                transition: all 0.3s ease;
                height: 100%;
                max-height: 220px;
            }
            .card-icon {
                width: 40px;
                height: 40px;
                margin-bottom: 12px;
            }
            .card-icon img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            .card-content h3 {
                color: #B07154;
                font-size: 15px;
                font-weight: 600;
                margin-bottom: 8px;
                line-height: 1.3;
            }
            .card-content p {
                color: #666;
                font-size: 12px;
                line-height: 1.4;
                margin-bottom: 5px;
                display: -webkit-box;
                display: box;
                -webkit-line-clamp: 2;
                line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                height: 34px;
            }
            @media screen and (max-width: 1024px) {
                .dashboard-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media screen and (max-width: 768px) {
                .dashboard-grid {
                    grid-template-columns: 1fr;
                }
                .card {
                    max-height: 200px;
                }
            }
        </style>

        <script>
            function handleLogout() {
                window.location.href = 'logout.php';
            }
        </script>

        <!-- Settings Modal -->
        <div id="settingsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Settings</h2>
                    <span class="close" onclick="closeSettingsModal()">×</span>
                </div>
                <div class="settings-form-container">
                    <!-- Profile Picture Section -->
                    <div class="profile-section">
                        <h3>Profile Picture</h3>
                        <div class="profile-picture-container" style="margin-bottom: 20px;">
                            <?php
                            $modalProfilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== 'default.jpg' 
                                ? 'uploads/profile_pictures/' . $_SESSION['profile_picture'] 
                                : 'images/user.png';
                            ?>
                            <div class="profile-icon">
                                <img src="<?php echo htmlspecialchars($modalProfilePicture); ?>" alt="Profile Picture" class="profile-picture">
                            </div>
                            <div class="profile-picture-actions">
                                <input type="file" id="settings-profile-picture-input" accept="image/*" style="display: none;">
                                <label for="settings-profile-picture-input" class="action-btn change-btn">Change Picture</label>
                                <button type="button" onclick="removeProfilePicture()" class="action-btn remove-btn">Remove Picture</button>
                            </div>
                        </div>
                    </div>

                    <div style="border-top: 1px solid #eee; margin: 20px 0;"></div>

                    <!-- Password Change Section -->
                    <div class="password-section">
                        <h3>Change Password</h3>

                        <form id="settingsForm" onsubmit="return changePassword(event)">
                            <div class="form-group">
                                <label for="currentPassword">Current Password</label>
                                <div class="input-container">
                                    <input type="password" id="currentPassword" name="currentPassword" required>
                                    <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('currentPassword', this)"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <div class="input-container">
                                    <input type="password" id="newPassword" name="newPassword" required>
                                    <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('newPassword', this)"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">Confirm New Password</label>
                                <div class="input-container">
                                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                                    <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
                                </div>
                            </div>

                            <div class="button-group">
                                <button type="button" class="cancel-btn" onclick="closeSettingsModal()">Cancel</button>
                                <button type="submit" class="confirm-btn">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .settings-form-container {
                padding: 20px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .input-container {
                position: relative;
            }

            .toggle-password {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                cursor: pointer;
                color: #666;
            }

            .button-group {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 20px;
            }

            .cancel-btn {
                background-color: #F4DECB;
                color: #B07154;
                padding: 8px 16px;
                border-radius: 6px;
                cursor: pointer;
                border: none;
                font-weight: 500;
            }

            .confirm-btn {
                background-color: #B07154;
                color: white;
                padding: 8px 16px;
                border-radius: 6px;
                cursor: pointer;
                border: none;
                font-weight: 500;
            }

            .profile-picture-container {
                text-align: center;
                margin-bottom: 20px;
            }

            .profile-icon {
                width: 120px;
                height: 120px;
                margin: 0 auto 15px;
                border-radius: 50%;
                overflow: hidden;
            }

            .profile-picture {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .modal {
                display: none;
                position: fixed;
                z-index: 9999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                align-items: center;
                justify-content: center;
            }

            .modal.show {
                display: flex !important;
            }

            .modal-content {
                background-color: #fff;
                margin: auto;
                width: 90%;
                max-width: 500px;
                border-radius: 12px;
                position: relative;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .modal-header {
                padding: 15px 20px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .modal-header h2 {
                margin: 0;
                color: #333;
                font-size: 18px;
            }

            .close {
                font-size: 24px;
                color: #666;
                cursor: pointer;
                border: none;
                background: none;
                padding: 0;
                line-height: 1;
            }

            .profile-section h3, 
            .password-section h3 {
                color: #333;
                font-size: 16px;
                margin-bottom: 15px;
            }

            .profile-picture-actions {
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-top: 15px;
            }

            .action-btn {
                padding: 8px 16px;
                border-radius: 6px;
                cursor: pointer;
                border: none;
                font-weight: 500;
            }

            .change-btn {
                background-color: #F4DECB;
                color: #B07154;
            }

            .remove-btn {
                background-color: #B07154;
                color: white;
            }
        </style>

        <script>
            // Add profile picture upload handler for settings modal
            document.addEventListener('DOMContentLoaded', function() {
                const settingsProfilePictureInput = document.getElementById('settings-profile-picture-input');
                if (settingsProfilePictureInput) {
                    settingsProfilePictureInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const formData = new FormData();
                            formData.append('profilePicture', file);

                            fetch('update_user_settings.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                window.location.reload();
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while uploading the profile picture');
                            });
                        }
                    });
                }
            });

            function openSettingsModal() {
                const modal = document.getElementById('settingsModal');
                if (modal) {
                    modal.classList.add('show');
                    console.log('Modal opened'); // Debug log
                }
            }

            function closeSettingsModal() {
                const modal = document.getElementById('settingsModal');
                if (modal) {
                    modal.classList.remove('show');
                    console.log('Modal closed'); // Debug log
                }
            }

            // Add event listener for clicking outside the modal
            window.onclick = function(event) {
                const modal = document.getElementById('settingsModal');
                if (event.target == modal) {
                    closeSettingsModal();
                }
            }

            function togglePassword(inputId, icon) {
                const input = document.getElementById(inputId);
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            }

            function removeProfilePicture() {
                fetch('update_user_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'remove_picture=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload(); // Reload the page to show default picture
                    } else {
                        showNotification('Failed to remove profile picture', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while removing the profile picture', 'error');
                });
            }

            function showNotification(message, type = 'success') {
                const notification = document.getElementById('notification');
                const messageElement = document.getElementById('notification-message');
                const icon = notification.querySelector('i');
                
                // Remove existing classes
                notification.classList.remove('success', 'error');
                
                // Add appropriate class and icon
                if (type === 'success') {
                    notification.classList.add('success');
                    icon.className = 'fas fa-check-circle';
                } else {
                    notification.classList.add('error');
                    icon.className = 'fas fa-times-circle';
                }
                
                messageElement.textContent = message;
                notification.classList.add('show');
                
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
            }

            function changePassword(event) {
                event.preventDefault();
                
                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (newPassword !== confirmPassword) {
                    showNotification('New passwords do not match!', 'error');
                    return false;
                }

                const formData = new FormData();
                formData.append('currentPassword', currentPassword);
                formData.append('newPassword', newPassword);

                fetch('update_user_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Password updated successfully!', 'success');
                        document.getElementById('settingsForm').reset();
                        closeSettingsModal();
                    } else {
                        showNotification(data.error || 'Failed to update password', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while updating the password', 'error');
                });

                return false;
            }
        </script>
    </div>

    <!-- Add this notification div -->
    <div id="notification" class="notification">
        <div class="notification-content">
            <i class="fas fa-check-circle"></i>
            <span id="notification-message"></span>
        </div>
    </div>

    <style>
        .notification {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }

        .notification.success {
            background-color: #B07154;
            color: white;
        }

        .notification.error {
            background-color: #FF4D4D;
            color: white;
        }

        .notification.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification i {
            font-size: 20px;
        }
    </style>
</body>

</html>