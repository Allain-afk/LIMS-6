document.addEventListener('DOMContentLoaded', function() {
    fetch('https://www.googleapis.com/books/v1/volumes?q=subject:fiction&maxResults=8')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('popular-books-container');
            container.innerHTML = '';
            
            data.items?.forEach(book => {
                const thumb = book.volumeInfo.imageLinks?.thumbnail?.replace('http://', 'https://') || 
                             'https://via.placeholder.com/160x240.png/efefef/999999?text=Book+Cover';
                // Rest of your book display code here
            });
        });
});
