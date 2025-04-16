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
            SESSION: "Your session has expired. Please log in again."
        },
        SUCCESS: {
            LOGIN: "Login successful! Redirecting...",
            REGISTER: "Registration successful! Redirecting...",
            UPDATE: "Update successful!"
        }
    };

    function clearErrors() {
        document.querySelectorAll(".error-message").forEach((element) => {
            element.textContent = "";
            element.style.display = "none";
        });
        document.querySelectorAll("input, select").forEach((element) => {
            element.classList.remove("error");
        });
    }

    function showError(fieldname, message) {
        const errorElement = document.getElementById(`${fieldname.toLowerCase()}-error`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = "block";
            
            const inputField = document.querySelector(`[name="${fieldname.toLowerCase()}"]`);
            if (inputField) {
                inputField.classList.add("error");
                inputField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                if (fieldname.toLowerCase() === 'confirm_password' && message.includes("match")) {
                    const passwordField = document.querySelector('[name="password"]');
                    if (passwordField) {
                        passwordField.classList.add("error");
                    }
                }
            }
        } else {
            Swal.fire({
                title: "Error!",
                text: message,
                icon: "error",
                confirmButtonText: "Try Again"
            });
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
            const startTime = Date.now();

            try {
                const data = await CSRFManager.fetchWithToken("../Backend/Core/MAIN.php", {
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

                    await Swal.fire({
                        title: "Success!",
                        text: data.message || "Operation completed successfully!",
                        icon: "success",
                        confirmButtonText: "Continue"
                    });

                    const action = formData.get('action');
                    if (action === 'register') {
                        window.location.href = 'login.html';
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    if (data.errors && Array.isArray(data.errors)) {
                        data.errors.forEach(error => {
                            showError(error.field, error.message);
                        });
                    } else {
                        await Swal.fire({
                            title: "Error!",
                            text: data.message || MESSAGES.ERROR.VALIDATION,
                            icon: "error",
                            confirmButtonText: "Try Again"
                        });
                    }
                }
            } catch (error) {
                await Swal.fire({
                    title: "Error!",
                    text: error.message || MESSAGES.ERROR.NETWORK,
                    icon: "error",
                    confirmButtonText: "Try Again"
                });
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