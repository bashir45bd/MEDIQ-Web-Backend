-- ==============================================================================
-- MEDIQ Database Migration: 001_create_all_tables.sql
-- Production-Ready Normalized Schema (MySQL 8+ / MariaDB 10.4+)
-- Charset: utf8mb4, Collation: utf8mb4_unicode_ci, Storage: InnoDB
-- Timezone: Asia/Dhaka
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. Users Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` ENUM('PATIENT', 'DOCTOR', 'PLATFORM_ADMIN') NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('ACTIVE', 'SUSPENDED', 'DEACTIVATED') NOT NULL DEFAULT 'ACTIVE',
  `profile_photo` VARCHAR(255) NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `phone_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_phone` (`phone`),
  UNIQUE KEY `uk_users_email` (`email`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Doctors Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `doctors`;
CREATE TABLE `doctors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `bmdc_number` VARCHAR(50) NOT NULL,
  `medical_reg_no` VARCHAR(50) NULL,
  `designation` VARCHAR(150) NOT NULL,
  `experience_years` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `consultation_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `follow_up_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `follow_up_validity_days` SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  `bio` TEXT NULL,
  `languages` VARCHAR(255) NOT NULL DEFAULT 'Bangla, English',
  `verification_status` ENUM('PENDING', 'UNDER_REVIEW', 'VERIFIED', 'REJECTED', 'SUSPENDED') NOT NULL DEFAULT 'PENDING',
  `verification_notes` TEXT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_by` BIGINT UNSIGNED NULL,
  `signature_path` VARCHAR(255) NULL,
  `rating_avg` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_doctors_user` (`user_id`),
  UNIQUE KEY `uk_doctors_bmdc` (`bmdc_number`),
  INDEX `idx_doctors_verification` (`verification_status`),
  INDEX `idx_doctors_rating` (`rating_avg`),
  CONSTRAINT `fk_doctors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doctors_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Doctor Documents Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `doctor_documents`;
CREATE TABLE `doctor_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `doc_type` ENUM('BMDC_CERT', 'NID', 'DEGREE_CERT', 'OTHER') NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(50) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_doc_docs_doctor` (`doctor_id`),
  CONSTRAINT `fk_doc_docs_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_docs_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Patients Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `dob` DATE NULL,
  `gender` ENUM('MALE', 'FEMALE', 'OTHER') NULL,
  `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
  `address` TEXT NULL,
  `emergency_contact_name` VARCHAR(120) NULL,
  `emergency_contact_phone` VARCHAR(20) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_patients_user` (`user_id`),
  CONSTRAINT `fk_patients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Patient Family Members Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `patient_family_members`;
CREATE TABLE `patient_family_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `relationship` ENUM('FATHER', 'MOTHER', 'SPOUSE', 'CHILD', 'SIBLING', 'OTHER') NOT NULL,
  `dob` DATE NULL,
  `gender` ENUM('MALE', 'FEMALE', 'OTHER') NOT NULL,
  `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
  `emergency_info` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_family_patient` (`patient_id`),
  CONSTRAINT `fk_family_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Taxonomy: Specialties Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `specialties`;
CREATE TABLE `specialties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_specialties_slug` (`slug`),
  INDEX `idx_specialties_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. Doctor Specialties Pivot Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `doctor_specialties`;
CREATE TABLE `doctor_specialties` (
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `specialty_id` INT UNSIGNED NOT NULL,
  `is_primary` BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`doctor_id`, `specialty_id`),
  CONSTRAINT `fk_doc_spec_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_spec_specialty` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. Locations: Cities and Areas
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `cities`;
CREATE TABLE `cities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `division` VARCHAR(100) NOT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`id`),
  INDEX `idx_cities_division` (`division`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `areas`;
CREATE TABLE `areas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `city_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`id`),
  INDEX `idx_areas_city` (`city_id`),
  CONSTRAINT `fk_areas_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. Doctor Chambers Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `doctor_chambers`;
