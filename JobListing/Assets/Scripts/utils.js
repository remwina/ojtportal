// Common utility functions for the application

// Standardized error handler
const ErrorHandler = {
    async handleError(error, title = 'Error!', defaultMessage = 'An error occurred') {
        console.error('Error:', error);
        
        // If error is an object with special properties (like deactivation error)
        if (typeof error === 'object' && !(error instanceof Error)) {
            await Swal.fire({
                title: error.title || title,
                [/<[a-z][\s\S]*>/i.test(error.message) ? 'html' : 'text']: error.message,
                icon: error.icon || 'error',
                confirmButtonColor: error.confirmButtonColor || '#dc3545',
                confirmButtonText: error.confirmButtonText || 'OK',
                width: error.modalWidth,
                allowOutsideClick: false
            });
            return;
        }

        // Handle regular Error objects
        const message = error.message || defaultMessage;
        const containsHtml = /<[a-z][\s\S]*>/i.test(message);
        
        await Swal.fire({
            title: title,
            [containsHtml ? 'html' : 'text']: message,
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'OK',
            allowOutsideClick: false
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
            errorElement.classList.add('show');
            
            const inputField = document.querySelector(`[name="${fieldname.toLowerCase()}"]`);
            if (inputField) {
                inputField.classList.add('error');
                // Auto-hide error when user starts typing
                inputField.addEventListener('input', () => {
                    inputField.classList.remove('error');
                    errorElement.classList.remove('show');
                    setTimeout(() => {
                        errorElement.style.display = 'none';
                        errorElement.textContent = '';
                    }, 300);
                }, { once: true });
            }
        }
    },

    setLoading(button, isLoading) {
        if (!button) return;
        
        button.disabled = isLoading;
        const btnText = button.querySelector('.btn-text');
        const btnLoader = button.querySelector('.btn-loader');
        
        if (btnText) {
            if (isLoading) {
                btnText.style.opacity = '0';
                setTimeout(() => {
                    btnText.style.display = 'none';
                }, 300);
            } else {
                btnText.style.opacity = '0';
                btnText.style.display = 'inline-block';
                requestAnimationFrame(() => {
                    btnText.style.opacity = '1';
                    btnText.style.transition = 'opacity 0.3s ease';
                });
            }
        }
        
        if (btnLoader) {
            if (isLoading) {
                btnLoader.style.display = 'inline-block';
                requestAnimationFrame(() => {
                    btnLoader.style.opacity = '1';
                });
            } else {
                btnLoader.style.opacity = '0';
                setTimeout(() => {
                    btnLoader.style.display = 'none';
                }, 300);
            }
        }
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
            // Ensure we have a valid CSRF token
            const token = await CSRFManager.ensureValidToken();
            if (!token) {
                throw new Error('Security token is missing. Please refresh the page.');
            }
            
            // Add CSRF token to headers
            options.headers = {
                ...options.headers,
                'X-Csrf-Token': token,
                'Accept': 'application/json'
            };

            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`Network error: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Handle error responses
            if (!data.success) {
                // Handle validation errors
                if (data.errors && Array.isArray(data.errors)) {
                    throw {
                        isValidationError: true,
                        errors: data.errors,
                        message: data.message || 'Validation failed'
                    };
                }
                
                // Handle account deactivation
                if (data.isDeactivated) {
                    return {
                        success: false,
                        isDeactivated: true,
                        message: data.message,
                        icon: data.icon || 'warning',
                        title: data.title || 'Account Deactivated',
                        confirmButtonText: data.confirmButtonText || 'I Understand',
                        confirmButtonColor: data.confirmButtonColor || '#6c757d',
                        modalWidth: data.modalWidth || '500px'
                    };
                }
                
                // Handle general errors
                throw new Error(data.message || 'Request failed');
            }

            // Update CSRF token if provided
            if (data.csrf_token) {
                CSRFManager.setToken(data.csrf_token);
            }

            return data;
        } catch (error) {
            console.error('API call failed:', error);
            
            // Pass through structured error objects
            if (typeof error === 'object' && error !== null && !Array.isArray(error) &&
                (error.isValidationError || error.isDeactivated || error.icon)) {
                throw error;
            }
            
            // Handle standard Error objects
            if (error instanceof Error) {
                throw error;
            }
            
            // Handle any other errors
            throw new Error(error.message || 'An unexpected error occurred');
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
