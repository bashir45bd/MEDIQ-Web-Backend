-- ==============================================================================
-- MEDIQ Database Seeders: 002_seed_data.sql
-- Production Seed Data (Settings, Taxonomy, Verified Doctors, Chambers, Schedules)
-- ==============================================================================

-- 1. System Settings
INSERT INTO `system_settings` (`key_name`, `setting_value`, `description`) VALUES
('default_platform_fee', '50.00', 'Standard platform fee charged per confirmed booking in BDT'),
('no_show_grace_minutes', '10', 'Grace period in minutes before patient can be marked as No-Show'),
('waitlist_claim_window_mins', '15', 'Time window in minutes for waitlist patient to claim offered slot'),
('emergency_contact_phone', '+8801999999999', 'MEDIQ 24/7 patient helpline');

-- 2. Specialties
INSERT INTO `specialties` (`id`, `name`, `slug`, `icon`, `description`, `is_active`) VALUES
(1, 'Cardiology', 'cardiology', 'heart-pulse', 'Heart, blood vessels, and cardiovascular conditions', 1),
(2, 'Dermatology', 'dermatology', 'sparkles', 'Skin, hair, nails, and cosmetic dermatology', 1),
(3, 'General Medicine', 'general-medicine', 'stethoscope', 'Primary health concerns, fever, diabetes, and hypertension', 1),
(4, 'Gynecology & Obstetrics', 'gynecology-obstetrics', 'baby', 'Women reproductive health, prenatal care, and delivery', 1),
(5, 'Pediatrics', 'pediatrics', 'child', 'Infant, child, and adolescent healthcare', 1),
(6, 'Orthopedics', 'orthopedics', 'bone', 'Bones, joints, ligaments, and fracture management', 1),
(7, 'Neurology', 'neurology', 'brain', 'Brain, spinal cord, and nervous system disorders', 1),
(8, 'Ophthalmology', 'ophthalmology', 'eye', 'Eye care, vision, and cataract treatment', 1);

-- 3. Locations: Cities & Areas
INSERT INTO `cities` (`id`, `name`, `division`, `is_active`) VALUES
(1, 'Dhaka', 'Dhaka Division', 1),
(2, 'Sylhet', 'Sylhet Division', 1),
(3, 'Chittagong', 'Chittagong Division', 1);

INSERT INTO `areas` (`id`, `city_id`, `name`, `postal_code`, `is_active`) VALUES
(1, 1, 'Dhanmondi', '1205', 1),
(2, 1, 'Gulshan 2', '1212', 1),
(3, 1, 'Uttara Sector 7', '1230', 1),
(4, 1, 'Mirpur 10', '1216', 1),
(5, 2, 'Amberkhana', '3100', 1),
(6, 2, 'Zindabazar', '3100', 1),
(7, 2, 'Subidbazar', '3100', 1),
(8, 3, 'Agrabad', '4100', 1),
(9, 3, 'Panchlaish', '4203', 1);

-- 4. Initial Platform Administrator
-- Password: Admin@12345
INSERT INTO `users` (`id`, `role`, `full_name`, `email`, `phone`, `password_hash`, `status`, `phone_verified_at`) VALUES
(1, 'PLATFORM_ADMIN', 'MEDIQ System Admin', 'admin@mediq.com.bd', '+8801700000001', '$2y$10$FAL3/ZbNcNWWzGaVpQl3SewPj81gjmcC3q/WwBzCEmvPoHnUELbUG', 'ACTIVE', NOW());

-- 5. Seed Verified Doctors
-- Password: Doctor@123
INSERT INTO `users` (`id`, `role`, `full_name`, `email`, `phone`, `password_hash`, `status`, `phone_verified_at`) VALUES
(2, 'DOCTOR', 'Dr. Ahmed Rahman', 'dr.ahmed@mediq.com.bd', '+8801711111111', '$2y$10$2NJ0AZKjlQXuRsGLwjobIeZK/litB7XGlCcFC31no4pNxs45Tu47u', 'ACTIVE', NOW()),
(3, 'DOCTOR', 'Dr. Fatima Begum', 'dr.fatima@mediq.com.bd', '+8801722222222', '$2y$10$2NJ0AZKjlQXuRsGLwjobIeZK/litB7XGlCcFC31no4pNxs45Tu47u', 'ACTIVE', NOW()),
(4, 'DOCTOR', 'Dr. Tanvir Hasan', 'dr.tanvir@mediq.com.bd', '+8801733333333', '$2y$10$2NJ0AZKjlQXuRsGLwjobIeZK/litB7XGlCcFC31no4pNxs45Tu47u', 'ACTIVE', NOW());

