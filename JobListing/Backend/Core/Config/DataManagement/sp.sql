-- Drop existing procedures if they exist
DROP PROCEDURE IF EXISTS sp_authenticate_user;
DROP PROCEDURE IF EXISTS sp_create_user;
DROP PROCEDURE IF EXISTS sp_get_active_companies_count;
DROP PROCEDURE IF EXISTS sp_get_available_positions_count;
DROP PROCEDURE IF EXISTS sp_get_courses_by_department;
DROP PROCEDURE IF EXISTS sp_get_departments;
DROP PROCEDURE IF EXISTS sp_get_job_details;
DROP PROCEDURE IF EXISTS sp_get_job_listings;
DROP PROCEDURE IF EXISTS sp_get_user_active_applications_count;
DROP PROCEDURE IF EXISTS sp_get_user_applications;
DROP PROCEDURE IF EXISTS sp_submit_application;
DROP PROCEDURE IF EXISTS sp_add_company;
DROP PROCEDURE IF EXISTS sp_update_company;
DROP PROCEDURE IF EXISTS sp_get_company_by_id;
DROP PROCEDURE IF EXISTS sp_get_companies;
DROP PROCEDURE IF EXISTS sp_add_job_listing;
DROP PROCEDURE IF EXISTS sp_update_job_listing;
DROP PROCEDURE IF EXISTS sp_update_application_status;
DROP PROCEDURE IF EXISTS sp_get_user_by_email;
DROP PROCEDURE IF EXISTS sp_update_user_password;
DROP PROCEDURE IF EXISTS sp_admin_add_company;
DROP PROCEDURE IF EXISTS sp_admin_update_company;
DROP PROCEDURE IF EXISTS sp_admin_update_application_status;
DROP PROCEDURE IF EXISTS sp_admin_get_dashboard_stats;
DROP PROCEDURE IF EXISTS sp_admin_force_password_reset;
DROP PROCEDURE IF EXISTS sp_clear_password_reset_flag;
DROP PROCEDURE IF EXISTS sp_add_resume;
DROP PROCEDURE IF EXISTS sp_update_resume;
DROP PROCEDURE IF EXISTS sp_delete_resume;
DROP PROCEDURE IF EXISTS sp_get_resume;
DROP PROCEDURE IF EXISTS sp_update_admin_status;
DROP PROCEDURE IF EXISTS sp_update_user_status;
DROP PROCEDURE IF EXISTS sp_check_duplicate_company;
DROP PROCEDURE IF EXISTS sp_check_duplicate_user;
DROP PROCEDURE IF EXISTS sp_check_duplicate_job;
DROP PROCEDURE IF EXISTS sp_check_duplicate_admin;

-- Authentication and User Management
DELIMITER $$

CREATE PROCEDURE sp_authenticate_user(IN p_email VARCHAR(255))
BEGIN
    SELECT u.id, u.srcode, u.firstname, u.lastname, u.email, u.password, 
           u.usertype, u.status, u.course_id, u.section, u.is_super_admin
    FROM users u 
    WHERE u.email = p_email 
    AND u.status = 'active' 
    LIMIT 1;
END$$

CREATE PROCEDURE sp_create_user(
    IN p_srcode VARCHAR(9),
    IN p_firstname VARCHAR(50),
    IN p_lastname VARCHAR(50),
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_course_id INT,
    IN p_section VARCHAR(20),
    IN p_usertype ENUM('admin', 'user', 'none'),
    IN p_status ENUM('active', 'inactive')
)
BEGIN
    INSERT INTO users (
        srcode, firstname, lastname, email, password, 
        course_id, section, usertype, status
    ) VALUES (
        p_srcode, p_firstname, p_lastname, p_email, p_password,
        p_course_id, p_section, p_usertype, p_status
    );
    SELECT LAST_INSERT_ID() as user_id;
END$$

CREATE PROCEDURE sp_get_user_by_email(IN p_email VARCHAR(255))
BEGIN
    SELECT * FROM users WHERE email = p_email LIMIT 1;
END$$

CREATE PROCEDURE sp_update_user_password(
    IN p_user_id INT,
    IN p_password VARCHAR(255),
    IN p_force_reset TINYINT(1)
)
BEGIN
    UPDATE users 
    SET password = p_password, 
        force_reset = p_force_reset,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_user_id;
END$$

