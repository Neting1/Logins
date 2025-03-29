document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetPasswordForm');
    const messageDiv = document.getElementById('resetPasswordMessage');
    
    // Get token from URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    if (!token) {
        messageDiv.textContent = 'Invalid reset link';
        messageDiv.style.color = 'red';
        return;
    }
    
    document.getElementById('token').value = token;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const code = document.getElementById('resetCode').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Validate inputs
        if (!code || !newPassword || !confirmPassword) {
            messageDiv.textContent = 'All fields are required';
            return;
        }
        
        if (newPassword !== confirmPassword) {
            messageDiv.textContent = 'Passwords do not match';
            return;
        }
        
        // Password complexity validation
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        if (!passwordRegex.test(newPassword)) {
            messageDiv.textContent = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
            return;
        }
        
        messageDiv.textContent = 'Resetting password...';
        messageDiv.style.color = 'blue';
        
        fetch('api/reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                code: code,
                new_password: newPassword
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.textContent = 'Password reset successfully! Redirecting to login...';
                messageDiv.style.color = 'green';
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 2000);
            } else {
                messageDiv.textContent = data.message || 'Failed to reset password';
                messageDiv.style.color = 'red';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.textContent = 'An error occurred';
            messageDiv.style.color = 'red';
        });
    });
});