INSERT INTO `doctors` (`id`, `user_id`, `bmdc_number`, `medical_reg_no`, `designation`, `experience_years`, `consultation_fee`, `follow_up_fee`, `follow_up_validity_days`, `bio`, `languages`, `verification_status`, `verified_at`, `verified_by`, `rating_avg`, `rating_count`) VALUES
(1, 2, 'BMDC-A12345', 'REG-CARD-8801', 'MBBS, FCPS (Cardiology), MD', 14, 1000.00, 800.00, 30, 'Senior Consultant Interventional Cardiologist with extensive clinical experience across National Heart Foundation and Apollo.', 'Bangla, English', 'VERIFIED', NOW(), 1, 4.85, 24),
(2, 3, 'BMDC-A54321', 'REG-GYN-9912', 'MBBS, DGO, MCPS, FCPS (Gynecology)', 11, 1200.00, 900.00, 30, 'Specialist Gynecologist, Infertility & High-Risk Pregnancy Consultant.', 'Bangla, English, Sylheti', 'VERIFIED', NOW(), 1, 4.90, 38),
(3, 4, 'BMDC-A67890', 'REG-PED-3341', 'MBBS, DCH, MD (Pediatrics)', 8, 800.00, 600.00, 21, 'Consultant Child & Adolescent Health Specialist, dedicated to pediatric growth and neonatal wellness.', 'Bangla, English', 'VERIFIED', NOW(), 1, 4.70, 16);

INSERT INTO `doctor_specialties` (`doctor_id`, `specialty_id`, `is_primary`) VALUES
(1, 1, 1),
(1, 3, 0),
(2, 4, 1),
(3, 5, 1);

-- 6. Doctor Chambers
INSERT INTO `doctor_chambers` (`id`, `doctor_id`, `name`, `address`, `city_id`, `area_id`, `latitude`, `longitude`, `contact_phone`, `room_number`, `token_prefix`, `is_active`) VALUES
(1, 1, 'Medinova Medical Centre', 'House 71/A, Road 5/A, Dhanmondi', 1, 1, 23.7461000, 90.3742000, '+8801711999888', 'Room 304, 3rd Floor', 'A', 1),
(2, 1, 'Popular Diagnostic Centre', 'Cha-90/2, Pragati Sarani, North Badda', 1, 2, 23.7806000, 90.4267000, '+8801711999777', 'Room 102', 'B', 1),
(3, 2, 'Mount Adora Hospital', 'Nayasarak, Sylhet', 2, 6, 24.8967000, 91.8744000, '+8801822334455', 'Suite 201', 'A', 1),
(4, 3, 'Ibn Sina Hospital', 'House 48, Road 9/A, Dhanmondi', 1, 1, 23.7490000, 90.3760000, '+8801933445566', 'Room 408', 'A', 1);

-- 7. Doctor Weekly Schedules (Every Day 0 to 6)
-- Dr. Ahmed at Medinova: Sunday(0), Monday(1), Tuesday(2), Wednesday(3), Thursday(4)
INSERT INTO `doctor_schedules` (`doctor_id`, `chamber_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration_minutes`, `default_capacity`, `is_active`) VALUES
(1, 1, 0, '17:00:00', '21:00:00', 10, 20, 1),
(1, 1, 1, '17:00:00', '21:00:00', 10, 20, 1),
(1, 1, 2, '17:00:00', '21:00:00', 10, 20, 1),
(1, 1, 3, '17:00:00', '21:00:00', 10, 20, 1),
(1, 1, 4, '17:00:00', '21:00:00', 10, 20, 1),
(1, 1, 5, '17:00:00', '21:00:00', 10, 20, 1),
(1, 1, 6, '17:00:00', '21:00:00', 10, 20, 1),
-- Dr. Fatima at Mount Adora
(2, 3, 0, '16:00:00', '20:00:00', 12, 18, 1),
(2, 3, 1, '16:00:00', '20:00:00', 12, 18, 1),
(2, 3, 2, '16:00:00', '20:00:00', 12, 18, 1),
(2, 3, 3, '16:00:00', '20:00:00', 12, 18, 1),
(2, 3, 4, '16:00:00', '20:00:00', 12, 18, 1),
(2, 3, 5, '16:00:00', '20:00:00', 12, 18, 1),
(2, 3, 6, '16:00:00', '20:00:00', 12, 18, 1);