CREATE PROCEDURE sp_update_user_status(
    IN p_user_id INT,
    IN p_status ENUM('active', 'inactive')
)
BEGIN
    DECLARE current_status VARCHAR(10);
    DECLARE user_type VARCHAR(10);
    
    -- Get current status and user type
    SELECT status, usertype INTO current_status, user_type 
    FROM users 
    WHERE id = p_user_id;
    
    -- Verify user exists
    IF current_status IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User not found';
    END IF;
    
    -- Prevent admin management through this procedure
    IF user_type = 'admin' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify admin status through this procedure';
    END IF;
    
    -- Check if trying to change to the same status
    IF current_status = p_status THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User account is already in the requested status';
    END IF;

    -- Update the status and log the change
    INSERT INTO status_change_logs (
        user_id, 
        old_status, 
        new_status, 
        changed_by
    ) VALUES (
        p_user_id, 
        current_status, 
        p_status,
        @admin_id
    );

    -- Update the status
    UPDATE users 
    SET status = p_status,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_user_id 
    AND usertype = 'user';
END$$

CREATE PROCEDURE sp_check_duplicate_company(
    IN p_name VARCHAR(100),
    IN p_id INT
)
BEGIN
    DECLARE duplicate_exists INT;
    
    IF p_id IS NULL THEN
        -- Check for duplicates when adding a new company
        SELECT COUNT(*) INTO duplicate_exists
        FROM companies
        WHERE LOWER(name) = LOWER(p_name);
    ELSE
        -- Check for duplicates when updating an existing company
        SELECT COUNT(*) INTO duplicate_exists
        FROM companies
        WHERE LOWER(name) = LOWER(p_name)
        AND id != p_id;
    END IF;
    
    SELECT 
        CASE 
            WHEN duplicate_exists > 0 THEN TRUE
            ELSE FALSE
        END as duplicate,
        CASE 
            WHEN duplicate_exists > 0 THEN 'A company with this name already exists'
            ELSE NULL
        END as message;
END$$

CREATE PROCEDURE sp_check_duplicate_user(
    IN p_srcode VARCHAR(9),
    IN p_email VARCHAR(255),
    IN p_id INT
)
BEGIN
    DECLARE srcode_exists INT;
    DECLARE email_exists INT;
    
    IF p_id IS NULL THEN
        -- Check for duplicates when adding a new user
        SELECT COUNT(*) INTO srcode_exists 
        FROM users 
        WHERE srcode = p_srcode;
        
        SELECT COUNT(*) INTO email_exists 
        FROM users 
        WHERE LOWER(email) = LOWER(p_email);
    ELSE
        -- Check for duplicates when updating an existing user
        SELECT COUNT(*) INTO srcode_exists 
        FROM users 
        WHERE srcode = p_srcode 
        AND id != p_id;
        
        SELECT COUNT(*) INTO email_exists 
        FROM users 
        WHERE LOWER(email) = LOWER(p_email) 
        AND id != p_id;
    END IF;
    
    SELECT 
        CASE 
            WHEN srcode_exists > 0 OR email_exists > 0 THEN TRUE
            ELSE FALSE
        END as duplicate,
        CASE 
            WHEN srcode_exists > 0 AND email_exists > 0 THEN 'Both SR Code and Email are already in use'
            WHEN srcode_exists > 0 THEN 'SR Code is already in use'
            WHEN email_exists > 0 THEN 'Email is already in use'
            ELSE NULL
        END as message;
END$$

CREATE PROCEDURE sp_check_duplicate_job(
    IN p_title VARCHAR(100),
    IN p_company_id INT,
    IN p_id INT
)
BEGIN
    DECLARE duplicate_exists INT;
    
    IF p_id IS NULL THEN
        -- Check for duplicates when adding a new job
        SELECT COUNT(*) INTO duplicate_exists
        FROM job_listings
        WHERE LOWER(title) = LOWER(p_title)
        AND company_id = p_company_id;
    ELSE
        -- Check for duplicates when updating an existing job
        SELECT COUNT(*) INTO duplicate_exists
        FROM job_listings
        WHERE LOWER(title) = LOWER(p_title)
        AND company_id = p_company_id
        AND id != p_id;
    END IF;
    
    SELECT 
        CASE 
            WHEN duplicate_exists > 0 THEN TRUE
            ELSE FALSE
        END as duplicate,
        CASE 
            WHEN duplicate_exists > 0 THEN 'A job with this title already exists for this company'
            ELSE NULL
        END as message;
END$$

