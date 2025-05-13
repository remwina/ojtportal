document.addEventListener('DOMContentLoaded', async function() {
    // Initialize CSRF token management
    try {
        console.log('Initializing CSRF token...');
        const initialized = await CSRFManager.init();
        if (!initialized) {
            throw new Error('Failed to initialize CSRF protection');
        }
    } catch (error) {
        console.error('CSRF initialization error:', error);
        await Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to initialize security token. Please refresh the page.'
        });
    }
    
    // Global search functionality
    const searchButton = document.querySelector('.search-button');
    const searchInput = document.querySelector('.search-bar input');
    
    if (searchButton && searchInput) {
        const performSearch = (searchTerm) => {
            const rows = document.querySelectorAll('tbody tr');
            searchTerm = searchTerm.toLowerCase().trim();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        };

        searchButton.addEventListener('click', () => performSearch(searchInput.value));
        searchInput.addEventListener('input', (e) => performSearch(e.target.value));
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch(searchInput.value);
            }
        });
    }

    // Apply for job functionality
    function attachApplyButtonListeners() {
        const applyButtons = document.querySelectorAll('.apply-btn');
        applyButtons.forEach(button => {
            button.removeEventListener('click', handleApplyButtonClick);
            button.addEventListener('click', handleApplyButtonClick);
        });
    }

    async function handleApplyButtonClick(e) {
        e.preventDefault();
        const button = this;
        const jobId = button.dataset.jobId;
        const row = button.closest('tr');
        const jobTitle = row.querySelector('h6').textContent.trim();
        const companyName = row.querySelector('.text-muted').textContent.trim();

        try {
            // Ensure we have a valid CSRF token
            const token = await CSRFManager.ensureValidToken();
            if (!token) {
                throw new Error('Security token is missing. Please refresh the page.');
            }

            // Create and show modal
            const modalContent = createApplyModalContent({ jobId, jobTitle, companyName });
            const modalWrapper = document.createElement('div');
            modalWrapper.innerHTML = modalContent;
            document.body.appendChild(modalWrapper.firstElementChild);

            const modalElement = document.getElementById('applyModal');
            const modalInstance = new bootstrap.Modal(modalElement);
            
            // Add token to form
            const form = modalElement.querySelector('#applicationForm');
            CSRFManager.updateFormToken(form);

            setupApplyModalListeners(modalElement, modalInstance, button);
            modalInstance.show();
        } catch (error) {
            console.error('Error showing apply modal:', error);
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to open application form. Please try again.'
            });
        }
    }

    function createApplyModalContent({ jobId, jobTitle, companyName }) {
        return `
            <div class="modal fade" id="applyModal" tabindex="-1" aria-labelledby="applyModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="applyModalLabel">Apply for Position</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="applicationForm">
                                <input type="hidden" name="job_id" value="${jobId}">
                                <p>You are applying for: <strong>${jobTitle}</strong></p>
                                <p>at <strong>${companyName}</strong></p>
                                <p class="mt-3">By clicking Submit, you agree to apply for this position.</p>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="submitApplication">Submit Application</button>
                        </div>
                    </div>
                </div>
            </div>`;
    }

    function setupApplyModalListeners(modalElement, modalInstance, applyButton) {
        const submitBtn = modalElement.querySelector('#submitApplication');
        const form = modalElement.querySelector('#applicationForm');

        submitBtn.addEventListener('click', async function() {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            submitBtn.disabled = true;
            try {
                const formData = new FormData(form);
                formData.append('action', 'applyForJob');
                formData.append('csrf_token', await CSRFManager.ensureValidToken());

                const response = await fetch('../Backend/Core/MAIN.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Csrf-Token': await CSRFManager.ensureValidToken(),
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to submit application');
                }

                modalInstance.hide();
                await Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Your application has been submitted successfully!'
                });

                // Update button state
                applyButton.disabled = true;
                applyButton.classList.remove('btn-primary');
                applyButton.classList.add('btn-success');
                applyButton.innerHTML = '<i class="fas fa-check"></i> Applied';

            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to submit application'
                });
            } finally {
                submitBtn.disabled = false;
            }
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            modalElement.remove();
        });
    }

    // Initialize functionality
    attachApplyButtonListeners();

    // Rest of your existing code for other features...
    const bookmarkIcons = document.querySelectorAll('.bookmark-icon');
    if (bookmarkIcons.length > 0) {
        bookmarkIcons.forEach(icon => {
            icon.addEventListener('click', function() {
                toggleBookmark(this);
            });
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

    function showApplicationDetails(application) {
        // Use SweetAlert2 to show application details in a modal
        Swal.fire({
            title: 'Application Details',
            html: `
                <div class="application-details">
                    <div class="mb-3">
                        <strong>Position:</strong> ${application.title}
                    </div>
                    <div class="mb-3">
                        <strong>Company:</strong> ${application.company_name}
                    </div>
                    <div class="mb-3">
                        <strong>Applied On:</strong> ${new Date(application.created_at).toLocaleDateString()}
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong> 
                        <span class="status-badge status-${application.status.toLowerCase()}">
                            ${application.status.charAt(0).toUpperCase() + application.status.slice(1)}
                        </span>
                    </div>
                    <div class="mb-3">
                        <strong>Job Type:</strong> ${application.job_type}
                    </div>
                    <div class="mb-3">
                        <strong>Cover Letter:</strong>
                        <div class="cover-letter mt-2 p-3 bg-light rounded">
                            ${application.cover_letter || 'No cover letter provided'}
                        </div>
                    </div>
                </div>
            `,
            width: '600px',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                container: 'application-details-modal'
            }
        });
    }

    // Helper functions
    function performSearch() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            // filter content based on search term
            showToast('Search', `Searching for: "${searchTerm}"`);
        }
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

    function toggleBookmark(icon) {
        icon.classList.toggle('bi-bookmark');
        icon.classList.toggle('bi-bookmark-fill');
        icon.classList.toggle('text-primary');
        
        showToast('Bookmark', icon.classList.contains('bi-bookmark-fill') ? 
            'Job saved to bookmarks' : 'Job removed from bookmarks');
    }
});