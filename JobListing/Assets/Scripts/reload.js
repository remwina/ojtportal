// Simple polling-based auto-reload functionality
let lastCheckTime = Date.now();
let isPolling = true;
const POLL_INTERVAL = 5000; // Check every 5 seconds

async function checkForChanges() {
    if (!isPolling) return;

    try {
        // Get current CSRF token if available
        const token = CSRFManager?.getToken();
        const headers = token ? { 'X-CSRF-Token': token } : {};
        
        const response = await fetch('../Backend/Core/MAIN.php?action=checkLastUpdate', {
            headers: headers
        });
        const data = await response.json();
        
        // Update CSRF token if provided in response
        if (data.csrf_token && CSRFManager) {
            CSRFManager.setToken(data.csrf_token);
        }
        
        if (data.lastUpdate && data.lastUpdate > lastCheckTime) {
            console.log('Changes detected, reloading page...');
            location.reload();
        }
        
        lastCheckTime = Date.now();
    } catch (error) {
        console.log('Auto-reload check failed:', error);
    }

    setTimeout(checkForChanges, POLL_INTERVAL);
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    checkForChanges();
});

// Clean up when page is unloaded
window.addEventListener('unload', function() {
    isPolling = false;
});