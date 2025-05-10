document.addEventListener('DOMContentLoaded', async function() {
    // Initialize CSRF token management
    await CSRFManager.init();
    
    // Global search functionality
    const searchButton = document.querySelector('.search-button');
    const searchInput = document.querySelector('.search-bar input');
    
    if (searchButton && searchInput) {
        searchButton.addEventListener('click', function() {
            performSearch();
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }

    // Filter tabs functionality
    const applicationTabs = document.getElementById('applicationTabs');
    if (applicationTabs) {
        applicationTabs.addEventListener('click', function(e) {
            if (e.target.classList.contains('nav-link')) {
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                e.target.classList.add('active');
            }
        });
    }

    // View details functionality for applications
    const viewDetailsButtons = document.querySelectorAll('.view-details');
    if (viewDetailsButtons.length > 0) {
        viewDetailsButtons.forEach(button => {
            button.addEventListener('click', function() {
                const applicationData = JSON.parse(this.dataset.application);
                showApplicationDetails(applicationData);
            });
        });
    }

    // Job listing filter functionality
    const applyFiltersBtn = document.querySelector('.apply-filters-btn');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyFilters);
    }

    // Bookmark functionality
    const bookmarkIcons = document.querySelectorAll('.bookmark-icon');
    if (bookmarkIcons.length > 0) {
        bookmarkIcons.forEach(icon => {
            icon.addEventListener('click', function() {
                toggleBookmark(this);
            });
        });
    }

    // Apply for job functionality
    const applyButtons = document.querySelectorAll('.apply-btn');
    if (applyButtons.length > 0) {
        applyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const jobId = this.dataset.jobId;
                showApplyModal(jobId, this);
            });
        });
    }

    // Navigation active state
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });

    // Login form submission
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = document.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        showLoader(submitBtn);

        try {
            const formData = new FormData(this);
            formData.append('action', 'login');
            formData.append('csrf_token', await CSRFManager.ensureValidToken());

            const response = await fetch('../Backend/Core/MAIN.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                if (data.force_reset) {
                    // Redirect to password reset page if force_reset is true
                    const resetToken = await initiateForcedReset(formData.get('email'));
                    if (resetToken) {
                        window.location.href = `reset-password.html?token=${resetToken}`;
                        return;
                    }
                } else {
                    // Normal login flow
                    if (data.usertype === 'admin') {
                        window.location.href = '../Admin/Dashboard.php';
                    } else {
                        window.location.href = '../Dashboard/dashboard.php';
                    }
                }
            } else {
                showAlert('error', 'Login Failed', data.message || 'Invalid login credentials');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('error', 'Error', 'An error occurred during login');
        } finally {
            submitBtn.disabled = false;
            hideLoader(submitBtn);
        }
    });

    // Helper function to initiate forced password reset
    async function initiateForcedReset(email) {
        try {
            const formData = new FormData();
            formData.append('action', 'forgot_password');
            formData.append('email', email);
            formData.append('csrf_token', await CSRFManager.ensureValidToken());

            const response = await fetch('../Backend/Core/MAIN.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                return data.token;
            } else {
                throw new Error(data.message || 'Failed to initiate password reset');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('error', 'Error', 'Failed to initiate password reset');
            return null;
        }
    }

    // Helper functions
    function performSearch() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            // filter content based on search term
            showToast('Search', `Searching for: "${searchTerm}"`);
        }
    }

    function applyFilters() {
        const jobCards = document.querySelectorAll('#job-listings-container .job-card');
        const noJobsMessage = document.getElementById('no-jobs-message');
        let visibleJobsCount = 0;

        // Get selected filter values
        const selectedJobTypes = getSelectedCheckboxValues('jobType');
        const selectedWorkModes = getSelectedCheckboxValues('workMode');
        const selectedExperienceLevels = getSelectedCheckboxValues('experienceLevel');
        const selectedSalaryRange = document.querySelector('#salaryRange')?.value;

        jobCards.forEach(card => {
            const matches = filterCard(card, selectedJobTypes, selectedWorkModes, selectedExperienceLevels, selectedSalaryRange);
            card.style.display = matches ? 'block' : 'none';
            if (matches) visibleJobsCount++;
        });

        if (noJobsMessage) {
            noJobsMessage.style.display = visibleJobsCount === 0 ? 'block' : 'none';
        }
    }

    function filterCard(card, jobTypes, workModes, experienceLevels, salaryRange) {
        const cardJobType = card.dataset.jobType;
        const cardWorkMode = card.dataset.workMode;
        const cardExperience = card.dataset.experience;
        const cardSalary = parseInt(card.dataset.salary, 10);

        const jobTypeMatch = jobTypes.length === 0 || jobTypes.includes(cardJobType);
        const workModeMatch = workModes.length === 0 || workModes.includes(cardWorkMode);
        const experienceMatch = experienceLevels.length === 0 || experienceLevels.includes(cardExperience);
        const salaryMatch = !salaryRange || cardSalary >= parseInt(salaryRange, 10);

        return jobTypeMatch && workModeMatch && experienceMatch && salaryMatch;
    }

    function getSelectedCheckboxValues(groupName) {
        const selected = [];
        document.querySelectorAll(`.filter-group input[type="checkbox"]:checked`).forEach(checkbox => {
            if (checkbox.value.toLowerCase().includes(groupName.toLowerCase())) {
                selected.push(checkbox.value);
            }
        });
        return selected;
    }

    function toggleBookmark(icon) {
        icon.classList.toggle('bi-bookmark');
        icon.classList.toggle('bi-bookmark-fill');
        icon.classList.toggle('text-primary');
        
        showToast('Bookmark', icon.classList.contains('bi-bookmark-fill') ? 
            'Job saved to bookmarks' : 'Job removed from bookmarks');
    }

    function showApplyModal(jobId, button) {
        const jobCard = button.closest('.job-card');
        const jobTitle = jobCard.querySelector('.job-title').textContent;
        const companyName = jobCard.querySelector('.company-name').textContent;
        
        const modal = createModal('apply', { jobId, jobTitle, companyName });
        document.body.appendChild(modal);
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        setupModalListeners(modal, modalInstance);
    }

    function showApplicationDetails(application) {
        const modal = createModal('details', application);
        document.body.appendChild(modal);
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        setupModalListeners(modal, modalInstance);
    }

    function createModal(type, data) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = `${type}Modal`;
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-hidden', 'true');
        
        let content;
        if (type === 'apply') {
            content = createApplyModalContent(data);
        } else if (type === 'details') {
            content = createDetailsModalContent(data);
        }
        
        modal.innerHTML = content;
        return modal;
    }

    function setupModalListeners(modal, modalInstance) {
        const form = modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                // Handle form submission
                modalInstance.hide();
                showToast('Success', 'Application submitted successfully');
            });
        }
        
        modal.addEventListener('hidden.bs.modal', function() {
            modal.remove();
        });
    }

    function showToast(title, message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '5';
        toast.innerHTML = `
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-${type} text-white">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    function createApplyModalContent({ jobTitle, companyName }) {
        return `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Apply for Position</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>You are applying for: <strong>${jobTitle}</strong> at <strong>${companyName}</strong></p>
                        <form id="applicationForm">
                            <div class="mb-3">
                                <label for="coverLetter" class="form-label">Cover Letter</label>
                                <textarea class="form-control" id="coverLetter" rows="4" 
                                        placeholder="Introduce yourself and explain why you're a good fit for this position..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="resumeUpload" class="form-label">Upload Resume</label>
                                <input class="form-control" type="file" id="resumeUpload" accept=".pdf,.doc,.docx" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="applicationForm" class="btn btn-primary">Submit Application</button>
                    </div>
                </div>
            </div>
        `;
    }

    function createDetailsModalContent(application) {
        return `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Application Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-center mb-4">
                            <img src="../Backend/Core/get_company_logo.php?id=${application.company_id}" alt="${application.company_name} Logo" class="company-logo me-3">
                            <div>
                                <h5 class="mb-1">${application.title}</h5>
                                <p class="mb-0 text-muted">${application.company_name}</p>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="mb-3">Application Information</h6>
                                <p><strong>Application Date:</strong> ${new Date(application.created_at).toLocaleDateString()}</p>
                                <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(application.status)}">${application.status}</span></p>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="mb-3">Cover Letter</h6>
                            <div class="p-3 bg-light rounded">
                                ${application.cover_letter || 'No cover letter provided'}
                            </div>
                        </div>
                        
                        ${application.resume_path ? `
                            <div class="mb-4">
                                <h6 class="mb-3">Resume</h6>
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="bi bi-file-earmark-pdf fs-4 me-3 text-danger"></i>
                                    <div>
                                        <p class="mb-1">Resume</p>
                                        <p class="mb-0 small text-muted">Uploaded with application</p>
                                    </div>
                                    <a href="${application.resume_path}" class="btn btn-sm btn-outline-primary ms-auto" target="_blank">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
    }

    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'reviewing': 'info',
            'interview': 'primary',
            'accepted': 'success',
            'rejected': 'danger'
        };
        return colors[status] || 'secondary';
    }
});