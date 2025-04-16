// CSRF Token Management
const CSRFManager = {
    async init() {
        try {
            const response = await fetch("../../JobListing/Backend/Core/MAIN.php", {
                method: 'POST',
                body: new URLSearchParams({
                    'action': 'csrf_init'
                })
            });
            const data = await response.json();
            if (data.csrf_token) {
                this.setToken(data.csrf_token);
            }
        } catch (error) {
            console.error('Failed to initialize CSRF token:', error);
        }
    },

    getToken() {
        return localStorage.getItem('csrf_token');
    },

    setToken(token) {
        if (token) {
            localStorage.setItem('csrf_token', token);
        }
    },

    removeToken() {
        localStorage.removeItem('csrf_token');
    },

    updateFormToken(form) {
        const token = this.getToken();
        if (token && form) {
            const tokenInput = form.querySelector('[name="csrf_token"]');
            if (tokenInput) {
                tokenInput.value = token;
            }
        }
    },

    // Add CSRF token to fetch requests
    async fetchWithToken(url, options = {}) {
        await this.init(); // Ensure we have a token
        const token = this.getToken();
        
        if (token) {
            if (options.body instanceof FormData) {
                options.body.set('csrf_token', token);
            } else {
                const formData = new FormData();
                formData.append('csrf_token', token);
                if (options.body) {
                    for (const [key, value] of Object.entries(options.body)) {
                        formData.append(key, value);
                    }
                }
                options.body = formData;
            }
        }
        
        const response = await fetch(url, options);
        const data = await response.json();
        
        // Update token if provided in response
        if (data.csrf_token) {
            this.setToken(data.csrf_token);
        }
        
        return data;
    }
};