-- 8. Seed Patients & Family Members
-- Password: Patient@123
INSERT INTO `users` (`id`, `role`, `full_name`, `email`, `phone`, `password_hash`, `status`, `phone_verified_at`) VALUES
(5, 'PATIENT', 'Md. Kamal Hossain', 'kamal@gmail.com', '+8801811111111', '$2y$10$Iz5yTYtzeEhYY2j8LCMLKuGYngxExAVwi3t5GAfy1W82A4/veucuu', 'ACTIVE', NOW()),
(6, 'PATIENT', 'Nasreen Akter', 'nasreen@gmail.com', '+8801822222222', '$2y$10$Iz5yTYtzeEhYY2j8LCMLKuGYngxExAVwi3t5GAfy1W82A4/veucuu', 'ACTIVE', NOW());

INSERT INTO `patients` (`id`, `user_id`, `dob`, `gender`, `blood_group`, `address`, `emergency_contact_name`, `emergency_contact_phone`) VALUES
(1, 5, '1988-04-12', 'MALE', 'B+', 'House 14, Road 3, Dhanmondi, Dhaka', 'Salma Hossain (Spouse)', '+8801811999000'),
(2, 6, '1994-09-22', 'FEMALE', 'O+', 'Amberkhana Point, Sylhet', 'Abdul Karim (Father)', '+8801822888111');

INSERT INTO `patient_family_members` (`id`, `patient_id`, `full_name`, `relationship`, `dob`, `gender`, `blood_group`, `emergency_info`) VALUES
(1, 1, 'Salma Hossain', 'SPOUSE', '1992-06-15', 'FEMALE', 'A+', 'Asthmatic, allergic to penicillin'),
(2, 1, 'Aryan Hossain', 'CHILD', '2019-11-04', 'MALE', 'B+', 'Pediatric patient');

-- 9. Daily Availability & Capacity Initialization (Today & Tomorrow)
INSERT INTO `doctor_daily_availability` (`doctor_id`, `chamber_id`, `appointment_date`, `status`, `doctor_live_status`, `delay_minutes`, `status_note`) VALUES
(1, 1, CURRENT_DATE(), 'ON', 'AVAILABLE', 0, 'On schedule. Welcome to Medinova.'),
(1, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), 'ON', 'AVAILABLE', 0, 'Booking open for tomorrow.'),
(2, 3, CURRENT_DATE(), 'ON', 'AVAILABLE', 0, 'Chamber starts at 4:00 PM.');

INSERT INTO `doctor_capacity` (`doctor_id`, `chamber_id`, `appointment_date`, `total_capacity`, `booked_count`, `version`) VALUES
(1, 1, CURRENT_DATE(), 20, 0, 1),
(1, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), 20, 0, 1),
(2, 3, CURRENT_DATE(), 18, 0, 1);

INSERT INTO `doctor_token_sequences` (`doctor_id`, `chamber_id`, `appointment_date`, `current_sequence`) VALUES
(1, 1, CURRENT_DATE(), 0),
(1, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), 0),
(2, 3, CURRENT_DATE(), 0);

-- 10. Promotions & Coupons
INSERT INTO `promotions` (`id`, `name`, `code`, `type`, `discount_value`, `max_discount`, `start_date`, `end_date`, `usage_limit`, `per_user_limit`, `minimum_fee`, `patient_type`, `priority`, `status`) VALUES
(1, 'First Free Platform Fee', 'FIRSTFREE', 'FREE', 50.00, 50.00, '2026-01-01', '2026-12-31', 5000, 1, 0.00, 'NEW', 10, 'ACTIVE'),
(2, 'Healthcare Launch Discount', 'HEALTH20', 'PERCENT', 40.00, 20.00, '2026-01-01', '2026-12-31', 10000, 3, 0.00, 'ALL', 5, 'ACTIVE');

INSERT INTO `coupons` (`promotion_id`, `coupon_code`) VALUES
(1, 'FIRSTFREE-USER01'),
(1, 'FIRSTFREE-USER02');
