# Job Placement and Listing Management System

<div align="center">
<img src="JobListing/Assets/Images/BatStateU-NEU-Logo.png" width="120">
</div>

## Overview
A web-based system for managing job placements and listings, designed for educational institutions. This system facilitates the connection between students, employers, and administrative staff by providing a centralized platform for job opportunities.

## Features
- User role-based access control (Admin, User)
- Secure authentication system
- SR Code validation for students
- Password policy enforcement
- Session management
- CSRF protection

## Technical Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP (recommended for local development)

## Installation

1. **Environment Setup**
   - Install XAMPP
   - Start Apache and MySQL services
   - Ensure ports 80 (Apache) and 3306 (MySQL) are available

2. **Database Configuration**
   - Navigate to `JobListing/Backend/Core/Config/DataManagement/setup.php`
   - The setup script will automatically:
     - Create the required database
     - Initialize necessary tables
     - Create the default administrator account

3. **System Access**
   - Access the login page at: `JobListing/Frontend/login.html`
   - Use the default admin credentials for first-time access

## Default Administrator Account
```
Email: admin@admin.com
Password: Admin@123
SR Code: 21-00001
```

## Database Configuration
```
Host: localhost
Username: root
Password: root
Database: joblisting
```

## Security Features
- Password hashing
- Input validation
- XSS protection
- CSRF token implementation
- Secure session management

## Troubleshooting
1. Verify XAMPP services are running
2. Check port availability
3. Ensure proper file permissions
4. Clear browser cache if experiencing UI issues

## Support
For technical support or bug reports, please contact the system administrator.

## Project Structure
```
JobListing/
├── Assets/         # Static resources
├── Backend/        # Server-side logic
│   ├── Core/       # Core functionality
│   └── Shell/      # Authentication handlers
└── Frontend/       # Client-side interface
```