<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('location: ../Frontend/login.html');
    exit();
}

require_once '../Backend/Core/Config/DataManagement/DB_Operations.php';
$db = new SQL_Operations();
$conn = $db->getConnection();

// Fetch all active job listings
$query = "SELECT jl.*, c.name as company_name, c.logo_path 
          FROM job_listings jl 
          JOIN companies c ON jl.company_id = c.id 
          WHERE jl.status = 'open' 
          AND (jl.expires_at IS NULL OR jl.expires_at >= CURDATE())
          ORDER BY jl.created_at DESC";
$result = $conn->query($query);
$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

$student_name = $_SESSION['student_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-red: #C8102E;
            --soft-red: #E63946;
            --light-gray: #F8F9FA;
            --dark-gray: #495057;
            --pure-white: #FFFFFF;
        }
        
        body {
            background-color: var(--light-gray);
            color: var(--dark-gray);
        }
        
        .sidebar {
            background-color: var(--primary-red);
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: inherit;
            overflow-y: auto;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px;
        }
        
        .logo {
            width: 150px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .logo-text {
            font-size: 1.4rem;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 5px;
        }
        
        .logo-subtext {
            font-size: 1rem;
            color: #ffffff;
            font-weight: 500;
        }
        
        .nav-link {
            color: #ffffff;
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-link i {
            font-size: 1.2rem;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        
        .search-bar {
            width: 1100px;
            max-width: 100%;
            margin: 0 auto;
            display: flex;
            gap: 10px;
        }
        
        .search-container {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        
        .search-button {
            padding: 0.375rem 0.75rem;
            background-color: var(--primary-red);
            color: var(--pure-white);
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        
        .search-button:hover {
            background-color: var(--soft-red);
        }
        
        .profile-section {
            display: flex;
            align-items: center;
            margin-left: -50px;
        }
        
        .profile-icon {
            font-size: 1.5rem;
            color: var(--primary-red);
            cursor: pointer;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header i {
            font-size: 1.5rem;
            margin-right: 10px;
            color: var(--primary-red);
        }
        
        .filter-section {
            background: var(--pure-white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-gray);
            display: flex;
            align-items: center;
        }
        
        .filter-title i {
            margin-right: 8px;
            color: var(--primary-red);
        }
        
        .filter-group {
            margin-bottom: 20px;
        }
        
        .filter-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }
        
        .clear-filters {
            color: var(--primary-red);
            background: none;
            border: none;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .clear-filters:hover {
            color: var(--soft-red);
            text-decoration: underline;
        }
        
        .job-card {
            border: 1px solid #dee2e6;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: var(--pure-white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            background-color: #F8F9FA;
        }
        
        .job-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-gray);
            margin-bottom: 5px;
        }
        
        .company-name {
            font-size: 0.95rem;
            color: #6c757d;
        }
        
        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge i {
            font-size: 0.8rem;
        }
        
        .badge-web {
            background-color: #007BFF;
            color: white;
        }
        
        .badge-remote {
            background-color: #6C757D;
            color: white;
        }
        
        .badge-php {
            background-color: #17A2B8;
            color: white;
        }
        
        .badge-hybrid {
            background-color: #6F42C1;
            color: white;
        }
        
        .badge-testing {
            background-color: #20C997;
            color: white;
        }
        
        .job-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .job-details i {
            color: var(--primary-red);
            margin-right: 5px;
        }
        
        .job-details .salary {
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .bookmark-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #adb5bd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .bookmark-icon:hover {
            color: var(--primary-red);
        }
        
        .apply-btn {
            background-color: var(--primary-red);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.2s;
        }
        
        .apply-btn:hover {
            background-color: var(--soft-red);
            color: white;
        }
        
        .sort-dropdown {
            margin-bottom: 20px;
        }
        
        .sort-dropdown .form-select {
            border-radius: 20px;
            padding-right: 30px;
        }

        .apply-filters-btn {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
            color: var(--pure-white);
        }
        
        .apply-filters-btn:hover {
            background-color: var(--soft-red);
            border-color: var(--soft-red);
             color: var(--pure-white);
        }
        
        @media (max-width: 768px) {
            .filter-section {
                margin-bottom: 15px;
            }
            
            .filter-group {
                margin-bottom: 10px;
            }
            
            .job-card {
                margin-bottom: 15px;
            }
        }
  
        .main-content {
            margin-left: 16.666667%;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="logo-container">
                    <img src="BatStateU-NEU-Logo.png" alt="BatStateU-NEU Logo" class="logo">
                    <div class="logo-text">BatState-U NEU</div>
                    <div class="logo-subtext">OJT Portal</div>
                </div>
                <div class="d-flex flex-column">
                    <a href="dashboard.php" class="nav-link">
                        <i class="bi bi-house-door-fill"></i> Dashboard
                    </a>
                    <a href="job_listings.php" class="nav-link active">
                        <i class="bi bi-briefcase-fill"></i> Job Listings
                    </a>
                    <a href="applications.php" class="nav-link">
                        <i class="bi bi-file-earmark-text-fill"></i> My Applications
                    </a>
                    <a href="companies.php" class="nav-link">
                        <i class="bi bi-building-fill"></i> Partner Companies
                    </a>
                    <a href="resume.php" class="nav-link">
                        <i class="bi bi-file-person-fill"></i> My Resume
                    </a>
                    <a href="profile.php" class="nav-link">
                        <i class="bi bi-person-fill"></i> My Profile
                    </a>
                    <a href="logout.php" class="nav-link">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4 main-content">
                <!-- Search and Profile -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="search-container">
                        <div class="search-bar">
                            <input type="text" class="form-control" placeholder="Type to search...">
                            <button class="search-button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="profile-section">
                        <a href="profile.php">
                            <i class="bi bi-person-circle profile-icon"></i>
                        </a>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="filter-card p-4 mb-4">
                    <div class="row">
                        <!-- Filter Groups -->
                        <div class="col-md-3 col-sm-6 mb-3 filter-group">
                            <h6 class="filter-title"><i class="bi bi-briefcase"></i> Job Type</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Full Time" id="fullTime">
                                <label class="form-check-label" for="fullTime">Full Time</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Part Time" id="partTime">
                                <label class="form-check-label" for="partTime">Part Time</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Internship" id="internship">
                                <label class="form-check-label" for="internship">Internship</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3 filter-group">
                            <h6 class="filter-title"><i class="bi bi-globe"></i> Work Mode</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Remote" id="remote">
                                <label class="form-check-label" for="remote">Remote</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Hybrid" id="hybrid">
                                <label class="form-check-label" for="hybrid">Hybrid</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="On-site" id="onsite">
                                <label class="form-check-label" for="onsite">On-site</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3 filter-group">
                            <h6 class="filter-title"><i class="bi bi-graph-up"></i> Experience Level</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Entry Level" id="entry">
                                <label class="form-check-label" for="entry">Entry Level</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Intermediate" id="intermediate">
                                <label class="form-check-label" for="intermediate">Intermediate</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Advanced" id="advanced">
                                <label class="form-check-label" for="advanced">Advanced</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3 filter-group">
                            <h6 class="filter-title"><i class="bi bi-currency-dollar"></i> Salary Range</h6>
                            <select class="form-select" id="salaryRange">
                                <option selected>Any</option>
                                <option>₱5,000 - ₱10,000</option>
                                <option>₱10,000 - ₱15,000</option>
                                <option>₱15,000 - ₱20,000</option>
                                <option>₱20,000+</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-sm btn-outline-primary me-2 clear-filters">
                            <i class="bi bi-x-circle"></i> Clear All Filters
                        </button>
                         <button class="btn btn-sm btn-primary apply-filters-btn">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                    </div>
                </div>

                <!-- Job Listings -->
                <div class="section-header">
                    <i class="bi bi-briefcase-fill"></i>
                    <h4 class="mb-0">Available Positions</h4>
                </div>
                
                <!-- Sort Dropdown -->
                <div class="sort-dropdown">
                    <select class="form-select">
                        <option selected>Sort by: Latest</option>
                        <option>Highest Salary</option>
                        <option>Most Relevant</option>
                        <option>A-Z</option>
                    </select>
                </div>

                <!-- Job Cards -->
                <div class="row" id="job-listings-container">
                    <?php foreach ($jobs as $job): ?>
                    <div class="col-md-6">
                        <div class="job-card" 
                             data-job-type="<?php echo htmlspecialchars(ucfirst($job['job_type'])); ?>" 
                             data-work-mode="<?php echo htmlspecialchars($job['location'] == 'Remote' ? 'Remote' : 'On-site'); ?>"
                             data-salary="<?php echo htmlspecialchars($job['salary_range']); ?>">
                            <i class="bi bi-bookmark bookmark-icon"></i>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($job['logo_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($job['company_name']); ?> Logo" 
                                     class="company-logo me-3">
                                <div>
                                    <h5 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                    <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <?php 
                                $tags = explode(',', $job['tags']);
                                foreach ($tags as $tag): ?>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                <?php endforeach; ?>
                                <span class="badge <?php echo $job['location'] == 'Remote' ? 'badge-remote' : 'badge-onsite'; ?>">
                                    <i class="bi <?php echo $job['location'] == 'Remote' ? 'bi-laptop' : 'bi-building'; ?>"></i>
                                    <?php echo htmlspecialchars($job['location']); ?>
                                </span>
                            </div>
                            <div class="job-details">
                                <p class="mb-2"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?></p>
                                <p class="mb-2"><i class="bi bi-calendar3"></i> Posted <?php echo timeAgo($job['created_at']); ?></p>
                                <p class="mb-2"><i class="bi bi-currency-dollar"></i> 
                                    <span class="salary"><?php echo htmlspecialchars($job['salary_range']); ?></span>
                                </p>
                            </div>
                            <button class="btn apply-btn btn-sm mt-3" data-job-id="<?php echo $job['id']; ?>">
                                <i class="bi bi-send"></i> Apply Now
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div id="no-jobs-message" class="col-12 text-center" style="display: none;">
                        <p class="fs-5 text-muted mt-4">No jobs match the current filters.</p>
                    </div>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bookmark functionality
            const bookmarkIcons = document.querySelectorAll('.bookmark-icon');
            bookmarkIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    this.classList.toggle('bi-bookmark');
                    this.classList.toggle('bi-bookmark-fill');
                    this.classList.toggle('text-primary');
                    
                    // Show toast notification
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = '5';
                    toast.innerHTML = `
                        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong class="me-auto">Notification</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                ${this.classList.contains('bi-bookmark-fill') ? 'Job saved to bookmarks' : 'Job removed from bookmarks'}
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    
                    // Remove toast after 3 seconds
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                });
            });
            
            // Apply button functionality
            const applyButtons = document.querySelectorAll('.apply-btn');
            applyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const jobTitle = this.closest('.job-card').querySelector('.job-title').textContent;
                    const companyName = this.closest('.job-card').querySelector('.company-name').textContent;
                    
                    // Show application modal
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.id = 'applyModal';
                    modal.setAttribute('tabindex', '-1');
                    modal.setAttribute('aria-labelledby', 'applyModalLabel');
                    modal.setAttribute('aria-hidden', 'true');
                    modal.innerHTML = `
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="applyModalLabel">Apply for Position</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>You are applying for: <strong>${jobTitle}</strong> at <strong>${companyName}</strong></p>
                                    <div class="mb-3">
                                        <label for="coverLetter" class="form-label">Cover Letter</label>
                                        <textarea class="form-control" id="coverLetter" rows="4" placeholder="Introduce yourself and explain why you're a good fit for this position..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="resumeUpload" class="form-label">Upload Resume</label>
                                        <input class="form-control" type="file" id="resumeUpload">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="submitApplication">Submit Application</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    
                    // Initialize and show the modal
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                    
                    // Handle submit button
                    document.getElementById('submitApplication').addEventListener('click', function() {
                        modalInstance.hide();
                        
                        // Show success toast
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 p-3';
                        toast.style.zIndex = '5';
                        toast.innerHTML = `
                            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header bg-success text-white">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong class="me-auto">Success</strong>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                                <div class="toast-body">
                                    Your application has been submitted successfully!
                                </div>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        
                        setTimeout(() => {
                            toast.remove();
                        }, 3000);
                        
                        modal.addEventListener('hidden.bs.modal', function() {
                            modal.remove();
                        });
                    });
                });
            });
            
            // Clear filters functionality
            const clearFiltersBtn = document.querySelector('.clear-filters');
            clearFiltersBtn.addEventListener('click', function() {
                // Reset all checkboxes
                document.querySelectorAll('.filter-group .form-check-input').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Reset salary dropdown
                document.querySelector('.filter-group .form-select').selectedIndex = 0;
                
                // Apply filters after clearing
                applyFilters();
                
                // Show toast notification
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = '5';
                toast.innerHTML = `
                    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong class="me-auto">Filters Reset</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            All filters have been cleared
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                
                // Remove toast after 3 seconds
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            });
            
            // new Apply Filters button
            const applyFiltersBtn = document.querySelector('.apply-filters-btn');
            applyFiltersBtn.addEventListener('click', applyFilters);


            // --- Filtering Logic ---
            function applyFilters() {
                const jobCards = document.querySelectorAll('#job-listings-container .job-card');
                const noJobsMessage = document.getElementById('no-jobs-message');
                let visibleJobsCount = 0;

                // Get selected filter values
                const selectedJobTypes = getSelectedCheckboxValues('jobType');
                const selectedWorkModes = getSelectedCheckboxValues('workMode');
                const selectedExperienceLevels = getSelectedCheckboxValues('experienceLevel');
                const selectedSalaryRange = document.querySelector('#salaryRange').value;

                jobCards.forEach(card => {
                    const cardJobType = card.dataset.jobType;
                    const cardWorkMode = card.dataset.workMode;
                    const cardExperience = card.dataset.experience;
                    const cardSalary = parseInt(card.dataset.salary, 10);

                    // Check filters
                    const jobTypeMatch = selectedJobTypes.length === 0 || selectedJobTypes.includes(cardJobType);
                    const workModeMatch = selectedWorkModes.length === 0 || selectedWorkModes.includes(cardWorkMode);
                    const experienceMatch = selectedExperienceLevels.length === 0 || selectedExperienceLevels.includes(cardExperience);
                    const salaryMatch = checkSalaryRange(cardSalary, selectedSalaryRange);

                    // Show/hide card
                    if (jobTypeMatch && workModeMatch && experienceMatch && salaryMatch) {
                        card.closest('.col-md-6').style.display = ''; // Show column
                        visibleJobsCount++;
                    } else {
                         card.closest('.col-md-6').style.display = 'none'; // Hide column
                    }
                });
                
                // Show/hide the 'no jobs' message
                noJobsMessage.style.display = visibleJobsCount === 0 ? 'block' : 'none';
            }

            function getSelectedCheckboxValues(groupName) {
                const values = [];
                 document.querySelectorAll(`.filter-group input[type="checkbox"]`).forEach(checkbox => {
                     let matchesGroup = false;
                     if (groupName === 'jobType' && ['fullTime', 'partTime', 'internship'].includes(checkbox.id)) matchesGroup = true;
                     if (groupName === 'workMode' && ['remote', 'hybrid', 'onsite'].includes(checkbox.id)) matchesGroup = true;
                     if (groupName === 'experienceLevel' && ['entry', 'intermediate', 'advanced'].includes(checkbox.id)) matchesGroup = true;

                    if (matchesGroup && checkbox.checked) {
                        // Map ID to the data attribute value
                        switch(checkbox.id) {
                             // Job Types
                            case 'fullTime': values.push('Full Time'); break;
                            case 'partTime': values.push('Part Time'); break;
                            case 'internship': values.push('Internship'); break;
                             // Work Modes
                            case 'remote': values.push('Remote'); break;
                            case 'hybrid': values.push('Hybrid'); break;
                            case 'onsite': values.push('On-site'); break;
                             // Experience Levels
                            case 'entry': values.push('Entry Level'); break;
                            case 'intermediate': values.push('Intermediate'); break;
                            case 'advanced': values.push('Advanced'); break;
                        }
                    }
                });
                return values;
            }

            function checkSalaryRange(cardSalary, selectedRange) {
                if (selectedRange === 'Any') return true;

                const rangeMap = {
                    '₱5,000 - ₱10,000': { min: 5000, max: 10000 },
                    '₱10,000 - ₱15,000': { min: 10000, max: 15000 },
                    '₱15,000 - ₱20,000': { min: 15000, max: 20000 },
                    '₱20,000+': { min: 20000, max: Infinity }
                };

                const range = rangeMap[selectedRange];
                if (!range) return true; 
                return cardSalary >= range.min && cardSalary <= range.max; 
            }
            // --- End Filtering Logic ---


            // Sort dropdown functionality
            const sortDropdown = document.querySelector('.sort-dropdown select');
            sortDropdown.addEventListener('change', function() {
                const sortValue = this.value;
                
                // Get the job listings container
                const jobListingsContainer = document.getElementById('job-listings-container');
                const jobCards = Array.from(jobListingsContainer.querySelectorAll('.job-card'));
                
                // Sort the job cards based on the selected option
                jobCards.sort((a, b) => {
                    switch(sortValue) {
                        case 'Highest Salary':
                            // Sort by salary (descending)
                            return parseInt(b.dataset.salary) - parseInt(a.dataset.salary);
                        
                        case 'A-Z':
                            // Sort by job title (alphabetically)
                            const titleA = a.querySelector('.job-title').textContent;
                            const titleB = b.querySelector('.job-title').textContent;
                            return titleA.localeCompare(titleB);
                        
                        case 'Most Relevant':
                            const expA = a.dataset.experience;
                            const expB = b.dataset.experience;
                            const expPriority = {
                                'Entry Level': 1,
                                'Intermediate': 2,
                                'Advanced': 3
                            };
                            
                            return expPriority[expA] - expPriority[expB];
                        
                        case 'Latest':
                        default:
                            return Math.random() - 0.5;
                    }
                });
                
                // Clear the container
                jobCards.forEach(card => {
                    card.closest('.col-md-6').remove();
                });
                
                // Re-append the sorted cards
                jobCards.forEach(card => {
                    const colDiv = document.createElement('div');
                    colDiv.className = 'col-md-6';
                    colDiv.appendChild(card);
                    jobListingsContainer.appendChild(colDiv);
                });
                
                // Show toast notification
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = '5';
                toast.innerHTML = `
                    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header">
                            <i class="bi bi-sort-down me-2"></i>
                            <strong class="me-auto">Sorting</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            Jobs sorted by: ${sortValue}
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                
                // Remove toast after 3 seconds
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            });
            
            // Search functionality
            const searchButton = document.querySelector('.search-button');
            const searchInput = document.querySelector('.search-bar input');
            
            searchButton.addEventListener('click', function() {
                performSearch();
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            function performSearch() {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = '5';
                    toast.innerHTML = `
                        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header">
                                <i class="bi bi-search me-2"></i>
                                <strong class="me-auto">Search</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                Searching for: "${searchTerm}"
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    
                    // Remove toast after 3 seconds
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                }
            }
        });
    </script>
</body>
</html>

<?php
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
?>

<script>
// Update the apply button click handler to work with backend
document.querySelectorAll('.apply-btn').forEach(button => {
    button.addEventListener('click', function() {
        const jobId = this.dataset.jobId;
        const jobTitle = this.closest('.job-card').querySelector('.job-title').textContent;
        const companyName = this.closest('.job-card').querySelector('.company-name').textContent;
        
        // Show application modal
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'applyModal';
        modal.setAttribute('tabindex', '-1');
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Apply for Position</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>You are applying for: <strong>${jobTitle}</strong> at <strong>${companyName}</strong></p>
                        <div class="mb-3">
                            <label for="coverLetter" class="form-label">Cover Letter</label>
                            <textarea class="form-control" id="coverLetter" rows="4" 
                                    placeholder="Introduce yourself and explain why you're a good fit for this position..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="submitApplication">Submit Application</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        document.getElementById('submitApplication').addEventListener('click', async function() {
            const coverLetter = document.getElementById('coverLetter').value;
            
            try {
                const formData = new FormData();
                formData.append('action', 'applyForJob');
                formData.append('job_id', jobId);
                formData.append('cover_letter', coverLetter);
                formData.append('csrf_token', '<?php echo $_SESSION["csrf_token"]; ?>');
                
                const response = await fetch('../Backend/Core/MAIN.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    modalInstance.hide();
                    showToast('Success', 'Application submitted successfully!', 'success');
                } else {
                    throw new Error(result.message || 'Failed to submit application');
                }
            } catch (error) {
                showToast('Error', error.message, 'error');
            }
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            modal.remove();
        });
    });
});

function showToast(title, message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '5';
    toast.innerHTML = `
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-${type} text-white">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>