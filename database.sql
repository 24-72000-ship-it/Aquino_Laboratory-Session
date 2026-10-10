CREATE DATABASE IF NOT EXISTS barangay_feedback
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barangay_feedback;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    address VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('resident', 'admin') NOT NULL DEFAULT 'resident',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS submissions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_no VARCHAR(32) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('Complaint', 'Suggestion') NOT NULL,
    category ENUM(
        'Road & Infrastructure',
        'Garbage & Sanitation',
        'Noise',
        'Peace & Order',
        'Water & Electricity',
        'Health',
        'Others'
    ) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('Received', 'In Progress', 'Resolved', 'Rejected') NOT NULL DEFAULT 'Received',
    admin_reply TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_submissions_reference (reference_no),
    KEY idx_submissions_user_created (user_id, created_at),
    KEY idx_submissions_status_created (status, created_at),
    KEY idx_submissions_category_type (category, type),
    CONSTRAINT fk_submissions_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS status_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    submission_id INT UNSIGNED NOT NULL,
    status ENUM('Received', 'In Progress', 'Resolved', 'Rejected') NOT NULL,
    remarks TEXT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status_history_submission_time (submission_id, changed_at),
    CONSTRAINT fk_status_history_submission
        FOREIGN KEY (submission_id) REFERENCES submissions (id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (id, full_name, email, address, password, role) VALUES
(1, 'Barangay Admin', 'admin@baryocare.local', 'Barangay Hall', '$2y$10$iRr51MFrRzZA0a0AKa8B6eSZi.vlqQ2O5d10xSDea0EL/65gxh3vG', 'admin'),
(2, 'Maria Santos', 'maria@example.com', 'Purok 1', '$2y$10$Sediq9lDrri5zMwWJS3HdexGbC2gc6Iya/mqQDdxv0keqXCRN2r5O', 'resident'),
(3, 'Jose Reyes', 'jose@example.com', 'Purok 3', '$2y$10$Sediq9lDrri5zMwWJS3HdexGbC2gc6Iya/mqQDdxv0keqXCRN2r5O', 'resident')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), address = VALUES(address), role = VALUES(role);

INSERT INTO submissions (id, reference_no, user_id, type, category, subject, description, is_anonymous, status, admin_reply, created_at) VALUES
(1, CONCAT('BRG-', YEAR(CURDATE()), '-0001'), 2, 'Complaint', 'Road & Infrastructure', 'Streetlight near Purok 1 is not working', 'The streetlight beside the community basketball court has been out for several nights. It is very dark for people walking home.', 0, 'In Progress', 'Thank you for letting us know. Our maintenance team has scheduled an inspection this week.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, CONCAT('BRG-', YEAR(CURDATE()), '-0002'), 3, 'Suggestion', 'Garbage & Sanitation', 'Add another community recycling bin', 'A second recycling bin near the covered court could make it easier to keep plastic bottles out of regular waste.', 0, 'Received', NULL, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, CONCAT('BRG-', YEAR(CURDATE()), '-0003'), 2, 'Complaint', 'Noise', 'Late-night noise in our neighborhood', 'Loud music has continued past quiet hours on several weeknights. Please remind residents about the neighborhood guidelines.', 1, 'Resolved', 'We spoke with the residents concerned and reviewed the quiet-hours guidelines with them. Thank you for raising this respectfully.', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(4, CONCAT('BRG-', YEAR(CURDATE()), '-0004'), 3, 'Complaint', 'Water & Electricity', 'Low water pressure during the morning', 'Water pressure has been low in our area between 6 and 8 in the morning since Monday.', 0, 'In Progress', 'The water service provider has been contacted and is checking the local line.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, CONCAT('BRG-', YEAR(CURDATE()), '-0005'), 2, 'Suggestion', 'Health', 'Request for a weekend wellness activity', 'Could the health center host a monthly Saturday blood pressure and wellness check for residents?', 0, 'Received', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, CONCAT('BRG-', YEAR(CURDATE()), '-0006'), 3, 'Suggestion', 'Road & Infrastructure', 'Paint a pedestrian crossing by the school', 'A visible pedestrian crossing at the elementary school entrance could help families cross more safely.', 0, 'Rejected', 'The roadway is managed by the city engineering office. We have forwarded the request and included their contact information.', DATE_SUB(NOW(), INTERVAL 17 DAY))
ON DUPLICATE KEY UPDATE subject = VALUES(subject);

INSERT INTO status_history (submission_id, status, remarks, changed_at) VALUES
(1, 'Received', 'Submission received by the barangay.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 'In Progress', 'Thank you for letting us know. Our maintenance team has scheduled an inspection this week.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'Received', 'Submission received by the barangay.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 'Received', 'Submission received by the barangay.', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(3, 'In Progress', 'The barangay team has started reviewing the concern.', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 'Resolved', 'We spoke with the residents concerned and reviewed the quiet-hours guidelines with them. Thank you for raising this respectfully.', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(4, 'Received', 'Submission received by the barangay.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 'In Progress', 'The water service provider has been contacted and is checking the local line.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 'Received', 'Submission received by the barangay.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 'Received', 'Submission received by the barangay.', DATE_SUB(NOW(), INTERVAL 17 DAY)),
(6, 'Rejected', 'The roadway is managed by the city engineering office. We have forwarded the request and included their contact information.', DATE_SUB(NOW(), INTERVAL 15 DAY))
ON DUPLICATE KEY UPDATE remarks = VALUES(remarks);
