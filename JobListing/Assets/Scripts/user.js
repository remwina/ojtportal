document.addEventListener('DOMContentLoaded', async function() {
    // Initialize CSRF token management
    try {
        console.log('Initializing CSRF token...');
        const initialized = await CSRFManager.init();
        if (!initialized) {
            throw new Error('Failed to initialize CSRF protection');
        }
        console.log('CSRF token initialized successfully:', CSRFManager.getToken());
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
        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }

    // Apply for job functionality
    function attachApplyButtonListeners() {
        const applyButtons = document.querySelectorAll('.apply-btn');
        if (applyButtons.length > 0) {
            applyButtons.forEach(button => {
                button.addEventListener('click', handleApplyButtonClick);
            });
        }
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
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'csrf_token';
            tokenInput.value = token;
            form.appendChild(tokenInput);

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
                                <div class="mb-3">
                                    <label for="coverLetter" class="form-label">Cover Letter</label>
                                    <textarea class="form-control" id="coverLetter" name="coverLetter" rows="4" 
                                        placeholder="Introduce yourself and explain why you're a good fit for this position..." required></textarea>
                                </div>
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
                // Get fresh token before submission
                const token = await CSRFManager.ensureValidToken();
                if (!token) {
                    throw new Error('Security token is missing. Please refresh the page.');
                }

                const formData = new FormData(form);
                formData.append('action', 'applyForJob');

                // Add token to both FormData and headers
                formData.set('csrf_token', token);

                const response = await fetch('../Backend/Core/MAIN.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Csrf-Token': token,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json();
                
                if (data.success) {
                    modalInstance.hide();
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Your application has been submitted successfully.'
                    });

                    // Update button to show applied state
                    applyButton.outerHTML = `
                        <button class="btn btn-secondary btn-sm" disabled>
                            <i class="bi bi-check2-circle"></i> Applied
                        </button>`;
                } else {
                    throw new Error(data.message || 'Failed to submit application');
                }
            } catch (error) {
                console.error('Application error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to submit application'
                });
            } finally {
                submitBtn.disabled = false;
            }
        });

        modalElement.addEventListener('hidden.bs.modal', function() {
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