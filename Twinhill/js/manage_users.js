document.addEventListener('DOMContentLoaded', function() {
    const userData = sessionStorage.getItem('user');
    if (!userData) {
        window.location.href = 'index.html';
        return;
    }
    
    const user = JSON.parse(userData);
    if (user.role !== 'admin') {
        window.location.href = 'user_dashboard.html';
        return;
    }
    
    document.getElementById('usernameDisplay').textContent = user.username;
    
    // Logout functionality
    document.getElementById('logoutBtn').addEventListener('click', function() {
        sessionStorage.removeItem('user');
        window.location.href = 'index.html';
    });
    
    // Load users
    loadUsers();
    
    // Add user button
    document.getElementById('addUserBtn').addEventListener('click', function() {
        // Implement add user functionality
        alert('Add user functionality will go here');
    });
    
    // Search functionality
    document.getElementById('searchBtn').addEventListener('click', function() {
        const searchTerm = document.getElementById('searchUsers').value;
        loadUsers(searchTerm);
    });
    
    function loadUsers(searchTerm = '') {
        let url = 'api/get_users.php';
        if (searchTerm) {
            url += `?search=${encodeURIComponent(searchTerm)}`;
        }
        
        fetch(url, {
            headers: {
                'Authorization': `Bearer ${user.token || ''}`
            }
        })
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#usersTable tbody');
            tableBody.innerHTML = '';
            
            if (data.success && data.users.length > 0) {
                data.users.forEach(user => {
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>${user.username}</td>
                        <td>${user.email}</td>
                        <td>${user.role}</td>
                        <td>${new Date(user.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="action-btn edit" onclick="editUser(${user.id})">Edit</button>
                            <button class="action-btn delete" onclick="deleteUser(${user.id})">Delete</button>
                        </td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5">No users found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Make functions available globally
    window.editUser = function(userId) {
        // Implement edit functionality
        alert(`Edit user ${userId} functionality will go here`);
    };
    
    window.deleteUser = function(userId) {
        if (confirm('Are you sure you want to delete this user?')) {
            fetch(`api/delete_user.php?id=${userId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${user.token || ''}`
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User deleted successfully');
                    loadUsers();
                } else {
                    alert(data.message || 'Failed to delete user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting user');
            });
        }
    };
});