CREATE PROCEDURE sp_check_duplicate_admin(
    IN p_srcode VARCHAR(9),
    IN p_email VARCHAR(255),
    IN p_id INT
)
BEGIN
    DECLARE srcode_exists INT;
    DECLARE email_exists INT;
    DECLARE srcode_exists_user INT;
    DECLARE email_exists_user INT;
    
    IF p_id IS NULL THEN
        -- Check for duplicates in administrators table when adding
        SELECT COUNT(*) INTO srcode_exists 
        FROM administrators 
        WHERE srcode = p_srcode;
        
        SELECT COUNT(*) INTO email_exists 
        FROM administrators 
        WHERE LOWER(email) = LOWER(p_email);
        
        -- Check for duplicates in users table
        SELECT COUNT(*) INTO srcode_exists_user 
        FROM users 
        WHERE srcode = p_srcode;
        
        SELECT COUNT(*) INTO email_exists_user 
        FROM users 
        WHERE LOWER(email) = LOWER(p_email);
    ELSE
        -- Check for duplicates in administrators table when updating
        SELECT COUNT(*) INTO srcode_exists 
        FROM administrators 
        WHERE srcode = p_srcode 
        AND id != p_id;
        
        SELECT COUNT(*) INTO email_exists 
        FROM administrators 
        WHERE LOWER(email) = LOWER(p_email) 
        AND id != p_id;
        
        -- Check for duplicates in users table
        SELECT COUNT(*) INTO srcode_exists_user 
        FROM users 
        WHERE srcode = p_srcode;
        
        SELECT COUNT(*) INTO email_exists_user 
        FROM users 
        WHERE LOWER(email) = LOWER(p_email);
    END IF;
    
    SELECT 
        CASE 
            WHEN srcode_exists > 0 OR email_exists > 0 OR
                 srcode_exists_user > 0 OR email_exists_user > 0 THEN TRUE
            ELSE FALSE
        END as duplicate,
        CASE 
            WHEN (srcode_exists > 0 OR srcode_exists_user > 0) AND 
                 (email_exists > 0 OR email_exists_user > 0) THEN 'Both SR Code and Email are already in use'
            WHEN srcode_exists > 0 OR srcode_exists_user > 0 THEN 'SR Code is already in use'
            WHEN email_exists > 0 OR email_exists_user > 0 THEN 'Email is already in use'
            ELSE NULL
        END as message;
END$$

-- Company Management
CREATE PROCEDURE sp_admin_add_company(
    IN p_name VARCHAR(100),
    IN p_address TEXT,
    IN p_contact_person VARCHAR(100),
    IN p_contact_email VARCHAR(255),
    IN p_contact_phone VARCHAR(20),
    IN p_website VARCHAR(255),
    IN p_description TEXT,
    IN p_logo_data MEDIUMBLOB,
    IN p_logo_type VARCHAR(50)
)
BEGIN
    INSERT INTO companies (
        name, address, contact_person, contact_email,
        contact_phone, website, description, logo_data, logo_type
    ) VALUES (
        p_name, p_address, p_contact_person, p_contact_email,
        p_contact_phone, p_website, p_description, p_logo_data, p_logo_type
    );
    SELECT LAST_INSERT_ID() as company_id;
END$$

CREATE PROCEDURE sp_admin_update_company(
    IN p_id INT,
    IN p_name VARCHAR(100),
    IN p_address TEXT,
    IN p_contact_person VARCHAR(100),
    IN p_contact_email VARCHAR(255),
    IN p_contact_phone VARCHAR(20),
    IN p_website VARCHAR(255),
    IN p_description TEXT,
    IN p_logo_data MEDIUMBLOB,
    IN p_logo_type VARCHAR(50),
    IN p_status ENUM('active', 'inactive')
)
BEGIN
    IF p_logo_data IS NULL AND p_logo_type IS NULL THEN
        -- Update without changing logo
        UPDATE companies 
        SET name = p_name,
            address = p_address,
            contact_person = p_contact_person,
            contact_email = p_contact_email,
            contact_phone = p_contact_phone,
            website = p_website,
            description = p_description,
            status = p_status,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_id;
    ELSE
        -- Update including new logo
        UPDATE companies 
        SET name = p_name,
            address = p_address,
            contact_person = p_contact_person,
            contact_email = p_contact_email,
            contact_phone = p_contact_phone,
            website = p_website,
            description = p_description,
            status = p_status,
            logo_data = p_logo_data,
            logo_type = p_logo_type,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_id;
    END IF;
END$$

CREATE PROCEDURE sp_get_company_by_id(IN p_id INT)
BEGIN
    SELECT * FROM companies WHERE id = p_id;
END$$

