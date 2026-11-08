-- ============================================================
-- MPDC LAND PROPERTY RECORDS AND GEOTAGGING SYSTEM
-- Database: mpdc_system
-- Complete database schema for PHP + MySQL / XAMPP
-- ============================================================

CREATE DATABASE IF NOT EXISTS mpdc_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE mpdc_system;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ADMINS
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL DEFAULT 'MPDC Administrator',
    email VARCHAR(150) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_username (username),
    UNIQUE KEY uq_admin_email (email),
    KEY idx_admin_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. BARANGAYS
-- ============================================================
CREATE TABLE IF NOT EXISTS barangays (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(30) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_barangay_name (name),
    UNIQUE KEY uq_barangay_code (code),
    KEY idx_barangay_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. PROPERTIES
-- Main property record.
-- Official/title data and geotagged/calculated data are kept
-- separate so field data does not silently overwrite records.
-- ============================================================
CREATE TABLE IF NOT EXISTS properties (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_code VARCHAR(80) NOT NULL,
    owner_name VARCHAR(255) NULL,
    property_type VARCHAR(100) NOT NULL DEFAULT 'Private',
    barangay_id BIGINT UNSIGNED NULL,
    municipality VARCHAR(120) NOT NULL DEFAULT 'Unisan',
    province VARCHAR(120) NOT NULL DEFAULT 'Quezon',
    lot_number VARCHAR(100) NULL,
    block_number VARCHAR(100) NULL,

    -- Official / recorded information
    recorded_area_sqm DECIMAL(15,2) NULL,
    technical_description TEXT NULL,
    survey_plan_number VARCHAR(150) NULL,
    tax_declaration_number VARCHAR(150) NULL,
    date_acquired DATE NULL,

    property_status VARCHAR(80) NOT NULL DEFAULT 'Active',
    verification_status ENUM(
        'Not Yet Verified',
        'For Verification',
        'Field Verified',
        'Verified',
        'Needs Correction'
    ) NOT NULL DEFAULT 'Not Yet Verified',

    -- Main geotag/reference point
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    coordinate_reference VARCHAR(100) NULL,
    coordinate_source VARCHAR(150) NULL,

    -- Calculated/field information
    calculated_area_sqm DECIMAL(15,2) NULL,
    area_difference_sqm DECIMAL(15,2) NULL,
    area_difference_percent DECIMAL(10,4) NULL,
    geotag_status ENUM(
        'Not Geotagged',
        'Incomplete',
        'Matching',
        'Needs Verification'
    ) NOT NULL DEFAULT 'Not Geotagged',

    remarks TEXT NULL,

    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_property_code (property_code),
    KEY idx_property_barangay (barangay_id),
    KEY idx_property_owner (owner_name),
    KEY idx_property_lot (lot_number),
    KEY idx_property_tax_decl (tax_declaration_number),
    KEY idx_property_status (property_status),
    KEY idx_property_verification (verification_status),
    KEY idx_property_geotag (geotag_status),
    KEY idx_property_coordinates (latitude, longitude),

    CONSTRAINT fk_property_barangay
        FOREIGN KEY (barangay_id) REFERENCES barangays(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_property_created_by
        FOREIGN KEY (created_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_property_updated_by
        FOREIGN KEY (updated_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_property_latitude
        CHECK (latitude IS NULL OR (latitude >= -90 AND latitude <= 90)),

    CONSTRAINT chk_property_longitude
        CHECK (longitude IS NULL OR (longitude >= -180 AND longitude <= 180)),

    CONSTRAINT chk_property_area
        CHECK (recorded_area_sqm IS NULL OR recorded_area_sqm > 0),

    CONSTRAINT chk_property_calculated_area
        CHECK (calculated_area_sqm IS NULL OR calculated_area_sqm > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. LAND TITLES
-- One property may have a title record. Title number is unique.
-- ============================================================
CREATE TABLE IF NOT EXISTS land_titles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT UNSIGNED NOT NULL,
    title_number VARCHAR(150) NOT NULL,
    title_type VARCHAR(80) NULL,
    registered_owner VARCHAR(255) NULL,
    title_area_sqm DECIMAL(15,2) NULL,
    date_registered DATE NULL,
    registry_office VARCHAR(255) NULL,
    technical_description TEXT NULL,
    document_reference VARCHAR(255) NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_title_number (title_number),
    UNIQUE KEY uq_land_title_property (property_id),
    KEY idx_title_owner (registered_owner),
    KEY idx_title_created (created_at),

    CONSTRAINT fk_title_property
        FOREIGN KEY (property_id) REFERENCES properties(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_title_created_by
        FOREIGN KEY (created_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_title_updated_by
        FOREIGN KEY (updated_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_title_area
        CHECK (title_area_sqm IS NULL OR title_area_sqm > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. BOUNDARY MARKERS / MOHONS
-- ============================================================
CREATE TABLE IF NOT EXISTS boundary_markers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT UNSIGNED NOT NULL,
    marker_code VARCHAR(100) NOT NULL,
    point_number INT UNSIGNED NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    coordinate_reference VARCHAR(100) NULL,
    coordinate_source VARCHAR(150) NULL,
    description VARCHAR(255) NULL,
    marker_condition VARCHAR(100) NULL,
    date_recorded DATE NULL,
    photo_path VARCHAR(500) NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_marker_code (marker_code),
    UNIQUE KEY uq_property_point (property_id, point_number),
    KEY idx_marker_property (property_id),
    KEY idx_marker_coordinates (latitude, longitude),

    CONSTRAINT fk_marker_property
        FOREIGN KEY (property_id) REFERENCES properties(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_marker_created_by
        FOREIGN KEY (created_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_marker_latitude
        CHECK (latitude >= -90 AND latitude <= 90),

    CONSTRAINT chk_marker_longitude
        CHECK (longitude >= -180 AND longitude <= 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. PROPERTY BOUNDARIES
-- GeoJSON / polygon representation.
-- ============================================================
CREATE TABLE IF NOT EXISTS property_boundaries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT UNSIGNED NOT NULL,
    boundary_name VARCHAR(150) NOT NULL DEFAULT 'Main Boundary',
    geometry_type VARCHAR(50) NOT NULL DEFAULT 'Polygon',
    geojson LONGTEXT NULL,
    coordinate_reference VARCHAR(100) NULL,
    source VARCHAR(150) NULL,
    calculated_area_sqm DECIMAL(15,2) NULL,
    point_count INT UNSIGNED NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verified_at DATETIME NULL,
    verified_by BIGINT UNSIGNED NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_boundary_property (property_id),
    KEY idx_boundary_verified (is_verified),

    CONSTRAINT fk_boundary_property
        FOREIGN KEY (property_id) REFERENCES properties(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_boundary_verified_by
        FOREIGN KEY (verified_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_boundary_created_by
        FOREIGN KEY (created_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_boundary_updated_by
        FOREIGN KEY (updated_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_boundary_area
        CHECK (calculated_area_sqm IS NULL OR calculated_area_sqm > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. PROPERTY DOCUMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS property_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(150) NULL,
    file_size BIGINT UNSIGNED NULL,
    description TEXT NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_document_property (property_id),
    KEY idx_document_type (document_type),

    CONSTRAINT fk_document_property
        FOREIGN KEY (property_id) REFERENCES properties(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_document_admin
        FOREIGN KEY (uploaded_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. PROPERTY PHOTOS
-- ============================================================
CREATE TABLE IF NOT EXISTS property_photos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT UNSIGNED NOT NULL,
    photo_type ENUM(
        'Property',
        'Boundary Marker',
        'Site',
        'Document',
        'Other'
    ) NOT NULL DEFAULT 'Property',
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NULL,
    file_size BIGINT UNSIGNED NULL,
    description TEXT NULL,
    taken_at DATETIME NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_photo_property (property_id),
    KEY idx_photo_type (photo_type),

    CONSTRAINT fk_photo_property
        FOREIGN KEY (property_id) REFERENCES properties(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_photo_admin
        FOREIGN KEY (uploaded_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. VERIFICATION RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS verification_records (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id BIGINT UNSIGNED NOT NULL,
    verification_status ENUM(
        'Not Yet Verified',
        'For Verification',
        'Field Verified',
        'Verified',
        'Needs Correction'
    ) NOT NULL,
    verification_date DATE NULL,
    verified_by BIGINT UNSIGNED NULL,
    field_notes TEXT NULL,
    coordinate_source VARCHAR(150) NULL,
    supporting_document_id BIGINT UNSIGNED NULL,
    remarks TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_verification_property (property_id),
    KEY idx_verification_status (verification_status),
    KEY idx_verification_date (verification_date),

    CONSTRAINT fk_verification_property
        FOREIGN KEY (property_id) REFERENCES properties(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_verification_admin
        FOREIGN KEY (verified_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_verification_document
        FOREIGN KEY (supporting_document_id) REFERENCES property_documents(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. GOVERNMENT PROPERTIES
-- ============================================================
CREATE TABLE IF NOT EXISTS government_properties (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_code VARCHAR(80) NOT NULL,
    property_name VARCHAR(255) NOT NULL,
    property_type VARCHAR(120) NULL,
    location VARCHAR(255) NULL,
    barangay_id BIGINT UNSIGNED NULL,
    lot_number VARCHAR(100) NULL,
    title_number VARCHAR(150) NULL,
    area_sqm DECIMAL(15,2) NULL,
    current_use VARCHAR(255) NULL,
    responsible_office VARCHAR(255) NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    boundary_geojson LONGTEXT NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Active',
    document_reference VARCHAR(255) NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_gov_property_code (property_code),
    KEY idx_gov_barangay (barangay_id),
    KEY idx_gov_title (title_number),
    KEY idx_gov_coordinates (W, S),

    CONSTRAINT fk_gov_barangay
        FOREIGN KEY (barangay_id) REFERENCES barangays(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_gov_created_by
        FOREIGN KEY (created_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_gov_updated_by
        FOREIGN KEY (updated_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_gov_latitude
        CHECK (latitude IS NULL OR (latitude >= -90 AND latitude <= 90)),

    CONSTRAINT chk_gov_longitude
        CHECK (longitude IS NULL OR (longitude >= -180 AND longitude <= 180)),

    CONSTRAINT chk_gov_area
        CHECK (area_sqm IS NULL OR area_sqm > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. ACTIVITY LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(100) NULL,
    record_type VARCHAR(100) NULL,
    record_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_activity_admin (admin_id),
    KEY idx_activity_action (action),
    KEY idx_activity_module (module),
    KEY idx_activity_created (created_at),

    CONSTRAINT fk_activity_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. CHATBOT LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS chatbot_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NULL,
    user_message TEXT NOT NULL,
    assistant_response TEXT NOT NULL,
    related_property_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_chat_admin (admin_id),
    KEY idx_chat_property (related_property_id),
    KEY idx_chat_created (created_at),

    CONSTRAINT fk_chat_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_chat_property
        FOREIGN KEY (related_property_id) REFERENCES properties(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. SYSTEM SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS system_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    setting_group VARCHAR(100) NOT NULL DEFAULT 'general',
    description VARCHAR(255) NULL,
    updated_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_setting_key (setting_key),
    KEY idx_setting_group (setting_group),

    CONSTRAINT fk_setting_admin
        FOREIGN KEY (updated_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT SETTINGS
-- Existing settings are preserved.
-- ============================================================
INSERT INTO system_settings
    (setting_key, setting_value, setting_group, description)
VALUES
    ('system_name', 'MPDC Land Property Records and Geotagging System', 'general', 'System name'),
    ('office_name', 'Municipal Planning and Development Coordinator (MPDC) Office', 'general', 'Office name'),
    ('municipality', 'Unisan', 'general', 'Municipality'),
    ('province', 'Quezon', 'general', 'Province'),
    ('default_map_zoom', '14', 'mapping', 'Default Leaflet map zoom'),
    ('area_tolerance_percent', '5.00', 'mapping', 'Configurable area comparison tolerance percentage')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    description = VALUES(description);

-- ============================================================
-- SAMPLE BARANGAY DATA
-- Only inserts if the name does not already exist.
-- Add/complete the municipality's official barangay list as needed.
-- ============================================================
INSERT IGNORE INTO barangays (name, code) VALUES
    ('Balanacan', 'BAL'),
    ('Ibabang Kalilayan', 'IBK');

-- ============================================================
-- OPTIONAL DEVELOPMENT ADMIN
-- IMPORTANT:
-- Replace the password hash with a hash generated by PHP:
-- password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT)
--
-- This schema intentionally does NOT insert a plaintext password.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- USEFUL CHECKS AFTER IMPORT
-- ============================================================
-- SELECT COUNT(*) AS admin_count FROM admins;
-- SELECT COUNT(*) AS property_count FROM properties;
-- SELECT COUNT(*) AS title_count FROM land_titles;
-- SELECT COUNT(*) AS marker_count FROM boundary_markers;
-- SELECT COUNT(*) AS boundary_count FROM property_boundaries;
-- SELECT COUNT(*) AS verification_count FROM verification_records;
-- SHOW TABLES;
