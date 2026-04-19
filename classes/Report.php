<?php
// Barangay Connect – Report Class
// classes/Report.php

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/constants.php';

class Report
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Daily transaction log.
     */
    public function dailyLog(string $date = ''): array
    {
        $date = $date ?: date('Y-m-d');
        return $this->db->fetchAll(
            "SELECT sr.*,
                    CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
             FROM ServiceRequest sr
             JOIN Resident r ON sr.ResidentID = r.ResidentID
             WHERE DATE(sr.CreatedAt) = ?
             ORDER BY sr.CreatedAt DESC",
            [$date]
        );
    }

    /**
     * Weekly pending report — requests past SLA.
     */
    public function weeklyPending(): array
    {
        return $this->db->fetchAll(
            "SELECT sr.*,
                    CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
                    TIMESTAMPDIFF(HOUR, sr.CreatedAt, NOW()) AS HoursElapsed
             FROM ServiceRequest sr
             JOIN Resident r ON sr.ResidentID = r.ResidentID
             WHERE sr.Status IN ('Pending','ForApproval')
               AND (
                 (sr.RequestType = 'Clearance'
                    AND TIMESTAMPDIFF(HOUR, sr.CreatedAt, NOW()) > ?)
                 OR (sr.RequestType = 'Indigency'
                    AND TIMESTAMPDIFF(HOUR, sr.CreatedAt, NOW()) > ?)
                 OR (sr.RequestType = 'FacilityReservation'
                    AND TIMESTAMPDIFF(HOUR, sr.CreatedAt, NOW()) > ?)
                 OR (sr.RequestType = 'Complaint'
                    AND TIMESTAMPDIFF(HOUR, sr.CreatedAt, NOW()) > ?)
               )
             ORDER BY sr.CreatedAt ASC",
            [
                SLA_CLEARANCE,
                SLA_INDIGENCY,
                SLA_RESERVATION,
                SLA_COMPLAINT
            ]
        );
    }

    /**
     * Monthly summary of service requests.
     */
    public function monthlySummary(int $year, int $month): array
    {
        return $this->db->fetchAll(
            "SELECT RequestType,
                    COUNT(*) AS Total,
                    SUM(CASE WHEN Status = 'Released'  THEN 1 ELSE 0 END) AS Released,
                    SUM(CASE WHEN Status = 'Rejected'  THEN 1 ELSE 0 END) AS Rejected,
                    SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) AS Cancelled,
                    AVG(TIMESTAMPDIFF(HOUR, CreatedAt, UpdatedAt)) AS AvgHours
             FROM ServiceRequest
             WHERE YEAR(CreatedAt)  = ?
               AND MONTH(CreatedAt) = ?
             GROUP BY RequestType",
            [$year, $month]
        );
    }

    /**
     * Complaint summary report.
     */
    public function complaintSummary(): array
    {
        return $this->db->fetchAll(
            "SELECT sr.Status,
                    COUNT(*) AS Count,
                    AVG(TIMESTAMPDIFF(DAY,
                        sr.CreatedAt, sr.UpdatedAt)) AS AvgDays
             FROM ServiceRequest sr
             WHERE sr.RequestType = 'Complaint'
             GROUP BY sr.Status"
        );
    }

    /**
     * Facility utilization report.
     */
    public function facilityUtilization(): array
    {
        return $this->db->fetchAll(
            "SELECT f.FacilityName,
                    COUNT(fr.ReservationID) AS TotalReservations,
                    SUM(CASE WHEN sr.Status IN ('Approved','Released')
                        THEN 1 ELSE 0 END) AS ApprovedReservations
             FROM Facility f
             LEFT JOIN FacilityReservation fr ON f.FacilityID = fr.FacilityID
             LEFT JOIN ServiceRequest sr      ON fr.RequestID = sr.RequestID
             GROUP BY f.FacilityID, f.FacilityName
             ORDER BY TotalReservations DESC"
        );
    }

    /**
     * Staff performance report.
     */
    public function staffPerformance(): array
    {
        return $this->db->fetchAll(
            "SELECT ua.FullName,
                    ua.Role,
                    COUNT(sr.RequestID) AS TotalProcessed,
                    AVG(TIMESTAMPDIFF(HOUR,
                        sr.CreatedAt, sr.ProcessedAt)) AS AvgHours
             FROM UserAccount ua
             LEFT JOIN ServiceRequest sr ON ua.UserAccountID = sr.ProcessedBy
             WHERE ua.Role IN ('secretary','staff')
             GROUP BY ua.UserAccountID, ua.FullName, ua.Role
             ORDER BY TotalProcessed DESC"
        );
    }
}
