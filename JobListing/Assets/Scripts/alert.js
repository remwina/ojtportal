document.addEventListener("DOMContentLoaded", async function () {
    const form = document.querySelector("form");
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

    window.MESSAGES = MESSAGES;

    if (form && !form.id.includes('login')) {  // Skip login form
        form.addEventListener("submit", async function (e) {
            e.preventDefault();
            Utils.Form.clearErrors();

            const formData = new FormData(form);
            const startTime = Date.now();

            try {
                const data = await Utils.Api.makeApiCall("../Backend/Core/MAIN.php", {
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

                    await Utils.Error.handleSuccess(data.message || MESSAGES.SUCCESS.UPDATE);

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
                    }
                } else {
                    if (data.errors && Array.isArray(data.errors)) {
                        data.errors.forEach(error => {
                            Utils.Form.showFieldError(error.field, error.message);
                        });
                    } else {
                        throw new Error(data.message || MESSAGES.ERROR.VALIDATION);
                    }
                }
            } catch (error) {
                await Utils.Error.handleError(error, "Error!", error.message || MESSAGES.ERROR.NETWORK);
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
