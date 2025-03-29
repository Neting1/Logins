document.addEventListener('DOMContentLoaded', function() {
    // Check if admin is logged in
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
    
    // Load users for dropdown and filter
    loadUsers();
    
    // Load all payslips
    loadAllPayslips();
    
    // Handle admin payslip upload
    const adminUploadForm = document.getElementById('adminUploadForm');
    adminUploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('userId', document.getElementById('userSelect').value);
        formData.append('monthYear', document.getElementById('adminMonthYear').value);
        formData.append('payslipFile', document.getElementById('adminPayslipFile').files[0]);
        
        fetch('api/admin_upload_payslip.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': `Bearer ${user.token || ''}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payslip uploaded successfully!');
                loadAllPayslips();
                adminUploadForm.reset();
            } else {
                alert(data.message || 'Failed to upload payslip');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while uploading payslip');
        });
    });
    
    // Apply filter
    document.getElementById('applyFilter').addEventListener('click', function() {
        loadAllPayslips();
    });
    
    function loadUsers() {
        fetch('api/get_users.php', {
            headers: {
                'Authorization': `Bearer ${user.token || ''}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.users.length > 0) {
                const userSelect = document.getElementById('userSelect');
                const filterUser = document.getElementById('filterUser');
                
                // Clear existing options
                userSelect.innerHTML = '';
                filterUser.innerHTML = '<option value="all">All Users</option>';
                
                data.users.forEach(user => {
                    if (user.role === 'user') { // Only show regular users for payslip assignment
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.username;
                        userSelect.appendChild(option);
                    }
                    
                    // Add all users to filter
                    const filterOption = document.createElement('option');
                    filterOption.value = user.id;
                    filterOption.textContent = user.username;
                    filterUser.appendChild(filterOption);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    function loadAllPayslips() {
        const userId = document.getElementById('filterUser').value;
        const month = document.getElementById('filterMonth').value;
        
        let url = 'api/get_all_payslips.php';
        const params = [];
        
        if (userId !== 'all') params.push(`userId=${userId}`);
        if (month) params.push(`month=${month}`);
        
        if (params.length > 0) {
            url += `?${params.join('&')}`;
        }
        
        fetch(url, {
            headers: {
                'Authorization': `Bearer ${user.token || ''}`
            }
        })
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#adminPayslipsTable tbody');
            tableBody.innerHTML = '';
            
            if (data.success && data.payslips.length > 0) {
                data.payslips.forEach(payslip => {
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>${payslip.username}</td>
                        <td>${payslip.month_year}</td>
                        <td>${payslip.file_name}</td>
                        <td>${new Date(payslip.upload_date).toLocaleDateString()}</td>
                        <td>
                            <button class="action-btn" onclick="downloadPayslip(${payslip.id})">Download</button>
                            <button class="action-btn delete" onclick="adminDeletePayslip(${payslip.id})">Delete</button>
                        </td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5">No payslips found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Make functions available globally
    window.downloadPayslip = function(payslipId) {
        fetch(`api/download_payslip.php?id=${payslipId}`, {
            headers: {
                'Authorization': `Bearer ${user.token || ''}`
            }
        })
        .then(response => {
            if (response.ok) return response.blob();
            throw new Error('Failed to download');
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `payslip_${payslipId}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to download payslip');
        });
    };
    
    window.adminDeletePayslip = function(payslipId) {
        if (!confirm('Are you sure you want to delete this payslip?')) return;
        
        fetch(`api/delete_payslip.php?id=${payslipId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${user.token || ''}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payslip deleted successfully!');
                loadAllPayslips();
            } else {
                alert(data.message || 'Failed to delete payslip');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting payslip');
        });
    };
});