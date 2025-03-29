document.addEventListener('DOMContentLoaded', function() {
    const userData = sessionStorage.getItem('user');
    if (!userData) {
        window.location.href = 'index.html';
        return;
    }
    
    const user = JSON.parse(userData);
    document.getElementById('usernameDisplay').textContent = user.username;
    
    // Logout functionality
    document.getElementById('logoutBtn').addEventListener('click', function() {
        sessionStorage.removeItem('user');
        window.location.href = 'index.html';
    });
    
    // Load user's payslips
    loadPayslips();
    
    // Filter functionality
    document.getElementById('applyFilter').addEventListener('click', function() {
        loadPayslips();
    });
    
    function loadPayslips() {
        const monthFilter = document.getElementById('filterMonth').value;
        let url = 'api/get_payslips.php';
        
        if (monthFilter) {
            url += `?month=${monthFilter}`;
        }
        
        fetch(url, {
            headers: {
                'Authorization': `Bearer ${user.token || ''}`
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            const tableBody = document.querySelector('#payslipsTable tbody');
            tableBody.innerHTML = '';
            
            if (data.success && data.payslips.length > 0) {
                data.payslips.forEach(payslip => {
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>${payslip.month_year}</td>
                        <td>${payslip.file_name.replace('.pdf', '')}</td>
                        <td>${new Date(payslip.upload_date).toLocaleDateString()}</td>
                        <td>
                            <button class="action-btn download-btn" data-id="${payslip.id}">Download</button>
                            <button class="action-btn view-btn" data-id="${payslip.id}">View</button>
                        </td>
                    `;
                    
                    tableBody.appendChild(row);
                });

                // Add event listeners
                document.querySelectorAll('.download-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        downloadPayslip(this.dataset.id);
                    });
                });

                document.querySelectorAll('.view-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        viewPayslip(this.dataset.id);
                    });
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="4">No payslips available</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load payslips. Please try again.');
        });
    }
    
    function downloadPayslip(payslipId) {
        fetch(`api/download_payslip.php?id=${payslipId}`, {
            headers: {
                'Authorization': `Bearer ${user.token || ''}`
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Download failed');
            return response.blob();
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
            alert('Failed to download payslip. Please try again.');
        });
    }
    
    function viewPayslip(payslipId) {
        fetch(`api/download_payslip.php?id=${payslipId}&view=1`, {
            headers: {
                'Authorization': `Bearer ${user.token || ''}`
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('View failed');
            return response.blob();
        })
        .then(blob => {
            const url = URL.createObjectURL(blob);
            const pdfWindow = window.open('', '_blank');
            pdfWindow.document.write(`
                <html>
                <head>
                    <title>Payslip Viewer</title>
                    <style>
                        body, html { margin: 0; padding: 0; height: 100%; }
                        embed { width: 100%; height: 100%; }
                    </style>
                </head>
                <body>
                    <embed src="${url}" type="application/pdf">
                </body>
                </html>
            `);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to view payslip. Please try again.');
        });
    }
});