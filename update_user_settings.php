<?php
session_start();
require 'database/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle profile picture upload
if (isset($_FILES['profilePicture'])) {
    $file = $_FILES['profilePicture'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    // Validate file
    if (!in_array($file['type'], $allowedTypes)) {
        die(json_encode(['error' => 'Invalid file type. Only JPG, PNG and GIF allowed.']));
    }

    if ($file['size'] > $maxSize) {
        die(json_encode(['error' => 'File too large. Maximum size is 5MB.']));
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $uploadPath = 'uploads/profile_pictures/' . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        try {
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            $stmt->execute([$filename, $_SESSION['user_id']]);

            // Update session
            $_SESSION['profile_picture'] = $filename;

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            die(json_encode(['error' => 'Database error occurred.']));
        }
    } else {
        die(json_encode(['error' => 'Failed to upload file.']));
    }
}

// Handle profile picture removal
if (isset($_POST['remove_picture'])) {
    try {
        // Get current profile picture
        $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentPicture = $stmt->fetchColumn();
        
        // Delete file if it exists
        if ($currentPicture && $currentPicture !== 'default.jpg') {
            $filepath = 'uploads/profile_pictures/' . $currentPicture;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        // Update database to default picture
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = 'default.jpg' WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Update session
        $_SESSION['profile_picture'] = 'default.jpg';
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        die(json_encode(['error' => 'Database error occurred.']));
    }
}

// Handle password change
if (isset($_POST['currentPassword']) && isset($_POST['newPassword'])) {
    try {
        // Get user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Direct comparison for now (temporary fix)
        if ($user && $_POST['currentPassword'] === $user['password']) {
            // Update with new password (storing as plain text temporarily)
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $updateStmt->execute([$_POST['newPassword'], $_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    }
    exit;
}

// Redirect back to settings page if no action was taken
// header('Location: user-settings.php');
// exit();