CREATE TABLE `doctor_chambers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `address` TEXT NOT NULL,
  `city_id` INT UNSIGNED NOT NULL,
  `area_id` INT UNSIGNED NOT NULL,
  `latitude` DECIMAL(10,8) NULL,
  `longitude` DECIMAL(11,8) NULL,
  `contact_phone` VARCHAR(20) NOT NULL,
  `room_number` VARCHAR(50) NULL,
  `token_prefix` VARCHAR(5) NOT NULL DEFAULT 'A',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_chambers_doctor` (`doctor_id`),
  INDEX `idx_chambers_location` (`city_id`, `area_id`),
  CONSTRAINT `fk_chambers_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chambers_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  CONSTRAINT `fk_chambers_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. Doctor Schedules Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `doctor_schedules`;
CREATE TABLE `doctor_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `chamber_id` BIGINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT UNSIGNED NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `slot_duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  `default_capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_doc_chamber_day` (`doctor_id`, `chamber_id`, `day_of_week`),
  INDEX `idx_schedules_chamber` (`chamber_id`),
  CONSTRAINT `fk_schedules_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_schedules_chamber` FOREIGN KEY (`chamber_id`) REFERENCES `doctor_chambers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 11. Doctor Schedule Breaks Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `doctor_schedule_breaks`;
CREATE TABLE `doctor_schedule_breaks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id` BIGINT UNSIGNED NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `label` VARCHAR(100) NOT NULL DEFAULT 'Break',
  PRIMARY KEY (`id`),
  INDEX `idx_breaks_schedule` (`schedule_id`),
  CONSTRAINT `fk_breaks_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `doctor_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 12. Doctor Daily Availability Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `doctor_daily_availability`;
CREATE TABLE `doctor_daily_availability` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `chamber_id` BIGINT UNSIGNED NOT NULL,
  `appointment_date` DATE NOT NULL,
  `status` ENUM('ON', 'OFF', 'PAUSED', 'FULL', 'CLOSED', 'COMPLETED') NOT NULL DEFAULT 'ON',
  `doctor_live_status` ENUM('AVAILABLE', 'CONSULTING', 'SHORT_BREAK', 'DELAYED', 'EMERGENCY_DELAY', 'FINISHED') NOT NULL DEFAULT 'AVAILABLE',
  `delay_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status_note` VARCHAR(255) NULL,
  `last_status_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_avail_doc_chamber_date` (`doctor_id`, `chamber_id`, `appointment_date`),
  INDEX `idx_avail_date_status` (`appointment_date`, `status`),
  CONSTRAINT `fk_avail_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avail_chamber` FOREIGN KEY (`chamber_id`) REFERENCES `doctor_chambers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 13. Doctor Capacity Table (Concurrency Protected)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `doctor_capacity`;
CREATE TABLE `doctor_capacity` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `chamber_id` BIGINT UNSIGNED NOT NULL,
  `appointment_date` DATE NOT NULL,
  `total_capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  `booked_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `version` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cap_doc_chamber_date` (`doctor_id`, `chamber_id`, `appointment_date`),
  CONSTRAINT `fk_cap_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cap_chamber` FOREIGN KEY (`chamber_id`) REFERENCES `doctor_chambers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 14. Doctor Token Sequences Table (Atomic Sequence Counter)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `doctor_token_sequences`;
