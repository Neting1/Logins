document.addEventListener('DOMContentLoaded', function() {
    const userData = sessionStorage.getItem('user');
    if (!userData) {
        window.location.href = 'index.html';
        return;
    }
    
    const user = JSON.parse(userData);
    document.getElementById('usernameDisplay').textContent = user.username;
    
    // Load user data
    document.getElementById('username').value = user.username;
    document.getElementById('email').value = user.email;
    
    // Logout functionality
    document.getElementById('logoutBtn').addEventListener('click', function() {
        sessionStorage.removeItem('user');
        window.location.href = 'index.html';
    });
    
    // Form submission
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            email: document.getElementById('email').value,
            current_password: document.getElementById('currentPassword').value,
            new_password: document.getElementById('newPassword').value
        };
        
        const messageDiv = document.getElementById('profileMessage');
        messageDiv.textContent = 'Updating profile...';
        messageDiv.className = 'message info';
        
        fetch('api/update_profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${user.token || ''}`
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.textContent = 'Profile updated successfully!';
                messageDiv.className = 'message success';
                
                // Update session storage if email changed
                if (data.user) {
                    sessionStorage.setItem('user', JSON.stringify(data.user));
                }
            } else {
                messageDiv.textContent = data.message || 'Failed to update profile';
                messageDiv.className = 'message error';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.textContent = 'An error occurred';
            messageDiv.className = 'message error';
        });
    });
});