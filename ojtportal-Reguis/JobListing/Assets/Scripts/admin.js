document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Initialize CSRF token management with logging
        console.log('Initializing CSRF Manager...');
        const initialized = await CSRFManager.init();
        console.log('CSRF Manager initialized:', initialized);
        console.log('Initial token:', CSRFManager.getToken());
    } catch (error) {
        console.error('Failed to initialize CSRF:', error);
    }
    
    // Initialize DataTables
    const tables = {};
    
    if (document.getElementById('jobListingsTable')) {
        tables.jobListings = new DataTable('#jobListingsTable', {
            responsive: true,
            order: [[5, 'desc']], // Sort by posted date by default
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search job listings..."
            }
        });
    }

    if (document.getElementById('applicationsTable')) {
        tables.applications = new DataTable('#applicationsTable', {
            responsive: true,
            order: [[3, 'desc']], // Sort by application date by default
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search applications..."
            }
        });
    }

    if (document.getElementById('companiesTable')) {
        tables.companies = new DataTable('#companiesTable', {
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search companies..."
            }
        });
    }

    if (document.getElementById('usersTable')) {
        tables.users = new DataTable('#usersTable', {
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search users..."
            }
        });
    }

    // Global search functionality
    document.querySelector('.search-button').addEventListener('click', function() {
        const searchTerm = document.querySelector('.search-bar input').value;
        Object.values(tables).forEach(table => {
            if (table) {
                table.search(searchTerm).draw();
            }
        });
    });

    document.querySelector('.search-bar input').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = this.value;
            Object.values(tables).forEach(table => {
                if (table) {
                    table.search(searchTerm).draw();
                }
            });
        }
    });

    // Job Listings functionality
    const saveJobBtn = document.getElementById('saveJobBtn');
    if (saveJobBtn) {
        saveJobBtn.addEventListener('click', async function() {
            const form = document.getElementById('addJobForm');
            if (form.checkValidity()) {
                try {
                    const formData = new FormData(form);
                    
                    // Add action and CSRF token
                    formData.append('action', 'addJobListing');
                    formData.append('csrf_token', await CSRFManager.ensureValidToken());
                    
                    // Validate required fields
                    const requiredFields = ['title', 'company_id', 'description', 'job_type', 'slots', 'status'];
                    for (const field of requiredFields) {
                        const value = formData.get(field);
                        if (!value || value.trim() === '') {
                            throw new Error(`${field.replace('_', ' ')} is required`);
                        }
                    }

                    // Validate slots is positive
                    const slots = parseInt(formData.get('slots'));
                    if (isNaN(slots) || slots < 1) {
                        throw new Error('Number of slots must be at least 1');
                    }

                    // Validate expires_at is in the future if provided
                    const expiresAt = formData.get('expires_at');
                    if (expiresAt) {
                        const expiryDate = new Date(expiresAt);
                        if (expiryDate < new Date()) {
                            throw new Error('Expiry date must be in the future');
                        }
                    }

                    const response = await fetch('../Backend/Core/MAIN.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        await Swal.fire({
                            title: 'Success!',
                            text: 'Job listing added successfully',
                            icon: 'success'
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to add job listing');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Failed to add job listing',
                        icon: 'error'
                    });
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('addJobModal')).hide();
                form.reset();
            } else {
                form.reportValidity();
            }
        });
    }

    // makeApiCall helper function
    async function makeApiCall(url, options = {}) {
        try {
            // Ensure we have CSRF token
            const token = await CSRFManager.ensureValidToken();
            
            // Add CSRF token to both headers and body
            options.headers = {
                'X-Csrf-Token': token,
                'Accept': 'application/json',
                ...options.headers
            };

            // If we have FormData, append the token to it
            if (options.body instanceof FormData) {
                options.body.append('csrf_token', token);
            }

            // Make the request
            const response = await fetch(url, options);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Server response:', errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'API call failed');
            }
            
            return data;
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
    }

    // Edit button handler 
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', async function() {
            try {
                const id = this.dataset.id;
                if (!id || isNaN(parseInt(id))) {
                    throw new Error('Invalid ID');
                }
                
                // Determine if we're editing a company or job listing based on the page
                const isJobListing = window.location.pathname.includes('JobListings.php');
                const action = isJobListing ? 'getJobListing' : 'getCompany';
                
                const token = await CSRFManager.ensureValidToken();
                const formData = new FormData();
                formData.append('action', action);
                formData.append('id', id);
                formData.append('csrf_token', token);

                const response = await fetch('../Backend/Core/MAIN.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Csrf-Token': token,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load details');
                }

                const item = data.data;
                const modalId = isJobListing ? 'editJobModal' : 'editCompanyModal';
                const formId = isJobListing ? 'editJobForm' : 'editCompanyForm';
                
                const form = document.getElementById(formId);
                if (!form) {
                    throw new Error('Edit form not found');
                }
                
                // Reset the form first to clear any previous data
                form.reset();
                
                if (isJobListing) {
                    // Fill job listing form fields
                    form.elements['id'].value = item.id;
                    form.elements['title'].value = item.title;
                    form.elements['company_id'].value = item.company_id;
                    form.elements['description'].value = item.description;
                    form.elements['work_mode'].value = item.work_mode;
                    form.elements['job_type'].value = item.job_type;
                    form.elements['slots'].value = item.slots;
                    form.elements['salary_range'].value = item.salary_range || '';
                    form.elements['requirements'].value = item.requirements || '';
                    form.elements['responsibilities'].value = item.responsibilities || '';
                    form.elements['qualifications'].value = item.qualifications || '';
                    form.elements['benefits'].value = item.benefits || '';
                    form.elements['status'].value = item.status;
                    if (item.expires_at) {
                        form.elements['expires_at'].value = item.expires_at.split(' ')[0]; // Get just the date part
                    }
                } else {
                    // Fill company form fields
                    form.elements['id'].value = item.id;
                    form.elements['name'].value = item.name;
                    form.elements['address'].value = item.address;
                    form.elements['contact_person'].value = item.contact_person || '';
                    form.elements['contact_email'].value = item.contact_email || '';
                    form.elements['contact_phone'].value = item.contact_phone || '';
                    form.elements['website'].value = item.website || '';
                    form.elements['description'].value = item.description || '';
                    form.elements['status'].value = item.status;
                    
                    // Show current logo preview for companies
                    const previewDiv = form.querySelector('#currentLogoPreview');
                    if (previewDiv) {
                        previewDiv.innerHTML = `
                            <img src="../Backend/Core/get_company_logo.php?id=${item.id}" 
                                 alt="Current Logo" 
                                 style="max-width: 100px; max-height: 100px; object-fit: contain;">
                        `;
                    }
                }

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById(modalId));
                modal.show();
                
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    title: 'Error!', 
                    text: error.message || 'Failed to load details',
                    icon: 'error'
                });
            }
        });
    });

    const updateJobBtn = document.getElementById('updateJobBtn');
    if (updateJobBtn) {
        updateJobBtn.addEventListener('click', async function() {
            const form = document.getElementById('editJobForm');
            if (form.checkValidity()) {
                try {
                    const formData = new FormData(form);
                    formData.append('action', 'updateJobListing');
                    formData.append('csrf_token', await CSRFManager.ensureValidToken());

                    const response = await fetch('../Backend/Core/MAIN.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        await Swal.fire({
                            title: 'Success!',
                            text: 'Job listing updated successfully',
                            icon: 'success'
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to update job listing');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Failed to update job listing',
                        icon: 'error'
                    });
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('editJobModal')).hide();
            } else {
                form.reportValidity();
            }
        });
    }

    // Navigation active state
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });



    // Company form validation and logo handling
    function validateLogo(file) {
        const maxSize = 2 * 1024 * 1024; // 2MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!allowedTypes.includes(file.type)) {
            throw new Error('Logo must be a JPG, PNG or GIF file');
        }
        
        if (file.size > maxSize) {
            throw new Error('Logo file size must be less than 2MB');
        }
        return true;
    }

    // Company functionality
    const saveCompanyBtn = document.getElementById('saveCompanyBtn');
    if (saveCompanyBtn) {
        saveCompanyBtn.addEventListener('click', async function() {
            const form = document.getElementById('addCompanyForm');
            if (form.checkValidity()) {
                try {
                    const formData = new FormData(form);
                    
                    // Validate logo if present
                    const logoFile = form.elements['logo'].files[0];
                    if (logoFile) {
                        validateLogo(logoFile);
                    }
                    
                    // Add action to form data
                    formData.append('action', 'addCompany');
                    formData.append('csrf_token', await CSRFManager.ensureValidToken());

                    const response = await fetch('../Backend/Core/MAIN.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        await Swal.fire({
                            title: 'Success!',
                            text: 'Company added successfully',
                            icon: 'success'
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to add company');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Failed to add company',
                        icon: 'error'
                    });
                }
            } else {
                form.reportValidity();
            }
        });
    }

    // Add event listeners for logo preview
    const logoInputs = document.querySelectorAll('input[type="file"][name="logo"]');
    logoInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                try {
                    validateLogo(file);
                    const previewDiv = this.closest('form').querySelector('#currentLogoPreview');
                    if (previewDiv) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewDiv.innerHTML = `
                                <img src="${e.target.result}" 
                                     alt="Logo Preview" 
                                     style="max-width: 100px; max-height: 100px; object-fit: contain;">
                            `;
                        };
                        reader.readAsDataURL(file);
                    }
                } catch (error) {
                    Swal.fire({
                        title: 'Invalid Logo',
                        text: error.message,
                        icon: 'error'
                    });
                    this.value = ''; // Clear the input
                }
            }
        });
    });

    // Delete functionality for all items
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const id = this.dataset.id;
            let action = '';
            let confirmMessage = 'This action cannot be undone.';
            
            // Determine action based on current page
            if (window.location.pathname.includes('Companies.php')) {
                action = 'deleteCompany';
                confirmMessage = 'This will permanently remove the company. Any associated job listings will be removed.';
            } else if (window.location.pathname.includes('Applications.php')) {
                action = 'deleteApplication';
            } else if (window.location.pathname.includes('JobListings.php')) {
                action = 'deleteJobListing';
                confirmMessage = 'This will permanently remove the job listing. Any active applications will be cancelled.';
            }

            const result = await Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('action', action);
                    formData.append('csrf_token', await CSRFManager.ensureValidToken());

                    const response = await fetch('../Backend/Core/MAIN.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        await Swal.fire({
                            title: 'Deleted!',
                            text: 'Item has been deleted successfully.',
                            icon: 'success'
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to delete item');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Failed to delete item',
                        icon: 'error'
                    });
                }
            }
        });
    });

    // Applications functionality
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', async function() {
            const id = this.dataset.id;
            const newStatus = this.value;
            
            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('status', newStatus);
                formData.append('csrf_token', await CSRFManager.ensureValidToken());

                const response = await fetch('../Backend/Core/MAIN.php?action=updateApplicationStatus', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Failed to update status',
                    icon: 'error'
                });
            }
        });
    });

    document.querySelectorAll('.update-status').forEach(button => {
        button.addEventListener('click', async function() {
            const id = this.dataset.id;
            const newStatus = this.dataset.status;
            
            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('status', newStatus);
                formData.append('csrf_token', await CSRFManager.ensureValidToken());

                const response = await fetch('../Backend/Core/MAIN.php?action=updateApplicationStatus', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Failed to update status',
                    icon: 'error'
                });
            }
        });
    });

    // Users functionality
    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const id = this.dataset.id;
            try {
                const response = await fetch(`../Backend/Core/MAIN.php?action=getUserDetails&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const user = data.data;
                    document.querySelector('#viewUserModal .modal-body').innerHTML = generateUserDetailsView(user);
                    new bootstrap.Modal(document.getElementById('viewUserModal')).show();
                } else {
                    throw new Error('Failed to load user details');
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Error occurred while loading user details',
                    icon: 'error'
                });
            }
        });
    });

    // Users functionality - status button handler
    document.querySelectorAll('.status-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const id = this.dataset.id;
            const action = this.dataset.action;
            const isDeactivate = action === 'deactivate';

            try {
                const result = await Swal.fire({
                    title: isDeactivate ? 'Deactivate User?' : 'Activate User?',
                    text: isDeactivate ? 
                        'Are you sure you want to deactivate this user? They will not be able to log in.' :
                        'Are you sure you want to activate this user? They will be able to log in again.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: isDeactivate ? '#dc3545' : '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: isDeactivate ? 'Yes, deactivate' : 'Yes, activate',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    // Show loading state
                    this.disabled = true;

                    // Get fresh CSRF token
                    const token = await CSRFManager.ensureValidToken();
                    
                    const formData = new FormData();
                    formData.append('action', 'updateUserStatus');
                    formData.append('id', id);
                    formData.append('status', isDeactivate ? 'inactive' : 'active');
                    formData.append('csrf_token', token);

                    const response = await fetch('../Backend/Core/MAIN.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        await Swal.fire({
                            title: 'Success!',
                            text: `User has been ${isDeactivate ? 'deactivated' : 'activated'} successfully.`,
                            icon: 'success',
                            confirmButtonColor: '#28a745'
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to update user status');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    title: 'Error!',
                    text: error.message || 'A server error occurred while updating user status',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            } finally {
                this.disabled = false;
            }
        });
    });

    // Force password reset functionality
    document.querySelectorAll('.force-reset-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const id = this.dataset.id;
            
            try {
                const result = await Swal.fire({
                    title: 'Force Password Reset?',
                    text: 'This user will be required to change their password on next login.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, force reset'
                });

                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'forcePasswordReset');
                    formData.append('user_id', id);
                    formData.append('csrf_token', await CSRFManager.ensureValidToken());

                    const response = await fetch('../Backend/Core/MAIN.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        await Swal.fire({
                            title: 'Success!',
                            text: 'Password reset has been forced for this user',
                            icon: 'success'
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to force password reset');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Failed to force password reset',
                    icon: 'error'
                });
            }
        });
    });

    // Update buttons functionality
    const updateCompanyBtn = document.getElementById('updateCompanyBtn');
    if (updateCompanyBtn) {
        updateCompanyBtn.addEventListener('click', async function() {
            const form = document.getElementById('editCompanyForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            try {
                const formData = new FormData(form);
                
                // Validate logo if present
                const logoFile = form.elements['logo'].files[0];
                if (logoFile) {
                    validateLogo(logoFile);
                }
                
                // Clean and validate form data
                const website = form.elements['website']?.value?.trim() || '';
                const email = form.elements['contact_email']?.value?.trim() || '';
                const phone = form.elements['contact_phone']?.value?.trim() || '';
                const description = form.elements['description']?.value?.trim() || '';
                
                // Add all fields to form data
                formData.append('action', 'updateCompany');
                formData.append('csrf_token', await CSRFManager.ensureValidToken());
                formData.append('website', website);
                formData.append('contact_email', email);
                formData.append('contact_phone', phone);
                formData.append('description', description);

                const response = await fetch('../Backend/Core/MAIN.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    await Swal.fire({
                        title: 'Success!',
                        text: 'Company updated successfully',
                        icon: 'success'
                    });
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to update company');
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Failed to update company',
                    icon: 'error'
                });
            }
        });
    }

    // Password management functionality
    document.querySelector('.profile-section').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
        modal.show();
    });

    const changePasswordBtn = document.getElementById('changePasswordBtn');
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', async function() {
            const form = document.getElementById('changePasswordForm');
            if (form.checkValidity()) {
                try {
                    const newPassword = document.getElementById('newPassword').value;
                    if (!/[A-Z]/.test(newPassword)) {
                        throw new Error('Password must contain at least one uppercase letter');
                    }
                    if (!/[a-z]/.test(newPassword)) {
                        throw new Error('Password must contain at least one lowercase letter');
                    }
                    if (!/[0-9]/.test(newPassword)) {
                        throw new Error('Password must contain at least one number');
                    }
                    if (!/[!@#$%^&*]/.test(newPassword)) {
                        throw new Error('Password must contain at least one special character');
                    }
                    if (newPassword.length < 6) {
                        throw new Error('Password must be at least 6 characters long');
                    }
                    if (newPassword !== document.getElementById('confirmPassword').value) {
                        throw new Error('Passwords do not match');
                    }

                    const formData = new FormData(form);
                    formData.append('csrf_token', await CSRFManager.ensureValidToken());

                    const response = await fetch('../Backend/Core/MAIN.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        await Swal.fire({
                            title: 'Success!',
                            text: 'Password changed successfully',
                            icon: 'success'
                        });
                        bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                        form.reset();
                    } else {
                        throw new Error(data.message || 'Failed to change password');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Failed to change password',
                        icon: 'error'
                    });
                }
            } else {
                form.reportValidity();
            }
        });
    }

    // Helper functions


    function generateCompanyEditForm(company) {
        return `
            <form id="editCompanyForm">
                <input type="hidden" name="id" value="${company.id}">
                <div class="mb-3">
                    <label class="form-label" for="company-name">Company Name</label>
                    <input type="text" class="form-control" id="company-name" name="name" value="${company.name}" required autocomplete="organization">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="contact-person">Contact Person</label>
                        <input type="text" class="form-control" id="contact-person" name="contact_person" value="${company.contact_person || ''}" autocomplete="name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="contact-phone">Contact Phone</label>
                        <input type="tel" class="form-control" id="contact-phone" name="contact_phone" value="${company.contact_phone || ''}" autocomplete="tel">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="contact-email">Contact Email</label>
                    <input type="email" class="form-control" id="contact-email" name="contact_email" value="${company.contact_email || ''}" autocomplete="email">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="company-address">Address</label>
                    <textarea class="form-control" id="company-address" name="address" rows="2" required autocomplete="street-address">${company.address}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="company-logo">Company Logo</label>
                    <input type="file" class="form-control" id="company-logo" name="logo" accept="image/*">
                    ${company.logo_path ? `<small class="form-text text-muted">Current logo will be kept if no new file is selected</small>` : ''}
                </div>
                <div id="currentLogoPreview"></div>
            </form>
        `;
    }

    function generateUserDetailsView(user) {
        return `
            <div class="user-details">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Name:</strong>
                        <p>${user.firstname} ${user.lastname}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>SR Code:</strong>
                        <p>${user.srcode}</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Email:</strong>
                        <p>${user.email}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong>
                        <p><span class="badge bg-${user.status === 'active' ? 'success' : 'warning'}">${user.status}</span></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Course:</strong>
                        <p>${user.course_name}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Department:</strong>
                        <p>${user.department_name}</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Section:</strong>
                        <p>${user.section}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Created:</strong>
                        <p>${new Date(user.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
            </div>
        `;
    }
});