CREATE TABLE `doctor_token_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `chamber_id` BIGINT UNSIGNED NOT NULL,
  `appointment_date` DATE NOT NULL,
  `current_sequence` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_seq_doc_chamber_date` (`doctor_id`, `chamber_id`, `appointment_date`),
  CONSTRAINT `fk_seq_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seq_chamber` FOREIGN KEY (`chamber_id`) REFERENCES `doctor_chambers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 15. Appointments Table (Immutably Stores Historical Pricing)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_number` VARCHAR(32) NOT NULL,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `chamber_id` BIGINT UNSIGNED NOT NULL,
  `appointment_date` DATE NOT NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `booked_by_user_id` BIGINT UNSIGNED NOT NULL,
  `family_member_id` BIGINT UNSIGNED NULL,
  `patient_type` ENUM('NEW', 'FOLLOW_UP') NOT NULL DEFAULT 'NEW',
  `token_number` INT UNSIGNED NOT NULL,
  `token_display` VARCHAR(10) NOT NULL,
  `status` ENUM('BOOKED', 'CHECKED_IN', 'WAITING', 'CALLED', 'CONSULTING', 'COMPLETED', 'NO_SHOW', 'SKIPPED', 'CANCELLED') NOT NULL DEFAULT 'BOOKED',
  `consultation_fee` DECIMAL(10,2) NOT NULL,
  `platform_fee` DECIMAL(10,2) NOT NULL,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_paid` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('PENDING', 'PAID', 'REFUNDED') NOT NULL DEFAULT 'PENDING',
  `check_in_time` TIMESTAMP NULL DEFAULT NULL,
  `check_in_method` ENUM('QR', 'MANUAL') NULL DEFAULT NULL,
  `consultation_start_time` TIMESTAMP NULL DEFAULT NULL,
  `consultation_end_time` TIMESTAMP NULL DEFAULT NULL,
  `cancellation_reason` TEXT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `cancelled_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_apt_number` (`appointment_number`),
  UNIQUE KEY `uk_apt_doc_chamber_date_token` (`doctor_id`, `chamber_id`, `appointment_date`, `token_number`),
  INDEX `idx_apt_patient` (`patient_id`),
  INDEX `idx_apt_session_lookup` (`doctor_id`, `chamber_id`, `appointment_date`, `status`),
  CONSTRAINT `fk_apt_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `fk_apt_chamber` FOREIGN KEY (`chamber_id`) REFERENCES `doctor_chambers` (`id`),
  CONSTRAINT `fk_apt_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `fk_apt_booker` FOREIGN KEY (`booked_by_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_apt_family` FOREIGN KEY (`family_member_id`) REFERENCES `patient_family_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_apt_canceller` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 16. Appointment Queue Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `appointment_queue`;
CREATE TABLE `appointment_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `chamber_id` BIGINT UNSIGNED NOT NULL,
  `appointment_date` DATE NOT NULL,
  `sequence_number` INT UNSIGNED NOT NULL,
  `status` ENUM('WAITING', 'CALLED', 'CONSULTING', 'COMPLETED', 'SKIPPED', 'NO_SHOW') NOT NULL DEFAULT 'WAITING',
  `called_at` TIMESTAMP NULL DEFAULT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `estimated_turn_start` TIME NULL DEFAULT NULL,
  `estimated_turn_end` TIME NULL DEFAULT NULL,
  `recommended_arrival_time` TIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_queue_appointment` (`appointment_id`),
  INDEX `idx_queue_lookup` (`doctor_id`, `chamber_id`, `appointment_date`, `status`, `sequence_number`),
  CONSTRAINT `fk_queue_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_queue_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `fk_queue_chamber` FOREIGN KEY (`chamber_id`) REFERENCES `doctor_chambers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 17. Queue Events Table (Audit Log for Queue Progress)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `queue_events`;
CREATE TABLE `queue_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `chamber_id` BIGINT UNSIGNED NOT NULL,
  `appointment_date` DATE NOT NULL,
  `event_type` ENUM('CHECK_IN', 'CALL', 'START', 'COMPLETE', 'SKIP', 'NO_SHOW', 'DELAY', 'PAUSE', 'RESUME') NOT NULL,
  `actor_id` BIGINT UNSIGNED NOT NULL,
  `actor_role` ENUM('PATIENT', 'DOCTOR', 'PLATFORM_ADMIN', 'SYSTEM') NOT NULL,
  `payload_json` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_events_appointment` (`appointment_id`),
  INDEX `idx_events_session` (`doctor_id`, `chamber_id`, `appointment_date`),
  CONSTRAINT `fk_events_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_events_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 18. Check-ins Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `check_ins`;
CREATE TABLE `check_ins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `qr_code_hash` VARCHAR(64) NULL,
  `check_in_method` ENUM('QR', 'MANUAL') NOT NULL DEFAULT 'QR',
  `verified_by` BIGINT UNSIGNED NULL,
  `checked_in_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_checkins_appointment` (`appointment_id`),
  CONSTRAINT `fk_checkins_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checkins_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 19. Consultations Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `consultations`;
