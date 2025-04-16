<?php
// This script updates all existing plain text passwords to hashed passwords
// Delete this file after running it once!

// Include the database connection
$pdo = require 'database/db_connection.php';

echo "<h1>Updating passwords to secure hashes</h1>";

try {
    // Get all users with passwords
    $stmt = $pdo->query("SELECT user_id, username, password FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    foreach ($users as $user) {
        // Skip passwords that already look like hashes (long strings)
        if (strlen($user['password']) < 60) {
            // Hash the plaintext password
            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
            
            // Update the user's password
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $updateStmt->execute([$hashedPassword, $user['user_id']]);
            $count++;
        }
    }
    
    echo "<p>Successfully updated $count passwords.</p>";
    echo "<p>Your system now uses secure password hashing!</p>";
    
} catch (PDOException $e) {
    echo "<p>Error updating passwords: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 