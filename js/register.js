// Register Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const errorMessage = document.getElementById('errorMessage');
    
    // Check for error messages from session
    if (errorMessage) {
        const error = errorMessage.getAttribute('data-error');
        if (error) {
            showError(error);
        }
    }
    
    // Check url search parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        showError(urlParams.get('error'));
    }
    
    // Client-side form validation before submission
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            
            if (password.length < 6) {
                e.preventDefault();
                showError('Password must be at least 6 characters long');
                return;
            }
            
            if (!validateEmail(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return;
            }
        });
    }
    
    function showError(message) {
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
});
