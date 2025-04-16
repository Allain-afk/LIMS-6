// Toggle Add Branch Form
function toggleAddForm() {
    const form = document.getElementById('addBranchForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

// Edit Branch Functions
function editBranch(branchId, branchName, branchLocation, contactNumber) {
    document.getElementById('editBranchId').value = branchId;
    document.getElementById('editBranchName').value = branchName;
    document.getElementById('editBranchLocation').value = branchLocation;
    document.getElementById('editContactNumber').value = contactNumber;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Delete Branch Function
function confirmDelete(branchId) {
    if (confirm('Are you sure you want to delete this branch?')) {
        window.location.href = `?delete=${branchId}`;
    }
}

// Notification System
function showNotification(type, message) {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    notification.innerHTML = `
        <span class="notification-text">${message}</span>
        <span class="notification-close" onclick="this.parentElement.remove()">&times;</span>
    `;
    
    container.appendChild(notification);
    
    // Show notification
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 400);
    }, 5000);
}

// Mobile Menu
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    const body = document.body;
    
    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    body.appendChild(overlay);
    
    function toggleMenu() {
        mobileMenuBtn.classList.toggle('active');
        sidebar.classList.toggle('active');
        content.classList.toggle('sidebar-active');
        overlay.classList.toggle('active');
        body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }
    
    mobileMenuBtn.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const branchElements = document.querySelectorAll('.branches-table tr:not(:first-child), .mobile-card');
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        branchElements.forEach(element => {
            if (element.classList.contains('no-data-row')) return;
            
            const isTableRow = element.tagName === 'TR';
            const name = isTableRow 
                ? element.querySelector('.branch-name')?.textContent.toLowerCase() || ''
                : element.querySelector('.mobile-card-info .branch-name')?.textContent.toLowerCase() || '';
            const location = isTableRow
                ? element.children[1]?.textContent.toLowerCase() || ''
                : element.querySelector('.mobile-card-info div:nth-child(2)')?.textContent.toLowerCase() || '';
            const contact = isTableRow
                ? element.children[2]?.textContent.toLowerCase() || ''
                : element.querySelector('.mobile-card-info div:nth-child(3)')?.textContent.toLowerCase() || '';
            
            if (name.includes(searchTerm) || 
                location.includes(searchTerm) || 
                contact.includes(searchTerm)) {
                element.style.display = '';
            } else {
                element.style.display = 'none';
            }
        });
    });
}); 