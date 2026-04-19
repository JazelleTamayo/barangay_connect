-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2026 at 12:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `barangay_connect`
--

-- --------------------------------------------------------

--
-- Table structure for table `auditlog`
--

CREATE TABLE `auditlog` (
  `LogID` int(11) NOT NULL,
  `UserAccountID` int(11) DEFAULT NULL,
  `Username` varchar(100) DEFAULT NULL,
  `Role` varchar(50) DEFAULT NULL,
  `Action` varchar(255) NOT NULL,
  `RecordAffected` varchar(100) DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `LoggedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auditlog`
--

INSERT INTO `auditlog` (`LogID`, `UserAccountID`, `Username`, `Role`, `Action`, `RecordAffected`, `IPAddress`, `LoggedAt`) VALUES
(1, NULL, 'system', 'unknown', 'New self-registration submitted', 'ResidentID: 4 | Username: jazelletamayo', '::1', '2026-04-19 17:18:12'),
(2, 2, 'secretary', 'secretary', 'Approved resident account', 'UserAccountID: 10', '::1', '2026-04-19 17:19:30');

-- --------------------------------------------------------

--
-- Table structure for table `captainprofile`
--

CREATE TABLE `captainprofile` (
  `CaptainID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `TermStart` date DEFAULT NULL,
  `TermEnd` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `captainprofile`
--

INSERT INTO `captainprofile` (`CaptainID`, `UserID`, `FirstName`, `LastName`, `ContactNumber`, `TermStart`, `TermEnd`) VALUES
(1, 1, 'Hon. Juan', 'dela Cruz', '09123456789', '2024-01-01', '2027-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `complaint`
--

CREATE TABLE `complaint` (
  `ComplaintID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `RespondentName` varchar(200) DEFAULT NULL,
  `RespondentContact` varchar(50) DEFAULT NULL,
  `RespondentResidentID` int(11) DEFAULT NULL,
  `IncidentDate` date DEFAULT NULL,
  `IncidentLocation` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `MediationDate` date DEFAULT NULL,
  `ActionsTaken` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facility`
--

CREATE TABLE `facility` (
  `FacilityID` int(11) NOT NULL,
  `FacilityName` varchar(150) NOT NULL,
  `Capacity` int(11) DEFAULT NULL,
  `ReservationFee` decimal(10,2) DEFAULT 0.00,
  `Description` text DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility`
--

INSERT INTO `facility` (`FacilityID`, `FacilityName`, `Capacity`, `ReservationFee`, `Description`, `Status`, `CreatedAt`) VALUES
(1, 'Barangay Hall', 100, 500.00, 'Main barangay hall for official events and community gatherings.', 'Active', '2026-04-19 15:39:22'),
(2, 'Basketball Court', 200, 300.00, 'Outdoor basketball court for sports events and community activities.', 'Active', '2026-04-19 15:39:22'),
(3, 'Multi-Purpose Hall', 150, 800.00, 'Indoor hall for seminars, meetings, and private events.', 'Active', '2026-04-19 15:39:22'),
(4, 'Covered Court', 250, 600.00, 'Large covered court for big community events.', 'Active', '2026-04-19 15:39:22');

-- --------------------------------------------------------

--
-- Table structure for table `facilityreservation`
--

CREATE TABLE `facilityreservation` (
  `ReservationID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `FacilityID` int(11) NOT NULL,
  `ReservationDate` date NOT NULL,
  `TimeSlot` varchar(50) DEFAULT NULL,
  `EventPurpose` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `ReceiptNo` varchar(50) NOT NULL,
  `Amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `PaymentMethod` enum('Cash','GCash','None') DEFAULT 'Cash',
  `RecordedBy` int(11) DEFAULT NULL,
  `RecordedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resident`
--

CREATE TABLE `resident` (
  `ResidentID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `MiddleName` varchar(100) DEFAULT NULL,
  `LastName` varchar(100) NOT NULL,
  `Birthdate` date NOT NULL,
  `Sex` enum('Male','Female') NOT NULL,
  `Address` varchar(255) NOT NULL,
  `Purok` varchar(100) DEFAULT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `GovIDType` varchar(50) DEFAULT NULL,
  `GovIDNumber` varchar(100) DEFAULT NULL,
  `GovIDImagePath` varchar(255) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resident`
--

INSERT INTO `resident` (`ResidentID`, `FirstName`, `MiddleName`, `LastName`, `Birthdate`, `Sex`, `Address`, `Purok`, `ContactNumber`, `Email`, `GovIDType`, `GovIDNumber`, `GovIDImagePath`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Ana', 'Reyes', 'Gonzales', '1995-06-15', 'Female', '123 Sampaguita St., Barangay Connect', 'Purok 2', '09171234567', 'ana.gonzales@email.com', NULL, NULL, NULL, 'Active', '2026-04-19 15:39:22', '2026-04-19 15:39:22'),
(2, 'Pedro', 'Santos', 'Dela Cruz', '1988-03-22', 'Male', '456 Kalayaan Ave., Barangay Connect', 'Purok 1', '09281234567', 'pedro.delacruz@email.com', NULL, NULL, NULL, 'Active', '2026-04-19 15:39:22', '2026-04-19 15:39:22'),
(3, 'Maria', 'Lopez', 'Reyes', '2000-11-10', 'Female', '789 Mabini St., Barangay Connect', 'Purok 3', '09391234567', 'maria.reyes@email.com', NULL, NULL, NULL, 'Active', '2026-04-19 15:39:22', '2026-04-19 15:39:22'),
(4, 'Jazelle', 'R.', 'Tamayo', '2005-02-23', 'Female', 'Salinas Dr', '2', '09205703793', 'jazellet5@gmail.com', NULL, NULL, 'uploads/government_ids/gov_id_69e49dd441d806.95207377.jpg', 'Active', '2026-04-19 17:18:12', '2026-04-19 17:18:12');

-- --------------------------------------------------------

--
-- Table structure for table `secretaryprofile`
--

CREATE TABLE `secretaryprofile` (
  `SecretaryID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `DateAssigned` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `secretaryprofile`
--

INSERT INTO `secretaryprofile` (`SecretaryID`, `UserID`, `FirstName`, `LastName`, `ContactNumber`, `DateAssigned`) VALUES
(1, 2, 'Maria', 'Santos', '09123456780', '2026-04-19 16:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `servicerequest`
--

CREATE TABLE `servicerequest` (
  `RequestID` int(11) NOT NULL,
  `ReferenceNo` varchar(30) NOT NULL,
  `ResidentID` int(11) NOT NULL,
  `RequestType` enum('Clearance','Indigency','FacilityReservation','Complaint') NOT NULL,
  `Purpose` text DEFAULT NULL,
  `Status` enum('Pending','ForApproval','Approved','Rejected','Released','Cancelled') DEFAULT 'Pending',
  `Remarks` text DEFAULT NULL,
  `ProcessedBy` int(11) DEFAULT NULL,
  `ProcessedAt` datetime DEFAULT NULL,
  `ReleasedBy` int(11) DEFAULT NULL,
  `ReleasedAt` datetime DEFAULT NULL,
  `CancelledBy` int(11) DEFAULT NULL,
  `CancelledAt` datetime DEFAULT NULL,
  `CancellationReason` text DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staffprofile`
--

CREATE TABLE `staffprofile` (
  `StaffID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `DateAssigned` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staffprofile`
--

INSERT INTO `staffprofile` (`StaffID`, `UserID`, `FirstName`, `LastName`, `ContactNumber`, `DateAssigned`) VALUES
(1, 3, 'Jose', 'Reyes', '09123456781', '2026-04-19 16:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `systemadminprofile`
--

CREATE TABLE `systemadminprofile` (
  `AdminID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `DateAssigned` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `systemadminprofile`
--

INSERT INTO `systemadminprofile` (`AdminID`, `UserID`, `FirstName`, `LastName`, `ContactNumber`, `DateAssigned`) VALUES
(1, 4, 'Tech', 'Admin', '09123456782', '2026-04-19 16:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `useraccount`
--

CREATE TABLE `useraccount` (
  `UserAccountID` int(11) NOT NULL,
  `ResidentID` int(11) DEFAULT NULL,
  `Username` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` enum('captain','secretary','staff','sysadmin','resident') NOT NULL,
  `AccountStatus` enum('Active','Inactive','PendingVerification','Rejected') DEFAULT 'PendingVerification',
  `FullName` varchar(255) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `VerifiedBy` int(11) DEFAULT NULL,
  `VerifiedAt` datetime DEFAULT NULL,
  `RejectionReason` text DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `useraccount`
--

INSERT INTO `useraccount` (`UserAccountID`, `ResidentID`, `Username`, `PasswordHash`, `Role`, `AccountStatus`, `FullName`, `Email`, `VerifiedBy`, `VerifiedAt`, `RejectionReason`, `CreatedAt`, `UpdatedAt`) VALUES
(1, NULL, 'captain', '$2y$10$xhAABu0ditVM7.IsONBstuXxtpkCOzv9CMEJQ0JRib4ZSHJtw/KoW', 'captain', 'Active', 'Hon. Juan dela Cruz', 'captain@barangay.gov.ph', NULL, NULL, NULL, '2026-04-19 15:39:22', '2026-04-19 17:07:36'),
(2, NULL, 'secretary', '$2y$10$DfsLksEHmKpoBDQenKLvIOavQ0iCZRdGYHB4V1b5HqcpBB4O49jaG', 'secretary', 'Active', 'Maria Santos', 'secretary@barangay.gov.ph', NULL, NULL, NULL, '2026-04-19 15:39:22', '2026-04-19 17:07:36'),
(3, NULL, 'staff', '$2y$10$BBGl7nQe/8AszC9OaOa.FezcWUJuYAwZ5adx7LuNsbAW/TN4HEIpO', 'staff', 'Active', 'Jose Reyes', 'staff@barangay.gov.ph', NULL, NULL, NULL, '2026-04-19 15:39:22', '2026-04-19 17:07:37'),
(4, NULL, 'sysadmin', '$2y$10$w3M8zLZF5/t3ez/HPQie1ufgb95jZYDCHHP/.l/n4NTUkS/gQw7VO', 'sysadmin', 'Active', 'Tech Admin', 'sysadmin@barangay.gov.ph', NULL, NULL, NULL, '2026-04-19 15:39:22', '2026-04-19 17:07:37'),
(10, 4, 'jazelletamayo', '$2y$10$pG5hIkzqaW7Xi9Vm4J1t9OSsSb07tfVquT5rS/1vbf3i4EAU4DMWi', 'resident', 'Active', 'Jazelle Tamayo', 'jazellet5@gmail.com', 2, '2026-04-19 17:19:30', NULL, '2026-04-19 17:18:12', '2026-04-19 17:19:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `UserAccountID` (`UserAccountID`);

--
-- Indexes for table `captainprofile`
--
ALTER TABLE `captainprofile`
  ADD PRIMARY KEY (`CaptainID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `complaint`
--
ALTER TABLE `complaint`
  ADD PRIMARY KEY (`ComplaintID`),
  ADD UNIQUE KEY `RequestID` (`RequestID`),
  ADD KEY `RespondentResidentID` (`RespondentResidentID`);

--
-- Indexes for table `facility`
--
ALTER TABLE `facility`
  ADD PRIMARY KEY (`FacilityID`);

--
-- Indexes for table `facilityreservation`
--
ALTER TABLE `facilityreservation`
  ADD PRIMARY KEY (`ReservationID`),
  ADD UNIQUE KEY `RequestID` (`RequestID`),
  ADD KEY `FacilityID` (`FacilityID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD UNIQUE KEY `ReceiptNo` (`ReceiptNo`),
  ADD KEY `RequestID` (`RequestID`),
  ADD KEY `RecordedBy` (`RecordedBy`);

--
-- Indexes for table `resident`
--
ALTER TABLE `resident`
  ADD PRIMARY KEY (`ResidentID`),
  ADD UNIQUE KEY `uq_resident` (`FirstName`,`LastName`,`Birthdate`,`Address`);

--
-- Indexes for table `secretaryprofile`
--
ALTER TABLE `secretaryprofile`
  ADD PRIMARY KEY (`SecretaryID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `servicerequest`
--
ALTER TABLE `servicerequest`
  ADD PRIMARY KEY (`RequestID`),
  ADD UNIQUE KEY `ReferenceNo` (`ReferenceNo`),
  ADD KEY `ResidentID` (`ResidentID`),
  ADD KEY `ProcessedBy` (`ProcessedBy`),
  ADD KEY `ReleasedBy` (`ReleasedBy`),
  ADD KEY `CancelledBy` (`CancelledBy`),
  ADD KEY `CreatedBy` (`CreatedBy`);

--
-- Indexes for table `staffprofile`
--
ALTER TABLE `staffprofile`
  ADD PRIMARY KEY (`StaffID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `systemadminprofile`
--
ALTER TABLE `systemadminprofile`
  ADD PRIMARY KEY (`AdminID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `useraccount`
--
ALTER TABLE `useraccount`
  ADD PRIMARY KEY (`UserAccountID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `ResidentID` (`ResidentID`),
  ADD KEY `VerifiedBy` (`VerifiedBy`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auditlog`
--
ALTER TABLE `auditlog`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `captainprofile`
--
ALTER TABLE `captainprofile`
  MODIFY `CaptainID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `complaint`
--
ALTER TABLE `complaint`
  MODIFY `ComplaintID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `facility`
--
ALTER TABLE `facility`
  MODIFY `FacilityID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `facilityreservation`
--
ALTER TABLE `facilityreservation`
  MODIFY `ReservationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resident`
--
ALTER TABLE `resident`
  MODIFY `ResidentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `secretaryprofile`
--
ALTER TABLE `secretaryprofile`
  MODIFY `SecretaryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `servicerequest`
--
ALTER TABLE `servicerequest`
  MODIFY `RequestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staffprofile`
--
ALTER TABLE `staffprofile`
  MODIFY `StaffID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `systemadminprofile`
--
ALTER TABLE `systemadminprofile`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `useraccount`
--
ALTER TABLE `useraccount`
  MODIFY `UserAccountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD CONSTRAINT `auditlog_ibfk_1` FOREIGN KEY (`UserAccountID`) REFERENCES `useraccount` (`UserAccountID`) ON DELETE SET NULL;

--
-- Constraints for table `captainprofile`
--
ALTER TABLE `captainprofile`
  ADD CONSTRAINT `captainprofile_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `useraccount` (`UserAccountID`);

--
-- Constraints for table `complaint`
--
ALTER TABLE `complaint`
  ADD CONSTRAINT `complaint_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `servicerequest` (`RequestID`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaint_ibfk_2` FOREIGN KEY (`RespondentResidentID`) REFERENCES `resident` (`ResidentID`) ON DELETE SET NULL;

--
-- Constraints for table `facilityreservation`
--
ALTER TABLE `facilityreservation`
  ADD CONSTRAINT `facilityreservation_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `servicerequest` (`RequestID`) ON DELETE CASCADE,
  ADD CONSTRAINT `facilityreservation_ibfk_2` FOREIGN KEY (`FacilityID`) REFERENCES `facility` (`FacilityID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`RequestID`) REFERENCES `servicerequest` (`RequestID`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`RecordedBy`) REFERENCES `useraccount` (`UserAccountID`) ON DELETE SET NULL;

--
-- Constraints for table `secretaryprofile`
--
ALTER TABLE `secretaryprofile`
  ADD CONSTRAINT `secretaryprofile_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `useraccount` (`UserAccountID`);

--
-- Constraints for table `servicerequest`
--
ALTER TABLE `servicerequest`
  ADD CONSTRAINT `servicerequest_ibfk_1` FOREIGN KEY (`ResidentID`) REFERENCES `resident` (`ResidentID`),
  ADD CONSTRAINT `servicerequest_ibfk_2` FOREIGN KEY (`ProcessedBy`) REFERENCES `useraccount` (`UserAccountID`) ON DELETE SET NULL,
  ADD CONSTRAINT `servicerequest_ibfk_3` FOREIGN KEY (`ReleasedBy`) REFERENCES `useraccount` (`UserAccountID`) ON DELETE SET NULL,
  ADD CONSTRAINT `servicerequest_ibfk_4` FOREIGN KEY (`CancelledBy`) REFERENCES `useraccount` (`UserAccountID`) ON DELETE SET NULL,
  ADD CONSTRAINT `servicerequest_ibfk_5` FOREIGN KEY (`CreatedBy`) REFERENCES `useraccount` (`UserAccountID`) ON DELETE SET NULL;

--
-- Constraints for table `staffprofile`
--
ALTER TABLE `staffprofile`
  ADD CONSTRAINT `staffprofile_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `useraccount` (`UserAccountID`);

--
-- Constraints for table `systemadminprofile`
--
ALTER TABLE `systemadminprofile`
  ADD CONSTRAINT `systemadminprofile_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `useraccount` (`UserAccountID`);

--
-- Constraints for table `useraccount`
--
ALTER TABLE `useraccount`
  ADD CONSTRAINT `useraccount_ibfk_1` FOREIGN KEY (`ResidentID`) REFERENCES `resident` (`ResidentID`) ON DELETE SET NULL,
  ADD CONSTRAINT `useraccount_ibfk_2` FOREIGN KEY (`VerifiedBy`) REFERENCES `useraccount` (`UserAccountID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
