const CSRFManager = {
    token: null,
    lastRefresh: null,
    refreshInterval: 1800000, // 30 minutes in milliseconds,

    async init() {
        try {
            console.log('Initializing CSRF token...');
            const response = await fetch('../Backend/Core/MAIN.php?action=getCSRFToken', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            console.log('CSRF init response:', data);
            if (data.success && data.token) {
                this.token = data.token;
                this.lastRefresh = Date.now();
                this.updateAllForms();
                this.startRefreshTimer();
                console.log('CSRF token initialized:', this.token);
                return true;
            }
            console.error('Failed to get CSRF token:', data);
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
            console.log('Refreshing CSRF token...');
            const response = await fetch('../Backend/Core/MAIN.php?action=getCSRFToken', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (data.success && data.token) {
                this.token = data.token;
                this.lastRefresh = Date.now();
                this.updateAllForms();
                console.log('CSRF token refreshed:', this.token);
            } else {
                console.error('Failed to refresh token:', data);
                throw new Error('Failed to refresh CSRF token');
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
        }
    },

    async ensureValidToken() {
        try {
            if (!this.token || Date.now() - this.lastRefresh >= this.refreshInterval) {
                console.log('Token expired or missing, refreshing...');
                await this.refreshToken();
            }
            if (!this.token) {
                console.error('No valid token available');
                throw new Error('No valid CSRF token available');
            }
            return this.token;
        } catch (error) {
            console.error('Error ensuring valid token:', error);
            throw error;
        }
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
                options.credentials = 'same-origin';
                options.headers = {
                    'X-Csrf-Token': token,
                    'Accept': 'application/json',
                    ...options.headers
                };
                
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