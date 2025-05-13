document.addEventListener("DOMContentLoaded", async function () {
    const form = document.querySelector("form");
    const submitButton = form?.querySelector('button[type="submit"]');
    const btnText = submitButton?.querySelector('.btn-text');
    const btnLoader = submitButton?.querySelector('.btn-loader');
    const MIN_PROCESSING_TIME = 800;

    const MESSAGES = {
        ERROR: {
            VALIDATION: "Please check your input and try again.",
            NETWORK: "Network error. Please check your connection.",
            SERVER: "Server error. Please try again later.",
            TOKEN: "Security token missing. Please refresh the page.",
            SESSION: "Your session has expired. Please log in again.",
            DEACTIVATED: "Your account has been deactivated. Please contact the administrator for assistance."
        },
        SUCCESS: {
            LOGIN: "Login successful! Redirecting...",
            REGISTER: "Registration successful! Redirecting...",
            UPDATE: "Update successful!"
        }
    };

    function clearErrors() {
        document.querySelectorAll('.error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        document.querySelectorAll('input, select').forEach(el => {
            el.classList.remove('error');
        });
    }

    function showFieldError(fieldname, message) {
        const input = document.getElementById(fieldname);
        const errorElement = document.getElementById(`${fieldname}-error`);
        
        if (input) {
            input.classList.add('error');
            // Remove error class when user starts typing
            input.addEventListener('input', function() {
                if (errorElement) {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                }
                this.classList.remove('error');
            }, { once: true });
        }
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    async function handleError(error, title = 'Error!', defaultMessage = 'An error occurred') {
        console.error('Error:', error);
        
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
    }

    async function handleSuccess(message = 'Operation completed successfully!') {
        await Swal.fire({
            title: 'Success!',
            text: message,
            icon: 'success',
            confirmButtonColor: '#28a745'
        });
    }

    async function makeApiCall(url, options = {}) {
        try {
            const token = await CSRFManager.ensureValidToken();
            if (!token) {
                throw new Error('Security token is missing. Please refresh the page.');
            }

            options.headers = {
                ...options.headers,
                'X-Csrf-Token': token,
                'Accept': 'application/json'
            };

            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`Network error: ${response.status}`);
            }            const data = await response.json();
            console.log('API Response:', data); // Debug log
            
            if (!data.success) {
                if (data.errors && Array.isArray(data.errors)) {
                    // Clear any existing errors first
                    clearErrors();
                    
                    // Display validation errors under respective fields
                    data.errors.forEach(error => {
                        console.log('Processing error:', error); // Debug log
                        showFieldError(error.field, error.message);
                    });
                    
                    // Show general error message for validation failures
                    throw {
                        isValidationError: true,
                        message: MESSAGES.ERROR.VALIDATION,
                        errors: data.errors
                    };
                }

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

                throw new Error(data.message || 'Request failed');
            }

            if (data.csrf_token) {
                CSRFManager.setToken(data.csrf_token);
            }

            return data;
        } catch (error) {
            console.error('API call failed:', error);
            if (typeof error === 'object' && error !== null && !Array.isArray(error) &&
                (error.isValidationError || error.isDeactivated || error.icon)) {
                throw error;
            }
            if (error instanceof Error) {
                throw error;
            }
            throw new Error(error.message || 'An unexpected error occurred');
        }
    }

    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();
            clearErrors();

            if (submitButton) {
                submitButton.disabled = true;
                if (btnText) btnText.style.display = 'none';
                if (btnLoader) btnLoader.style.display = 'inline-block';
            }

            const formData = new FormData(form);
            const startTime = Date.now();            try {
                const data = await makeApiCall("../Backend/Core/MAIN.php", {
                    method: "POST",
                    body: formData
                });

                const elapsedTime = Date.now() - startTime;
                if (elapsedTime < MIN_PROCESSING_TIME) {
                    await new Promise(resolve => setTimeout(resolve, MIN_PROCESSING_TIME - elapsedTime));
                }

                if (data.success) {
                    if (data.userData) {
                        Object.entries(data.userData).forEach(([key, value]) => {
                            sessionStorage.setItem(key, value);
                        });
                    }

                    await handleSuccess(data.message || MESSAGES.SUCCESS.UPDATE);

                    const action = formData.get('action');
                    const usertype = formData.get('usertype');
                    if (action === 'register') {
                        if (usertype === 'admin') {
                            window.location.href = 'Admin/Login.php';
                        } else {
                            window.location.href = 'login.html';
                        }
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    }                } else if (!data.success) {
                    // Handle validation errors from backend
                    if (data.errors && Array.isArray(data.errors)) {
                        clearErrors(); // Clear any existing errors
                        data.errors.forEach(error => {
                            showFieldError(error.field, error.message);
                        });
                        return; // Stop here, don't show additional error messages
                    } else {
                        throw new Error(data.message || MESSAGES.ERROR.VALIDATION);
                    }
                }
            } catch (error) {
                console.error('Form submission error:', error);
                if (error.isValidationError && error.errors) {
                    // Handle validation errors
                    error.errors.forEach(err => {
                        showFieldError(err.field, err.message);
                    });
                    return;
                }
                await handleError(error, "Error!", error.message || MESSAGES.ERROR.NETWORK);
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    if (btnText) btnText.style.display = 'inline-block';
                    if (btnLoader) btnLoader.style.display = 'none';
                }
            }
        });
    }

    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
        });

        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) tooltip.remove();
        });
    });
});
