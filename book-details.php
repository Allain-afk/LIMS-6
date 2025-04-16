<?php
// book-details.php - Display book information

$title = isset($_GET['title']) ? urldecode($_GET['title']) : 'Unknown Book';

// Fetch book details from Google Books API
$apiUrl = "https://www.googleapis.com/books/v1/volumes?q=intitle:" . urlencode($title) . "&maxResults=1";
$response = file_get_contents($apiUrl);
$bookData = json_decode($response, true);

$book = $bookData['items'][0]['volumeInfo'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Details: <?php echo htmlspecialchars($title); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .book-cover { max-width: 200px; float: left; margin-right: 20px; }
        .book-info { overflow: hidden; }
        .btn { display: inline-block; padding: 8px 16px; background: #2c3e50; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="user-dashboard.php" class="btn">Back to Dashboard</a>
        
        <?php if ($book): ?>
            <div style="margin-top: 30px;">
                <?php if (isset($book['imageLinks']['thumbnail'])): ?>
                    <img src="<?php echo str_replace('http://', 'https://', $book['imageLinks']['thumbnail']); ?>" 
                         class="book-cover" alt="Book Cover">
                <?php endif; ?>
                
                <div class="book-info">
                    <h1><?php echo htmlspecialchars($book['title'] ?? $title); ?></h1>
                    
                    <?php if (isset($book['authors'])): ?>
                        <p><strong>Author:</strong> <?php echo htmlspecialchars(implode(', ', $book['authors'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($book['publishedDate'])): ?>
                        <p><strong>Published:</strong> <?php echo htmlspecialchars($book['publishedDate']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($book['description'])): ?>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($book['description']); ?></p>
                    <?php endif; ?>
                    
                    <p><strong>Status:</strong> Available</p>
                </div>
            </div>
        <?php else: ?>
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p>Detailed information about this book could not be loaded.</p>
        <?php endif; ?>
    </div>
</body>
</html>