CREATE TABLE `consultations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `symptoms` TEXT NULL,
  `clinical_notes` TEXT NULL,
  `follow_up_date` DATE NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_consultations_appointment` (`appointment_id`),
  INDEX `idx_consultations_patient` (`patient_id`),
  INDEX `idx_consultations_doctor` (`doctor_id`),
  CONSTRAINT `fk_consult_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_consult_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `fk_consult_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 20. Diagnoses Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `diagnoses`;
CREATE TABLE `diagnoses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` BIGINT UNSIGNED NOT NULL,
  `condition_name` VARCHAR(255) NOT NULL,
  `icd10_code` VARCHAR(20) NULL,
  `severity` ENUM('MILD', 'MODERATE', 'SEVERE', 'CHRONIC') NOT NULL DEFAULT 'MODERATE',
  `type` ENUM('PRIMARY', 'SECONDARY') NOT NULL DEFAULT 'PRIMARY',
  PRIMARY KEY (`id`),
  INDEX `idx_diagnoses_consultation` (`consultation_id`),
  CONSTRAINT `fk_diag_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 21. Prescriptions Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE `prescriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` BIGINT UNSIGNED NOT NULL,
  `prescription_code` VARCHAR(32) NOT NULL,
  `doctor_signature_path` VARCHAR(255) NULL,
  `verification_token` VARCHAR(64) NOT NULL,
  `is_signed` BOOLEAN NOT NULL DEFAULT TRUE,
  `pdf_path` VARCHAR(255) NULL,
  `qr_code_path` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prescriptions_consultation` (`consultation_id`),
  UNIQUE KEY `uk_prescriptions_code` (`prescription_code`),
  UNIQUE KEY `uk_prescriptions_token` (`verification_token`),
  CONSTRAINT `fk_rx_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 22. Prescription Items Table (Medicines & Dosage)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `prescription_items`;
CREATE TABLE `prescription_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `prescription_id` BIGINT UNSIGNED NOT NULL,
  `medicine_name` VARCHAR(150) NOT NULL,
  `dosage` VARCHAR(50) NOT NULL,
  `frequency` VARCHAR(50) NOT NULL,
  `duration` VARCHAR(50) NOT NULL,
  `instructions` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_rx_items_prescription` (`prescription_id`),
  CONSTRAINT `fk_rx_items_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 23. Investigations & Medical Advice Tables
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `investigations`;
CREATE TABLE `investigations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` BIGINT UNSIGNED NOT NULL,
  `test_name` VARCHAR(255) NOT NULL,
  `notes` TEXT NULL,
  `urgent` BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`id`),
  INDEX `idx_investigations_consultation` (`consultation_id`),
  CONSTRAINT `fk_inv_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `medical_advice`;
CREATE TABLE `medical_advice` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `consultation_id` BIGINT UNSIGNED NOT NULL,
  `advice_text` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_advice_consultation` (`consultation_id`),
  CONSTRAINT `fk_advice_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 24. Medical Records Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `medical_records`;
CREATE TABLE `medical_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `record_type` ENUM('PRESCRIPTION', 'LAB_REPORT', 'IMAGING', 'DISCHARGE_SUMMARY', 'OTHER') NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `consultation_id` BIGINT UNSIGNED NULL,
  `uploaded_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_records_patient` (`patient_id`, `created_at`),
  CONSTRAINT `fk_records_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_records_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_records_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 25. Payments & Platform Transactions Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `transaction_ref` VARCHAR(64) NOT NULL,
  `gateway_name` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'BDT',
  `status` ENUM('INITIATED', 'PENDING', 'SUCCESSFUL', 'FAILED', 'CANCELLED', 'REFUNDED') NOT NULL DEFAULT 'INITIATED',
  `gateway_payload` JSON NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payments_ref` (`transaction_ref`),
  INDEX `idx_payments_appointment` (`appointment_id`),
  INDEX `idx_payments_status` (`status`),
  CONSTRAINT `fk_payments_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `platform_transactions`;
CREATE TABLE `platform_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `transaction_type` ENUM('FEE_COLLECTION', 'REFUND', 'ADJUSTMENT') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `balance_after` DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_trans_payment` (`payment_id`),
  CONSTRAINT `fk_trans_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 26. Promotions, Rules & Usage Tables
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `promotions`;
CREATE TABLE `promotions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `type` ENUM('FIXED', 'PERCENT', 'FREE') NOT NULL,
  `discount_value` DECIMAL(10,2) NOT NULL,
  `max_discount` DECIMAL(10,2) NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `usage_limit` INT UNSIGNED NULL,
  `per_user_limit` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `minimum_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `patient_type` ENUM('ALL', 'NEW', 'FOLLOW_UP') NOT NULL DEFAULT 'ALL',
  `priority` INT NOT NULL DEFAULT 0,
  `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_promotions_code` (`code`),
  INDEX `idx_promotions_status` (`status`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `promotion_rules`;
CREATE TABLE `promotion_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `promotion_id` BIGINT UNSIGNED NOT NULL,
  `rule_type` ENUM('DOCTOR', 'SPECIALTY', 'CITY', 'CHAMBER') NOT NULL,
  `rule_value` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_rules_promotion` (`promotion_id`),
  CONSTRAINT `fk_rules_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `promotion_usage`;
CREATE TABLE `promotion_usage` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `promotion_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `discount_applied` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usage_promo_apt` (`promotion_id`, `appointment_id`),
  INDEX `idx_usage_user` (`user_id`),
  CONSTRAINT `fk_usage_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_usage_apt` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `promotion_id` BIGINT UNSIGNED NOT NULL,
  `coupon_code` VARCHAR(50) NOT NULL,
  `is_redeemed` BOOLEAN NOT NULL DEFAULT FALSE,
  `redeemed_by` BIGINT UNSIGNED NULL,
  `redeemed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_coupons_code` (`coupon_code`),
  CONSTRAINT `fk_coupons_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coupons_user` FOREIGN KEY (`redeemed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 27. Waitlists Table
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `waitlists`;
CREATE TABLE `waitlists` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `chamber_id` BIGINT UNSIGNED NOT NULL,
  `appointment_date` DATE NOT NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('WAITING', 'OFFERED', 'CLAIMED', 'EXPIRED', 'CANCELLED') NOT NULL DEFAULT 'WAITING',
  `offered_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_waitlist_lookup` (`doctor_id`, `chamber_id`, `appointment_date`, `status`),
  CONSTRAINT `fk_waitlist_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `fk_waitlist_chamber` FOREIGN KEY (`chamber_id`) REFERENCES `doctor_chambers` (`id`),
  CONSTRAINT `fk_waitlist_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 28. Reviews and Favorites Tables
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` TEXT NULL,
  `is_published` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reviews_appointment` (`appointment_id`),
  INDEX `idx_reviews_doctor` (`doctor_id`, `is_published`),
  CONSTRAINT `fk_reviews_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `fk_reviews_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `favorites`;
CREATE TABLE `favorites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_favorites` (`patient_id`, `doctor_id`),
  CONSTRAINT `fk_fav_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 29. Notifications & Logs
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `event_key` VARCHAR(100) NOT NULL,
  `idempotency_key` VARCHAR(120) NOT NULL,
  `channel` ENUM('PUSH', 'SMS', 'WHATSAPP', 'EMAIL') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('PENDING', 'SENT', 'FAILED') NOT NULL DEFAULT 'PENDING',
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_notifications_idempotency` (`idempotency_key`),
  INDEX `idx_notifications_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `notification_preferences`;
CREATE TABLE `notification_preferences` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `push_enabled` BOOLEAN NOT NULL DEFAULT TRUE,
  `sms_enabled` BOOLEAN NOT NULL DEFAULT TRUE,
  `whatsapp_enabled` BOOLEAN NOT NULL DEFAULT TRUE,
  `email_enabled` BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_notif_pref_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 30. Audit Logs & System Settings
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_id` BIGINT UNSIGNED NOT NULL,
  `actor_role` ENUM('PATIENT', 'DOCTOR', 'PLATFORM_ADMIN', 'SYSTEM') NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity` VARCHAR(50) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `old_value_json` JSON NULL,
  `new_value_json` JSON NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_audit_actor` (`actor_id`, `created_at`),
  INDEX `idx_audit_entity` (`entity`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `key_name` VARCHAR(50) NOT NULL,
  `setting_value` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_name`),
  CONSTRAINT `fk_settings_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
