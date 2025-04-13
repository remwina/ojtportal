# Student Job Listing Platform

A web application for managing student job listings and applications.

## Features

- User Registration and Authentication
- SR Code Validation
- CSRF Protection
- Secure Password Handling
- Modern UI with Blob Animations

## Setup Instructions

1. Clone the repository:
```bash
git clone [your-repository-url]
```

2. Set up your XAMPP environment:
   - Place the project in your XAMPP's htdocs folder
   - Start Apache and MySQL services

3. Database Setup:
   - Visit `http://localhost/Finals_But_Its_ADBMS/joblisting/Backend/Core/Config/migrate.php`
   - This will create the necessary database and tables

4. Access the application:
   - Registration: `http://localhost/Finals_But_Its_ADBMS/joblisting/Frontend/register.html`
   - Login: `http://localhost/Finals_But_Its_ADBMS/joblisting/Frontend/login.php`

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP (or similar PHP development environment)
- Modern web browser

## Project Structure

```
joblisting/
├── Assets/
│   ├── Scripts/
│   └── Styles/
├── Backend/
│   ├── Core/
│   │   ├── Config/
│   │   └── Security/
│   └── Shell/
└── Frontend/
    ├── register.html
    └── login.php
```

## Security Features

- CSRF Protection
- Password Hashing
- Input Validation
- SQL Injection Prevention
- XSS Protection 