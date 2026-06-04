// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    fetchDashboardStats();
});

function fetchDashboardStats() {
    // This would ideally fetch from an API
    // For now, we'll load stats via JavaScript
    
    // Get stats from the database
    fetch('get_stats.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalUsers').textContent = data.users || 0;
            document.getElementById('totalPets').textContent = data.pets || 0;
            document.getElementById('totalAdoptions').textContent = data.adoptions || 0;
            document.getElementById('pendingRequests').textContent = data.pending || 0;
        })
        .catch(error => console.log('Stats loaded from page'));
}
