-- ============================================
-- Barangay Connect – MySQL Database Schema
-- database/barangay_connect.sql
--
-- HOW TO USE:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Click "Import" tab
-- 3. Choose this file and click "Go"
-- ============================================

CREATE DATABASE IF NOT EXISTS barangay_connect
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE barangay_connect;

-- ─────────────────────────────────────────────
-- TABLE: Resident
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Resident (
    ResidentID      INT AUTO_INCREMENT PRIMARY KEY,
    FirstName       VARCHAR(100)    NOT NULL,
    MiddleName      VARCHAR(100),
    LastName        VARCHAR(100)    NOT NULL,
    Birthdate       DATE            NOT NULL,
    Sex             ENUM('Male','Female') NOT NULL,
    Address         VARCHAR(255)    NOT NULL,
    Purok           VARCHAR(100),
    ContactNumber   VARCHAR(20),
    Email           VARCHAR(150),
    GovIDType       VARCHAR(50),
    GovIDNumber     VARCHAR(100),
    GovIDImagePath  VARCHAR(255),
    Status          ENUM('Active','Inactive') DEFAULT 'Active',
    CreatedAt       DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt       DATETIME DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resident (FirstName, LastName, Birthdate, Address)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- TABLE: UserAccount
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS UserAccount (
    UserAccountID   INT AUTO_INCREMENT PRIMARY KEY,
    ResidentID      INT NULL,
    Username        VARCHAR(100)    NOT NULL UNIQUE,
    PasswordHash    VARCHAR(255)    NOT NULL,
    Role            ENUM('captain','secretary','staff',
                         'sysadmin','resident') NOT NULL,
    AccountStatus   ENUM('Active','Inactive',
                         'PendingVerification','Rejected')
                    DEFAULT 'PendingVerification',
    FullName        VARCHAR(255),
    Email           VARCHAR(150),
    VerifiedBy      INT NULL,
    VerifiedAt      DATETIME NULL,
    RejectionReason TEXT NULL,
    CreatedAt       DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt       DATETIME DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ResidentID)
        REFERENCES Resident(ResidentID)
        ON DELETE SET NULL,
    FOREIGN KEY (VerifiedBy)
        REFERENCES UserAccount(UserAccountID)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- TABLE: Facility
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Facility (
    FacilityID      INT AUTO_INCREMENT PRIMARY KEY,
    FacilityName    VARCHAR(150)    NOT NULL,
    Capacity        INT,
    ReservationFee  DECIMAL(10,2)   DEFAULT 0.00,
    Description     TEXT,
    Status          ENUM('Active','Inactive') DEFAULT 'Active',
    CreatedAt       DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- TABLE: ServiceRequest
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ServiceRequest (
    RequestID       INT AUTO_INCREMENT PRIMARY KEY,
    ReferenceNo     VARCHAR(30)     NOT NULL UNIQUE,
    ResidentID      INT             NOT NULL,
    RequestType     ENUM('Clearance','Indigency',
                         'FacilityReservation','Complaint') NOT NULL,
    Purpose         TEXT,
    Status          ENUM('Pending','ForApproval','Approved',
                         'Rejected','Released','Cancelled')
                    DEFAULT 'Pending',
    Remarks         TEXT,
    ProcessedBy     INT NULL,
    ProcessedAt     DATETIME NULL,
    ReleasedBy      INT NULL,
    ReleasedAt      DATETIME NULL,
    CancelledBy     INT NULL,
    CancelledAt     DATETIME NULL,
    CancellationReason TEXT NULL,
    CreatedBy       INT NULL,
    CreatedAt       DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt       DATETIME DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ResidentID)
        REFERENCES Resident(ResidentID),
    FOREIGN KEY (ProcessedBy)
        REFERENCES UserAccount(UserAccountID)
        ON DELETE SET NULL,
    FOREIGN KEY (ReleasedBy)
        REFERENCES UserAccount(UserAccountID)
        ON DELETE SET NULL,
    FOREIGN KEY (CancelledBy)
        REFERENCES UserAccount(UserAccountID)
        ON DELETE SET NULL,
    FOREIGN KEY (CreatedBy)
        REFERENCES UserAccount(UserAccountID)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- TABLE: FacilityReservation
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS FacilityReservation (
    ReservationID   INT AUTO_INCREMENT PRIMARY KEY,
    RequestID       INT             NOT NULL UNIQUE,
    FacilityID      INT             NOT NULL,
    ReservationDate DATE            NOT NULL,
    TimeSlot        VARCHAR(50),
    EventPurpose    TEXT,
    FOREIGN KEY (RequestID)
        REFERENCES ServiceRequest(RequestID)
        ON DELETE CASCADE,
    FOREIGN KEY (FacilityID)
        REFERENCES Facility(FacilityID)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- TABLE: Complaint
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Complaint (
    ComplaintID             INT AUTO_INCREMENT PRIMARY KEY,
    RequestID               INT             NOT NULL UNIQUE,
    RespondentName          VARCHAR(200),
    RespondentContact       VARCHAR(50),
    RespondentResidentID    INT NULL,
    IncidentDate            DATE,
    IncidentLocation        VARCHAR(255),
    Description             TEXT,
    MediationDate           DATE NULL,
    ActionsTaken            TEXT NULL,
    FOREIGN KEY (RequestID)
        REFERENCES ServiceRequest(RequestID)
        ON DELETE CASCADE,
    FOREIGN KEY (RespondentResidentID)
        REFERENCES Resident(ResidentID)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- TABLE: Payment
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Payment (
    PaymentID       INT AUTO_INCREMENT PRIMARY KEY,
    RequestID       INT             NOT NULL,
    ReceiptNo       VARCHAR(50)     NOT NULL UNIQUE,
    Amount          DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    PaymentMethod   ENUM('Cash','GCash','None') DEFAULT 'Cash',
    RecordedBy      INT NULL,
    RecordedAt      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RequestID)
        REFERENCES ServiceRequest(RequestID),
    FOREIGN KEY (RecordedBy)
        REFERENCES UserAccount(UserAccountID)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- TABLE: AuditLog
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS AuditLog (
    LogID           INT AUTO_INCREMENT PRIMARY KEY,
    UserAccountID   INT NULL,
    Username        VARCHAR(100),
    Role            VARCHAR(50),
    Action          VARCHAR(255)    NOT NULL,
    RecordAffected  VARCHAR(100),
    IPAddress       VARCHAR(45),
    LoggedAt        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserAccountID)
        REFERENCES UserAccount(UserAccountID)
        ON DELETE SET NULL
) ENGINE=InnoDB;