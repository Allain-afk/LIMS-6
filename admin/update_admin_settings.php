<?php
session_start();
require '../database/db_connection.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    
    try {
        // Get admin's current password
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE admin_id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if ($admin && $currentPassword === $admin['password']) {
            // Update password
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
            $stmt->execute([$newPassword, $_SESSION['admin_id']]);
            
            $_SESSION['update_success'] = "Password updated successfully!";
        } else {
            $_SESSION['update_error'] = "Current password is incorrect.";
        }
    } catch (PDOException $e) {
        $_SESSION['update_error'] = "An error occurred while updating the password.";
        error_log($e->getMessage());
    }
    
    header('Location: admin-settings.php');
    exit();
}
?>