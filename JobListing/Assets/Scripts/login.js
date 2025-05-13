        document.addEventListener('DOMContentLoaded', async function() {
            await CSRFManager.init();
            CSRFManager.updateFormToken(document.getElementById('loginForm'));

            document.getElementById('loginForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Clear any previous errors and disable form
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                const submitButton = this.querySelector('button[type="submit"]');
                const btnText = submitButton?.querySelector('.btn-text');
                const btnLoader = submitButton?.querySelector('.btn-loader');

                // Form validation
                const email = this.email.value.trim();
                const password = this.password.value.trim();

                // Validate email format
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (!email) {
                    document.getElementById('email-error').textContent = 'Email is required';
                    return;
                } else if (!emailRegex.test(email)) {
                    document.getElementById('email-error').textContent = 'Please enter a valid email address';
                    return;
                }

                // Validate password
                if (!password) {
                    document.getElementById('password-error').textContent = 'Password is required';
                    return;
                }
                
                // Disable form and show loading state
                submitButton.disabled = true;
                if (btnText) btnText.style.display = 'none';
                if (btnLoader) btnLoader.style.display = 'inline-block';
                
                try {
                    const formData = new FormData(this);
                    
                    // Make API call directly since Utils is not available
                    const response = await fetch('../Backend/Core/MAIN.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Csrf-Token': await CSRFManager.ensureValidToken()
                        }
                    });
                    
                    const data = await response.json();
                    if (!data) {
                        throw new Error('Invalid response from server');
                    }
                    
                    // Check for deactivation before anything else - uses backend message
                    if (data.isDeactivated === true) {
                        // Clear any existing errors
                        document.querySelectorAll('.error-message').forEach(el => {
                            el.textContent = '';
                            el.style.display = 'none';
                        });
                        document.querySelectorAll('input').forEach(input => {
                            input.classList.remove('error');
                        });
                        
                        await Swal.fire({
                            title: data.title,
                            html: data.message,
                            icon: data.icon,
                            confirmButtonText: 'I Understand', 
                            allowOutsideClick: false
                        });
                        return;
                    }
                    
                    if (data.success) {
                        await Swal.fire({
                            icon: data.icon || 'success',
                            title: data.title || 'Login Successful',
                            text: data.message || 'Redirecting...',
                            timer: data.timer || 1500,
                            showConfirmButton: data.showConfirmButton !== undefined ? data.showConfirmButton : false,
                            allowOutsideClick: false
                        });
                        
                        // Redirect based on user type with animation timing
                        setTimeout(() => {
                            window.location.href = data.redirect || 
                                (data.usertype === 'admin' ? '../Admin/Dashboard.php' : '../Dashboard/dashboard.php');
                        }, data.timer || 1500);
                        return;
                    }
                    
                    if (data.errors && Array.isArray(data.errors) && !data.isDeactivated) {
                        data.errors.forEach(error => {
                            const errorElement = document.getElementById(`${error.field}-error`);
                            if (errorElement) {
                                errorElement.textContent = error.message;
                                errorElement.style.display = 'block';
                                
                                const input = document.getElementById(error.field);
                                if (input) {
                                    input.classList.add('error');
                                    input.addEventListener('input', function() {
                                        this.classList.remove('error');
                                        errorElement.textContent = '';
                                        errorElement.style.display = 'none';
                                    }, { once: true });
                                }
                            }
                        });
                    } else if (!data.isDeactivated) {
                        await Swal.fire({
                            icon: data.icon || 'error',
                            title: data.title || 'Login Failed',
                            text: data.message || 'Invalid email or password',
                            confirmButtonText: data.confirmButtonText || 'Try Again',
                            confirmButtonColor: data.confirmButtonColor || '#dc3545',
                            showClass: {
                                popup: 'animate__animated animate__fadeIn'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOut'
                            }
                        });
                    }
                    
                } catch (error) {
                    console.error('Login error:', error);
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred during login. Please try again.',
                        confirmButtonText: 'Try Again'
                    });
                } finally {
                    // Re-enable form and hide loading state with animation
                    setTimeout(() => {
                        submitButton.disabled = false;
                        if (btnText) {
                            btnText.style.opacity = '0';
                            btnText.style.display = 'inline-block';
                            requestAnimationFrame(() => {
                                btnText.style.opacity = '1';
                                btnText.style.transition = 'opacity 0.3s ease';
                            });
                        }
                        if (btnLoader) {
                            btnLoader.style.opacity = '1';
                            btnLoader.style.transition = 'opacity 0.3s ease';
                            setTimeout(() => {
                                btnLoader.style.opacity = '0';
                                setTimeout(() => {
                                    btnLoader.style.display = 'none';
                                }, 300);
                            }, 0);
                        }
                    }, 300); // Delay to ensure smooth animation
                }
            });
        });