CREATE PROCEDURE sp_get_companies()
BEGIN
    SELECT c.*,
           COALESCE((
               SELECT COUNT(*)
               FROM job_listings jl
               WHERE jl.company_id = c.id
               AND jl.status = 'open'
               AND (jl.expires_at IS NULL OR jl.expires_at >= CURDATE())
           ), 0) as open_positions,
           COALESCE((
               SELECT COUNT(*)
               FROM job_listings jl
               WHERE jl.company_id = c.id
           ), 0) as total_positions
    FROM companies c
    ORDER BY c.name ASC;
END$$

CREATE PROCEDURE sp_get_active_companies_count()
BEGIN
    SELECT COUNT(*) as count 
    FROM companies 
    WHERE status = 'active';
END$$

-- Job Listings Management
CREATE PROCEDURE sp_add_job_listing(
    IN p_company_id INT,
    IN p_title VARCHAR(100),
    IN p_description TEXT,
    IN p_requirements TEXT,
    IN p_responsibilities TEXT,
    IN p_qualifications TEXT,
    IN p_benefits TEXT,
    IN p_salary_range VARCHAR(100),
    IN p_work_mode ENUM('onsite', 'hybrid', 'remote'),
    IN p_job_type ENUM('full-time', 'part-time', 'internship'),
    IN p_slots INT,
    IN p_status ENUM('open', 'closed'),
    IN p_expires_at DATE
)
BEGIN
    INSERT INTO job_listings (
        company_id, title, description, requirements,
        responsibilities, qualifications, benefits, salary_range,
        work_mode, job_type, slots, status, expires_at
    ) VALUES (
        p_company_id, p_title, p_description, p_requirements,
        p_responsibilities, p_qualifications, p_benefits, p_salary_range,
        p_work_mode, p_job_type, p_slots, p_status, p_expires_at
    );
    SELECT LAST_INSERT_ID() as job_id;
END$$

CREATE PROCEDURE sp_update_job_listing(
    IN p_id INT,
    IN p_title VARCHAR(100),
    IN p_description TEXT,
    IN p_requirements TEXT,
    IN p_responsibilities TEXT,
    IN p_qualifications TEXT,
    IN p_benefits TEXT,
    IN p_salary_range VARCHAR(100),
    IN p_work_mode ENUM('onsite', 'hybrid', 'remote'),
    IN p_job_type ENUM('full-time', 'part-time', 'internship'),
    IN p_slots INT,
    IN p_status ENUM('open', 'closed'),
    IN p_expires_at DATE
)
BEGIN
    UPDATE job_listings 
    SET title = p_title,
        description = p_description,
        requirements = p_requirements,
        responsibilities = p_responsibilities,
        qualifications = p_qualifications,
        benefits = p_benefits,
        salary_range = p_salary_range,
        work_mode = p_work_mode,
        job_type = p_job_type,
        slots = p_slots,
        status = p_status,
        expires_at = p_expires_at,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_id;
END$$

CREATE PROCEDURE sp_get_job_details(IN p_job_id INT)
BEGIN
    SELECT jl.*, c.* 
    FROM job_listings jl 
    JOIN companies c ON jl.company_id = c.id 
    WHERE jl.id = p_job_id;
END$$

CREATE PROCEDURE sp_get_job_listings(IN p_admin BOOLEAN)
BEGIN
    SELECT jl.*, 
           c.name as company_name, 
           c.id as company_id,
           c.logo_data, 
           c.logo_type,
           COALESCE((
               SELECT COUNT(*)
               FROM job_applications ja
               WHERE ja.job_id = jl.id
           ), 0) as application_count
    FROM job_listings jl 
    LEFT JOIN companies c ON jl.company_id = c.id 
    WHERE (p_admin = TRUE) OR 
          (jl.status = 'open' AND c.status = 'active' AND (jl.expires_at IS NULL OR jl.expires_at >= CURDATE()))
    ORDER BY jl.created_at DESC;
END$$

CREATE PROCEDURE sp_get_available_positions_count()
BEGIN
    SELECT COUNT(*) as count 
    FROM job_listings 
    WHERE status = 'open' 
    AND (expires_at IS NULL OR expires_at >= CURDATE());
END$$

-- Application Management
CREATE PROCEDURE sp_submit_application(
    IN p_user_id INT,
    IN p_job_id INT
)
BEGIN
    -- First check if the student has a resume
    DECLARE has_resume INT;
    SELECT COUNT(*) INTO has_resume FROM student_resumes WHERE user_id = p_user_id;
    
    IF has_resume = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Please upload your resume before applying';
    END IF;

    -- Submit the application
    INSERT INTO job_applications (
        user_id, job_id, status
    ) VALUES (
        p_user_id, p_job_id, 'pending'
    );
    SELECT LAST_INSERT_ID() as application_id;
END$$

