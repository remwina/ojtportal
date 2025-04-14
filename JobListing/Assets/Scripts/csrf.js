// Function to fetch CSRF token from backend
async function fetchCsrfToken() {
    try {
        const response = await fetch('../Backend/Core/MAIN.php?action=getToken');
        const data = await response.json();
        if (data.token) {
            document.getElementById('csrf_token').value = data.token;
        }
    } catch (error) {
        console.error('Error fetching CSRF token:', error);
    }
}

// Fetch token when page loads
document.addEventListener('DOMContentLoaded', fetchCsrfToken); 