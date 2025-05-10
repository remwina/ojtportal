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
DROP PROCEDURE IF EXISTS sp_admin_get_dashboard_stats;
DROP PROCEDURE IF EXISTS sp_admin_force_password_reset;
DROP PROCEDURE IF EXISTS sp_clear_password_reset_flag;

-- Authentication and User Management
DELIMITER $$
CREATE PROCEDURE sp_authenticate_user(IN p_email VARCHAR(255))
BEGIN
    SELECT u.id, u.srcode, u.firstname, u.lastname, u.email, u.password, 
           u.usertype, u.status, u.course_id, u.section 
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
    SELECT * FROM companies ORDER BY name;
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
    IN p_status ENUM('open', 'closed', 'draft'),
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
    IN p_status ENUM('open', 'closed', 'draft'),
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

CREATE PROCEDURE sp_get_job_listings()
BEGIN
    SELECT jl.*, c.name as company_name, c.id as company_id,
           c.logo_data, c.logo_type
    FROM job_listings jl 
    JOIN companies c ON jl.company_id = c.id 
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
    IN p_job_id INT,
    IN p_resume_path VARCHAR(255),
    IN p_cover_letter TEXT
)
BEGIN
    INSERT INTO job_applications (
        user_id, job_id, resume_path, cover_letter
    ) VALUES (
        p_user_id, p_job_id, p_resume_path, p_cover_letter
    );
    SELECT LAST_INSERT_ID() as application_id;
END$$

CREATE PROCEDURE sp_update_application_status(
    IN p_application_id INT,
    IN p_status ENUM('pending', 'shortlisted', 'interviewed', 'offered', 'accepted', 'rejected')
)
BEGIN
    UPDATE job_applications 
    SET status = p_status,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_application_id;
END$$

CREATE PROCEDURE sp_get_user_applications(IN p_user_id INT)
BEGIN
    SELECT ja.*, jl.title, c.name as company_name, c.id as company_id
    FROM job_applications ja 
    JOIN job_listings jl ON ja.job_id = jl.id 
    JOIN companies c ON jl.company_id = c.id 
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

DELIMITER ;