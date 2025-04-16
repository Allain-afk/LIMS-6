<?php
// database/db_connection.php

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=lims",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    return $pdo;
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Connection failed: ' . $e->getMessage());
}
