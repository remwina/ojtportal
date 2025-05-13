        document.addEventListener('DOMContentLoaded', async function() {
            await CSRFManager.init();
            CSRFManager.updateFormToken(document.getElementById('registerForm'));

            // Check if user is logged in as super admin
            const response = await fetch('../Backend/Core/MAIN.php?action=checkAdmin');
            const data = await response.json();
            const usertypeSelect = document.getElementById('usertype');
            
            // Only show admin option if user is a super admin
            if (!data.isSuperAdmin) {
                const adminOption = usertypeSelect.querySelector('option[value="admin"]');
                if (adminOption) {
                    adminOption.remove();
                }
            }

            const departmentSelect = document.getElementById('department');
            const courseSelect = document.getElementById('course');

            // Fetch departments
            try {
                const response = await fetch('../Backend/Core/MAIN.php?action=getDepartments');
                const data = await response.json();
                if (data.success && data.departments) {
                    departmentSelect.innerHTML = '<option value="">Select Department</option>';
                    data.departments.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        departmentSelect.appendChild(option);
                    });
                } else {
                    throw new Error(data.message || 'Failed to load departments');
                }
            } catch (error) {
                console.error('Error loading departments:', error);
                departmentSelect.innerHTML = '<option value="">Failed to load departments</option>';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load departments. Please refresh the page.',
                });
            }

            // Handle department change
            departmentSelect.addEventListener('change', async function() {
                courseSelect.disabled = true;
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                
                if (this.value) {
                    try {
                        const response = await fetch(`../Backend/Core/MAIN.php?action=getCoursesByDepartment&department_id=${this.value}`);
                        const data = await response.json();
                        if (data.success && data.courses) {
                            data.courses.forEach(course => {
                                const option = document.createElement('option');
                                option.value = course.id;
                                option.textContent = course.name;
                                courseSelect.appendChild(option);
                            });
                            courseSelect.disabled = false;
                        } else {
                            throw new Error(data.message || 'Failed to load courses');
                        }
                    } catch (error) {
                        console.error('Error loading courses:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load courses. Please try again.',
                        });
                    }
                }
            });

            // Form submission
            document.getElementById('registerForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Clear previous errors
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

                const departmentSelect = document.getElementById('department');
                const courseSelect = document.getElementById('course');

                // Basic frontend validation
                if (!departmentSelect.value) {
                    document.getElementById('department-error').textContent = 'Please select a department';
                    departmentSelect.classList.add('error');
                    return;
                }

                if (!courseSelect.value) {
                    document.getElementById('course-error').textContent = 'Please select a course';
                    courseSelect.classList.add('error');
                    return;
                }

                const submitButton = this.querySelector('button[type="submit"]');
                const btnText = submitButton.querySelector('.btn-text');
                const btnLoader = submitButton.querySelector('.btn-loader');
                
                try {
                    // Show loading state
                    submitButton.disabled = true;
                    btnText.style.display = 'none';
                    btnLoader.style.display = 'inline-block';

                    const formData = new FormData(this);
                    formData.append('action', 'register');

                    // Get fresh CSRF token before request
                    const token = await CSRFManager.ensureValidToken();
                    formData.append('csrf_token', token);

                    const response = await fetch('../Backend/Core/MAIN.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Csrf-Token': token,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    
                    if (!data || !data.success) {
                        // Handle validation errors first
                        if (data && data.errors) {
                            data.errors.forEach(error => {
                                if (error.field === 'general') {
                                    throw new Error(error.message || 'Registration failed');
                                } else {
                                    const errorElement = document.getElementById(error.field + '-error');
                                    if (errorElement) {
                                        errorElement.textContent = error.message;
                                        const input = document.getElementById(error.field);
                                        if (input) input.classList.add('error');
                                    }
                                }
                            });
                            return; // Don't redirect if we have field errors
                        }
                        throw new Error(data?.message || 'Registration failed. Please try again.');
                    }
                    
                    // Update CSRF token if provided
                    if (data.csrf_token) {
                        CSRFManager.setToken(data.csrf_token);
                    }
                    
                    // Show success message
                    await Swal.fire({
                        icon: 'success',
                        title: 'Registration Successful!',
                        text: data.message || 'Your account has been created successfully.',
                        showConfirmButton: true,
                        confirmButtonText: 'Proceed to Login',
                        allowOutsideClick: false
                    });
                    
                    // Clear form and redirect
                    this.reset();
                    window.location.href = 'login.html';
                    
                } catch (error) {
                    console.error('Registration error:', error);
                    await Swal.fire({
                        icon: 'error',
                        title: 'Registration Failed',
                        text: error.message || 'An error occurred during registration. Please try again.',
                        confirmButtonText: 'Try Again'
                    });
                } finally {
                    // Reset button state if we haven't redirected
                    if (document.body) {
                        submitButton.disabled = false;
                        btnText.style.display = 'inline-block';
                        btnLoader.style.display = 'none';
                    }
                }
            });
        });