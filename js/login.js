// Login Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const errorMessage = document.getElementById('errorMessage');
    
    // Check for error messages from session
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        showError(urlParams.get('error'));
    }
    
    // Check if error comes from PHP session
    if (errorMessage) {
        const error = errorMessage.getAttribute('data-error');
        if (error) {
            showError(error);
        }
    }
    
    function showError(message) {
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
        }
    }
});
