<?php
// Include database connection
$pdo = require '../database/db_connection.php';

try {
    // Add missing columns if they don't exist
    $pdo->exec("ALTER TABLE users
        ADD COLUMN IF NOT EXISTS first_name VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS last_name VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS phone VARCHAR(15) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");
    
    echo "Table structure updated successfully";
} catch (PDOException $e) {
    echo "Error updating table structure: " . $e->getMessage();
}
?> 