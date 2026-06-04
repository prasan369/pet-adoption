// User Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterPets);
    }
    
    if (filterType) {
        filterType.addEventListener('change', filterPets);
    }
});

function filterPets() {
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const petCards = document.querySelectorAll('.pet-card');
    
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const selectedType = filterType ? filterType.value : '';
    
    petCards.forEach(card => {
        const petName = card.querySelector('.pet-details h3').textContent.toLowerCase();
        const petType = card.querySelector('.pet-details p:nth-child(2)').textContent;
        
        const matchesSearch = petName.includes(searchTerm);
        const matchesType = !selectedType || petType.includes(selectedType);
        
        card.style.display = (matchesSearch && matchesType) ? 'block' : 'none';
    });
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    });
});
