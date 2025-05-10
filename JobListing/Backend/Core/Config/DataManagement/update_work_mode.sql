-- Drop the location column and add work_mode column
ALTER TABLE job_listings 
    DROP COLUMN IF EXISTS location,
    ADD COLUMN work_mode ENUM('onsite', 'hybrid', 'remote') NOT NULL DEFAULT 'onsite' AFTER requirements;

-- Update stored procedures
DROP PROCEDURE IF EXISTS sp_add_job_listing;
DELIMITER $$
CREATE PROCEDURE sp_add_job_listing(
    IN p_company_id INT,
    IN p_title VARCHAR(100),
    IN p_description TEXT,
    IN p_requirements TEXT,
    IN p_work_mode ENUM('onsite', 'hybrid', 'remote'),
    IN p_job_type ENUM('full-time', 'part-time', 'internship'),
    IN p_slots INT,
    IN p_status ENUM('open', 'closed', 'draft'),
    IN p_expires_at DATE
)
BEGIN
    INSERT INTO job_listings (
        company_id, title, description, requirements,
        work_mode, job_type, slots, status, expires_at
    ) VALUES (
        p_company_id, p_title, p_description, p_requirements,
        p_work_mode, p_job_type, p_slots, p_status, p_expires_at
    );
    SELECT LAST_INSERT_ID() as job_id;
END $$

DROP PROCEDURE IF EXISTS sp_update_job_listing;
CREATE PROCEDURE sp_update_job_listing(
    IN p_id INT,
    IN p_title VARCHAR(100),
    IN p_description TEXT,
    IN p_requirements TEXT,
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
        work_mode = p_work_mode,
        job_type = p_job_type,
        slots = p_slots,
        status = p_status,
        expires_at = p_expires_at,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_id;
END $$
DELIMITER ;
