document.addEventListener('DOMContentLoaded', function() {
    // Strict admin verification
    const userData = sessionStorage.getItem('admin');
    if (!userData) {
        window.location.href = 'index.html';
        return;
    }
    
    const admin = JSON.parse(userData);
    
    // Display admin info
    document.getElementById('usernameDisplay').textContent = admin.username;
    document.getElementById('username').value = admin.username;
    document.getElementById('email').value = admin.email;

    // Handle all admin navigation
    document.querySelectorAll('.admin-nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = this.getAttribute('href');
        });
    });

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', function() {
        sessionStorage.removeItem('admin');
        window.location.href = 'index.html';
    });

    // Profile form submission
    document.getElementById('adminProfileForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const messageDiv = document.getElementById('profileMessage');
        messageDiv.textContent = '';
        messageDiv.className = 'message';
        
        // Validate passwords
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (newPassword && newPassword !== confirmPassword) {
            showMessage('Passwords do not match', 'error');
            return;
        }

        const formData = {
            admin_id: admin.id,
            email: document.getElementById('email').value,
            current_password: document.getElementById('currentPassword').value,
            new_password: newPassword
        };

        try {
            showMessage('Updating profile...', 'info');
            
            const response = await fetch('api/admin_update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${admin.token}`
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage('Profile updated successfully!', 'success');
                // Update session with new data
                sessionStorage.setItem('admin', JSON.stringify(data.admin));
            } else {
                showMessage(data.message || 'Update failed', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('An error occurred', 'error');
        }
    });
    
    function showMessage(text, type) {
        const messageDiv = document.getElementById('profileMessage');
        messageDiv.textContent = text;
        messageDiv.className = `message ${type}`;
    }
});