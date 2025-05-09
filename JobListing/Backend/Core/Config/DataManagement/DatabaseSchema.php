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
            )",

            'companies' => "CREATE TABLE IF NOT EXISTS companies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                address TEXT NOT NULL,
                contact_person VARCHAR(100),
                contact_email VARCHAR(255),
                contact_phone VARCHAR(20),
                website VARCHAR(255),
                description TEXT,
                logo_path VARCHAR(255),
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            'job_listings' => "CREATE TABLE IF NOT EXISTS job_listings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                title VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                requirements TEXT,
                location VARCHAR(255),
                job_type ENUM('full-time', 'part-time', 'internship') NOT NULL,
                slots INT NOT NULL DEFAULT 1,
                status ENUM('open', 'closed', 'draft') NOT NULL DEFAULT 'draft',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                expires_at DATE,
                FOREIGN KEY (company_id) REFERENCES companies(id)
            )",
            
            'job_applications' => "CREATE TABLE IF NOT EXISTS job_applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                job_id INT NOT NULL,
                status ENUM('pending', 'approved', 'rejected', 'interview') NOT NULL DEFAULT 'pending',
                resume_path VARCHAR(255),
                cover_letter TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (job_id) REFERENCES job_listings(id),
                UNIQUE KEY unique_application (user_id, job_id)
            )",
            
            'applications' => "CREATE TABLE IF NOT EXISTS applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                company_name VARCHAR(100) NOT NULL,
                position VARCHAR(100) NOT NULL,
                status ENUM('pending', 'approved', 'rejected', 'interview') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",
            
            'student_resumes' => "CREATE TABLE IF NOT EXISTS student_resumes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                resume_path VARCHAR(255) NOT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
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

    public static function initializeDatabase($conn) {
        try {
            // Create tables using schema definitions
            foreach (self::getTableDefinitions() as $tableName => $definition) {
                if (!$conn->query($definition)) {
                    throw new Exception("Error creating $tableName table: " . $conn->error);
                }
            }

            $result = $conn->query("SELECT COUNT(*) as count FROM departments");
            $hasDepartments = ($result && $result->fetch_assoc()['count'] > 0);

            $result = $conn->query("SELECT COUNT(*) as count FROM courses");
            $hasCourses = ($result && $result->fetch_assoc()['count'] > 0);

            if (!$hasDepartments && !$hasCourses) {
                // Insert departments
                $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
                foreach (self::getDepartments() as $dept) {
                    $stmt->bind_param('s', $dept);
                    $stmt->execute();
                }
                $stmt->close();

                // Insert courses
                $stmt = $conn->prepare("INSERT INTO courses (name, department_id) VALUES (?, ?)");
                foreach (self::getCourses() as $course) {
                    $stmt->bind_param('si', $course[0], $course[1]);
                    $stmt->execute();
                }
                $stmt->close();
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Database initialization failed: " . $e->getMessage());
        }
    }

    public static function resetDatabase($conn) {
        try {
            // Drop tables in reverse order due to foreign key constraints
            foreach (array_reverse(array_keys(self::getTableDefinitions())) as $table) {
                $conn->query("DROP TABLE IF EXISTS $table");
            }
            return self::initializeDatabase($conn);
        } catch (Exception $e) {
            throw new Exception("Database reset failed: " . $e->getMessage());
        }
    }
}