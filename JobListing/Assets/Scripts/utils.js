// Common utility functions for the application

// Standardized error handler
const ErrorHandler = {
    async handleError(error, title = 'Error!', defaultMessage = 'An error occurred') {
        console.error('Error:', error);
        await Swal.fire({
            title: title,
            text: error.message || defaultMessage,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    },

    async handleSuccess(message = 'Operation completed successfully!') {
        await Swal.fire({
            title: 'Success!',
            text: message,
            icon: 'success',
            confirmButtonColor: '#28a745'
        });
    }
};

// Form utilities
const FormUtils = {
    clearErrors() {
        document.querySelectorAll('.error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        document.querySelectorAll('input, select').forEach(el => {
            el.classList.remove('error');
        });
    },

    showFieldError(fieldname, message) {
        const errorElement = document.getElementById(`${fieldname.toLowerCase()}-error`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            
            const inputField = document.querySelector(`[name="${fieldname.toLowerCase()}"]`);
            if (inputField) {
                inputField.classList.add('error');
                inputField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    },

    setLoading(button, isLoading) {
        if (!button) return;
        
        button.disabled = isLoading;
        const btnText = button.querySelector('.btn-text');
        const btnLoader = button.querySelector('.btn-loader');
        
        if (btnText) btnText.style.display = isLoading ? 'none' : 'inline-block';
        if (btnLoader) btnLoader.style.display = isLoading ? 'inline-block' : 'none';
    }
};

// API utilities
const ApiUtils = {
    /**
     * Makes an API call with automatic CSRF token handling
     * @param {string} url - The URL to make the request to
     * @param {object} options - Fetch options
     * @returns {Promise<any>} - The parsed response data
     */
    async makeApiCall(url, options = {}) {
        try {
            const token = await CSRFManager.ensureValidToken();
            
            // Set up headers with CSRF token
            options.headers = {
                'X-Csrf-Token': token,
                'Accept': 'application/json',
                ...options.headers
            };
            options.credentials = 'same-origin';

            // Handle form data
            if (options.body instanceof FormData) {
                options.body.append('csrf_token', token);
            } else if (typeof options.body === 'object' && !(options.body instanceof FormData)) {
                const formData = new FormData();
                for (const [key, value] of Object.entries(options.body)) {
                    formData.append(key, value);
                }
                formData.append('csrf_token', token);
                options.body = formData;
            }

            const response = await fetch(url, options);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Server response:', errorText);
                let errorMessage;
                try {
                    const errorData = JSON.parse(errorText);
                    errorMessage = errorData.message || `Server error (${response.status})`;
                } catch (e) {
                    errorMessage = `Server error (${response.status}): ${errorText}`;
                }
                throw new Error(errorMessage);
            }
            
            const data = await response.json();

            // Update CSRF token if provided in response
            if (data.csrf_token) {
                CSRFManager.setToken(data.csrf_token);
            }

            if (!data.success) {
                throw new Error(data.message || 'API call failed');
            }
            
            return data;
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
    },

    /**
     * Submits a form through an API endpoint
     * @param {HTMLFormElement} form - The form to submit
     * @param {string} action - The action to append to the form data
     * @param {string} successMessage - Message to show on success
     */
    async submitForm(form, action, successMessage = 'Operation completed successfully') {
        if (!form.checkValidity()) {
            form.reportValidity();
            return false;
        }

        try {
            const formData = new FormData(form);
            formData.append('action', action);

            const data = await this.makeApiCall('../Backend/Core/MAIN.php', {
                method: 'POST',
                body: formData
            });

            await Utils.Error.handleSuccess(successMessage);
            return true;
        } catch (error) {
            await Utils.Error.handleError(error);
            return false;
        }
    }
};

// Validation utilities
const ValidationUtils = {
    validateLogo(file) {
        const maxSize = 2 * 1024 * 1024; // 2MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!allowedTypes.includes(file.type)) {
            throw new Error('Logo must be a JPG, PNG or GIF file');
        }
        
        if (file.size > maxSize) {
            throw new Error('Logo file size must be less than 2MB');
        }
        return true;
    },

    validateRequiredFields(formData, requiredFields) {
        for (const field of requiredFields) {
            const value = formData.get(field);
            if (!value || value.trim() === '') {
                throw new Error(`${field.replace('_', ' ')} is required`);
            }
        }
    }
};

// DataTable utilities
const TableUtils = {
    initDataTable(elementId, customOptions = {}) {
        const element = document.getElementById(elementId);
        if (!element) return null;

        // Destroy existing instance if it exists
        if ($.fn.DataTable.isDataTable('#' + elementId)) {
            $('#' + elementId).DataTable().destroy();
        }

        const defaultOptions = {
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            }
        };

        return new DataTable('#' + elementId, { ...defaultOptions, ...customOptions });
    }
};

// Export utilities
window.Utils = {
    Error: ErrorHandler,
    Form: FormUtils,
    Api: ApiUtils,
    Validation: ValidationUtils,
    Table: TableUtils
};
