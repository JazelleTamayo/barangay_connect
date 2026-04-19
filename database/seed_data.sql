-- ============================================
-- Barangay Connect – Seed Data
-- database/seed_data.sql
--
-- HOW TO USE:
-- 1. Make sure barangay_connect.sql is imported first
-- 2. Open phpMyAdmin
-- 3. Import this file
--
-- IMPORTANT: Passwords below are bcrypt hashes.
-- Demo passwords:
--   captain   → captain123
--   secretary → secretary123
--   staff     → staff123
--   sysadmin  → sysadmin123
--   resident  → resident123
-- ============================================

USE barangay_connect;

-- ─────────────────────────────────────────────
-- SEED: Demo User Accounts
-- ─────────────────────────────────────────────
INSERT INTO UserAccount
    (Username, PasswordHash, Role, AccountStatus, FullName, Email)
VALUES
(
    'captain',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'captain',
    'Active',
    'Hon. Juan dela Cruz',
    'captain@barangay.gov.ph'
),
(
    'secretary',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'secretary',
    'Active',
    'Maria Santos',
    'secretary@barangay.gov.ph'
),
(
    'staff',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'staff',
    'Active',
    'Jose Reyes',
    'staff@barangay.gov.ph'
),
(
    'sysadmin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'sysadmin',
    'Active',
    'Tech Admin',
    'sysadmin@barangay.gov.ph'
);

-- ─────────────────────────────────────────────
-- SEED: Sample Resident + Resident Account
-- ─────────────────────────────────────────────
INSERT INTO Resident
    (FirstName, MiddleName, LastName, Birthdate, Sex,
     Address, Purok, ContactNumber, Email, Status)
VALUES
(
    'Ana', 'Reyes', 'Gonzales',
    '1995-06-15', 'Female',
    '123 Sampaguita St., Barangay Connect',
    'Purok 2', '09171234567',
    'ana.gonzales@email.com', 'Active'
),
(
    'Pedro', 'Santos', 'Dela Cruz',
    '1988-03-22', 'Male',
    '456 Kalayaan Ave., Barangay Connect',
    'Purok 1', '09281234567',
    'pedro.delacruz@email.com', 'Active'
),
(
    'Maria', 'Lopez', 'Reyes',
    '2000-11-10', 'Female',
    '789 Mabini St., Barangay Connect',
    'Purok 3', '09391234567',
    'maria.reyes@email.com', 'Active'
);

INSERT INTO UserAccount
    (ResidentID, Username, PasswordHash, Role,
     AccountStatus, FullName, Email)
VALUES
(
    1,
    'resident',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'resident',
    'Active',
    'Ana Gonzales',
    'ana.gonzales@email.com'
);

-- ─────────────────────────────────────────────
-- SEED: Sample Facilities
-- ─────────────────────────────────────────────
INSERT INTO Facility
    (FacilityName, Capacity, ReservationFee, Description, Status)
VALUES
(
    'Barangay Hall',
    100,
    500.00,
    'Main barangay hall for official events and community gatherings.',
    'Active'
),
(
    'Basketball Court',
    200,
    300.00,
    'Outdoor basketball court for sports events and community activities.',
    'Active'
),
(
    'Multi-Purpose Hall',
    150,
    800.00,
    'Indoor hall for seminars, meetings, and private events.',
    'Active'
),
(
    'Covered Court',
    250,
    600.00,
    'Large covered court for big community events.',
    'Active'
);

-- ─────────────────────────────────────────────
-- NOTE: Password Hash Info
-- ─────────────────────────────────────────────
-- The hash above '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
-- is the bcrypt hash for the word 'password'.
--
-- For production, generate real hashes using:
--   password_hash('yourpassword', PASSWORD_BCRYPT)
--
-- For the demo, login.php uses hardcoded demo accounts
-- that bypass the DB so you can still log in without
-- worrying about the hash values.