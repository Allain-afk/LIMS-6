<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../helpers/activity_logger.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=lims",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get admin name from session
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';
$adminLastName = $_SESSION['admin_last_name'] ?? '';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $language = trim($_POST['language'] ?? '');

        if (empty($title) || empty($author) || empty($type) || empty($language)) {
            throw new Exception("All fields are required!");
        }

        // Insert the book
        $stmt = $pdo->prepare("INSERT INTO books (title, author, type, language, availability) VALUES (?, ?, ?, ?, 'Available')");
        $stmt->execute([$title, $author, $type, $language]);

        header("Location: book-management.php");
        exit();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #FEF3E8;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        h1 {
            color: #B07154;
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #F4DECB;
            border-radius: 8px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #B07154;
        }

        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .cancel-btn {
            background: #F4DECB;
            color: #B07154;
        }

        .submit-btn {
            background: #B07154;
            color: white;
        }

        .cancel-btn:hover {
            background: #E8C5B0;
        }

        .submit-btn:hover {
            background: #95604A;
        }

        .error {
            color: #dc3545;
            background-color: #ffe6e6;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            color: #4CAF50;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #4CAF50;
            border-radius: 4px;
            background-color: #E8F5E9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Book</h1>
        
        <?php if ($error_message): ?>
            <div class="error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required
                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="author">Author</label>
                <input type="text" id="author" name="author" required
                       value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="type">Type</label>
                <input type="text" id="type" name="type" required
                       value="<?php echo isset($_POST['type']) ? htmlspecialchars($_POST['type']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="language">Language</label>
                <input type="text" id="language" name="language" required
                       value="<?php echo isset($_POST['language']) ? htmlspecialchars($_POST['language']) : ''; ?>">
            </div>

            <div class="button-group">
                <button type="button" class="cancel-btn" onclick="window.location.href='book-management.php'">Cancel</button>
                <button type="submit" class="submit-btn">Add Book</button>
            </div>
        </form>
    </div>
</body>
</html>
