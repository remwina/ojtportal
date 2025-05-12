const CSRFManager = {
    token: null,
    lastRefresh: null,
    refreshInterval: 1800000, // 30 minutes in milliseconds

    /**
     * Fetch a new CSRF token from the server
     * @returns {Promise<{success: boolean, token?: string}>}
     */
    async fetchNewToken() {
        try {
            const response = await fetch('../Backend/Core/MAIN.php?action=getCSRFToken', {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            return await response.json();
        } catch (error) {
            console.error('Failed to fetch CSRF token:', error);
            return { success: false };
        }
    },

    /**
     * Update token state and forms
     * @param {string} token - New token value
     */
    updateTokenState(token) {
        this.token = token;
        this.lastRefresh = Date.now();
        this.updateAllForms();
    },

    /**
     * Initialize CSRF protection
     * @returns {Promise<boolean>} Success status
     */
    async init() {
        console.log('Initializing CSRF token...');
        const data = await this.fetchNewToken();
            
        if (data.success && data.token) {
            this.updateTokenState(data.token);
            this.startRefreshTimer();
            console.log('CSRF token initialized:', this.token);
            return true;
        }
        
        console.error('Failed to get CSRF token:', data);
        return false;
    },

    /**
     * Start timer to refresh token periodically
     */
    startRefreshTimer() {
        setInterval(async () => {
            if (Date.now() - this.lastRefresh >= this.refreshInterval) {
                await this.refreshToken();
            }
        }, 60000); // Check every minute
    },

    /**
     * Refresh the CSRF token
     */
    async refreshToken() {
        console.log('Refreshing CSRF token...');
        const data = await this.fetchNewToken();
            
        if (data.success && data.token) {
            this.updateTokenState(data.token);
            console.log('CSRF token refreshed successfully');
        } else {
            throw new Error('Failed to refresh CSRF token');
        }
    },

    /**
     * Ensure a valid token is available
     * @returns {Promise<string>} Valid CSRF token
     */
    async ensureValidToken() {
        if (!this.token || Date.now() - this.lastRefresh >= this.refreshInterval) {
            await this.refreshToken();
        }
        if (!this.token) {
            throw new Error('No valid CSRF token available');
        }
        return this.token;
    },

    /**
     * Get current token
     * @returns {string|null} Current token
     */
    getToken() {
        return this.token;
    },

    /**
     * Set new token and update forms
     * @param {string} token - New token
     */
    setToken(token) {
        this.token = token;
        this.lastRefresh = Date.now();
        this.updateAllForms();
    },

    /**
     * Update CSRF token in a form
     * @param {HTMLFormElement} form - Form to update
     */
    updateFormToken(form) {
        if (!form) return;
        
        let tokenInput = form.querySelector('input[name="csrf_token"]');
        if (!tokenInput) {
            tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'csrf_token';
            form.appendChild(tokenInput);
        }
        tokenInput.value = this.token;
    },

    /**
     * Update CSRF token in all forms
     */
    updateAllForms() {
        document.querySelectorAll('form').forEach(form => this.updateFormToken(form));
    },

    /**
     * Remove token and clear from forms
     */
    removeToken() {
        this.token = null;
        this.lastRefresh = null;
        document.querySelectorAll('input[name="csrf_token"]').forEach(input => input.value = '');
    }
};