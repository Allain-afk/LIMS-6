<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

// Include database connection
$pdo = require '../database/db_connection.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate form data
        $branchName = trim($_POST['branch_name'] ?? '');
        $branchLocation = trim($_POST['branch_location'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');

        // Validate required fields
        if (empty($branchName) || empty($branchLocation)) {
            $_SESSION['error'] = 'Branch name and location are required';
            header('Location: branch-management.php');
            exit;
        }

        // Insert new branch
        $stmt = $pdo->prepare("INSERT INTO branches (branch_name, branch_location, contact_number) VALUES (?, ?, ?)");
        $result = $stmt->execute([$branchName, $branchLocation, $contactNumber]);

        if ($result) {
            $_SESSION['success'] = 'Branch added successfully';
        } else {
            $_SESSION['error'] = 'Failed to add branch';
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }

    // Redirect back to branch management page
    header('Location: branch-management.php');
    exit;
} else {
    // If someone tries to access this file directly without POST data
    header('Location: branch-management.php');
    exit;
}
?> 