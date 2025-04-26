<?php
class DatabaseSchema {
    public static function getDepartments() {
        return [
            'College of Informatics and Computing Sciences',
            'College of Engineering Technology',
            'College of Arts and Sciences',
            'College of Teacher Education',
            'College of Accountancy, Business and Economics'
        ];
    }

    public static function getCourses() {
        return [
            // CICS
            ['BS Information Technology', 1],
            ['BS Computer Science', 1],
            // CET
            ['Bachelor of Industrial Technology', 2],
            // CAS
            ['BA Communication', 3],
            ['BA Psychology', 3],
            ['BA Political Science', 3],
            // CTE
            ['Bachelor of Elementary Education', 4],
            ['Bachelor of Secondary Education', 4],
            // CABE
            ['BS Accountancy', 5],
            ['BS Accounting Management', 5],
            ['BS Applied Economics', 5],
            ['BS Business Administration', 5],
            ['BS Entrepreneurship', 5],
            ['BS Hotel and Restaurant Management', 5],
            ['BS Tourism Management', 5],
            ['BA Public Administration', 5],
            ['BS Customs Administration', 5]
        ];
    }

    public static function getTableDefinitions() {
        return [
            'departments' => "CREATE TABLE IF NOT EXISTS departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL
            )",
            
            'courses' => "CREATE TABLE IF NOT EXISTS courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                department_id INT NOT NULL,
                FOREIGN KEY (department_id) REFERENCES departments(id)
            )",
            
            'users' => "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                srcode VARCHAR(9) UNIQUE NOT NULL,
                firstname VARCHAR(50) NOT NULL,
                lastname VARCHAR(50) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                course_id INT NOT NULL,
                section VARCHAR(20) NOT NULL,
                usertype ENUM('admin', 'user', 'none') NOT NULL DEFAULT 'none',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                FOREIGN KEY (course_id) REFERENCES courses(id),
                INDEX idx_email_status (email, status),
                INDEX idx_srcode_status (srcode, status)
            )"
        ];
    }

    public static function getDefaultAdmin() {
        return [
            'srcode' => '21-00001',
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@admin.com',
            'password' => 'Admin@123',
            'course' => 1,  // Default to BSIT
            'section' => '2201',
            'usertype' => 'admin',
            'status' => 'active'
        ];
    }
}