CREATE PROCEDURE sp_admin_update_application_status(
    IN p_application_id INT,
    IN p_status ENUM('pending', 'reviewing', 'interview', 'accepted', 'rejected')
)
BEGIN
    UPDATE job_applications 
    SET status = p_status,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_application_id;
END$$

CREATE PROCEDURE sp_get_user_applications(IN p_user_id INT)
BEGIN
    SELECT ja.*, 
           jl.title, 
           c.name as company_name, 
           c.id as company_id,
           sr.id as resume_id,
           sr.resume_name,
           sr.resume_type
    FROM job_applications ja 
    JOIN job_listings jl ON ja.job_id = jl.id 
    JOIN companies c ON jl.company_id = c.id 
    LEFT JOIN student_resumes sr ON sr.user_id = ja.user_id
    WHERE ja.user_id = p_user_id
    ORDER BY ja.created_at DESC;
END$$

CREATE PROCEDURE sp_get_user_active_applications_count(IN p_user_id INT)
BEGIN
    SELECT COUNT(*) as count 
    FROM job_applications 
    WHERE user_id = p_user_id 
    AND status != 'rejected';
END$$

-- Resume Management
CREATE PROCEDURE sp_add_resume(
    IN p_user_id INT,
    IN p_resume_data LONGBLOB,
    IN p_resume_name VARCHAR(255),
    IN p_resume_type VARCHAR(100)
)
BEGIN
    INSERT INTO student_resumes (
        user_id, resume_data, resume_name, resume_type
    ) VALUES (
        p_user_id, p_resume_data, p_resume_name, p_resume_type
    );
    SELECT LAST_INSERT_ID() as resume_id;
END$$

CREATE PROCEDURE sp_update_resume(
    IN p_user_id INT,
    IN p_resume_data LONGBLOB,
    IN p_resume_name VARCHAR(255),
    IN p_resume_type VARCHAR(100)
)
BEGIN
    UPDATE student_resumes 
    SET resume_data = p_resume_data,
        resume_name = p_resume_name,
        resume_type = p_resume_type,
        updated_at = CURRENT_TIMESTAMP
    WHERE user_id = p_user_id;
END$$

CREATE PROCEDURE sp_delete_resume(
    IN p_user_id INT
)
BEGIN
    DELETE FROM student_resumes 
    WHERE user_id = p_user_id;
END$$

CREATE PROCEDURE sp_get_resume(
    IN p_user_id INT
)
BEGIN
    SELECT resume_data, resume_name, resume_type, uploaded_at, updated_at
    FROM student_resumes 
    WHERE user_id = p_user_id;
END$$

-- Academic Data Management
CREATE PROCEDURE sp_get_departments()
BEGIN
    SELECT * FROM departments ORDER BY name;
END$$

CREATE PROCEDURE sp_get_courses_by_department(IN p_department_id INT)
BEGIN
    SELECT * FROM courses WHERE department_id = p_department_id ORDER BY name;
END$$

-- Dashboard
CREATE PROCEDURE sp_admin_get_dashboard_stats()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM job_listings) as total_jobs,
        (SELECT COUNT(*) FROM companies) as total_companies,
        (SELECT COUNT(*) FROM job_applications) as total_applications,
        (SELECT COUNT(*) FROM users WHERE usertype = 'user') as total_users;
END$$

-- Password Reset Management
CREATE PROCEDURE sp_admin_force_password_reset(
    IN p_user_id INT
)
BEGIN
    UPDATE users 
    SET force_reset = 1,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_user_id;
END$$

CREATE PROCEDURE sp_clear_password_reset_flag(
    IN p_user_id INT
)
BEGIN
    UPDATE users 
    SET force_reset = 0,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_user_id;
END$$

-- Admin status update
CREATE PROCEDURE sp_update_admin_status(
    IN p_admin_id INT,
    IN p_status ENUM('active', 'inactive')
)
BEGIN
    DECLARE active_admins INT;
    DECLARE current_status VARCHAR(10);
    
    -- Get current status
    SELECT status INTO current_status 
    FROM administrators 
    WHERE id = p_admin_id;
    
    -- Check if trying to change to the same status
    IF current_status = p_status THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Administrator account is already in the requested status';
    END IF;

    -- If deactivating, check if it's the last active admin
    IF p_status = 'inactive' THEN
        SELECT COUNT(*) INTO active_admins
        FROM administrators
        WHERE status = 'active' AND id != p_admin_id;
        
        IF active_admins = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot deactivate the last active administrator';
        END IF;
    END IF;

    -- Update the status
    UPDATE administrators
    SET status = p_status,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_admin_id;
END$$

DELIMITER ;