const CSRFManager = {
    token: null,
    lastRefresh: null,
    refreshInterval: 1800000, // 30 minutes in milliseconds
    
    async init() {
        try {
            const response = await fetch('../Backend/Core/MAIN.php?action=getCSRFToken');
            const data = await response.json();
            if (data.success && data.token) {
                this.token = data.token;
                this.lastRefresh = Date.now();
                this.updateAllForms();
                this.startRefreshTimer();
                return true;
            }
            return false;
        } catch (error) {
            console.error('Failed to initialize CSRF token:', error);
            return false;
        }
    },

    startRefreshTimer() {
        setInterval(async () => {
            if (Date.now() - this.lastRefresh >= this.refreshInterval) {
                await this.refreshToken();
            }
        }, 60000); // Check every minute
    },

    async refreshToken() {
        try {
            const response = await fetch('../Backend/Core/MAIN.php?action=getCSRFToken');
            const data = await response.json();
            if (data.success && data.token) {
                this.token = data.token;
                this.lastRefresh = Date.now();
                this.updateAllForms();
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
        }
    },

    async ensureValidToken() {
        if (!this.token || Date.now() - this.lastRefresh >= this.refreshInterval) {
            await this.refreshToken();
        }
        return this.token;
    },

    getToken() {
        return this.token;
    },

    setToken(token) {
        this.token = token;
        this.updateAllForms();
    },

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

    updateAllForms() {
        document.querySelectorAll('form').forEach(form => this.updateFormToken(form));
    },

    removeToken() {
        this.token = null;
        document.querySelectorAll('input[name="csrf_token"]').forEach(input => input.value = '');
    },

    async fetchWithToken(url, options = {}) {
        let token = await this.ensureValidToken();
        let retries = 2;
        
        while (retries > 0) {
            try {
                if (!options.headers) {
                    options.headers = {};
                }
                options.headers['X-CSRF-Token'] = token;
                
                // Handle different body types
                if (options.body instanceof FormData) {
                    options.body.append('csrf_token', token);
                } else if (typeof options.body === 'object') {
                    if (!(options.body instanceof FormData)) {
                        const formData = new FormData();
                        for (const [key, value] of Object.entries(options.body)) {
                            formData.append(key, value);
                        }
                        formData.append('csrf_token', token);
                        options.body = formData;
                    }
                }

                const response = await fetch(url, options);
                const data = await response.json();
                
                if (data.csrf_token) {
                    this.setToken(data.csrf_token);
                    this.lastRefresh = Date.now();
                }
                
                return data;
            } catch (error) {
                if (error.message?.includes('Invalid security token') && retries > 0) {
                    // If token is invalid, try to refresh it and retry the request
                    await this.refreshToken();
                    token = this.token;
                    retries--;
                } else {
                    throw error;
                }
            }
        }
    }
};