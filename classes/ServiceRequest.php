<?php
// Barangay Connect – ServiceRequest Class
// classes/ServiceRequest.php

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/constants.php';

class ServiceRequest
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Generate a unique reference number.
     * Format: BRGY-YYYYMMDD-XXXXX
     */
    public function generateReferenceNo(): string
    {
        $date = date('Ymd');
        $last = $this->db->fetchOne(
            "SELECT ReferenceNo FROM ServiceRequest
             WHERE ReferenceNo LIKE ?
             ORDER BY ReferenceNo DESC LIMIT 1",
            ["BRGY-{$date}-%"]
        );
        if ($last) {
            $parts  = explode('-', $last['ReferenceNo']);
            $seq    = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }
        return "BRGY-{$date}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get all requests with optional filters.
     */
    public function getAll(array $filters = []): array
    {
        $sql    = "SELECT sr.*,
                          CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
                   FROM ServiceRequest sr
                   JOIN Resident r ON sr.ResidentID = r.ResidentID
                   WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql     .= " AND sr.Status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $sql     .= " AND sr.RequestType = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['resident_id'])) {
            $sql     .= " AND sr.ResidentID = ?";
            $params[] = $filters['resident_id'];
        }

        $sql .= " ORDER BY sr.CreatedAt DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get a single request by ID.
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT sr.*,
                    CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
             FROM ServiceRequest sr
             JOIN Resident r ON sr.ResidentID = r.ResidentID
             WHERE sr.RequestID = ?",
            [$id]
        );
    }

    /**
     * Get a request by reference number.
     */
    public function getByReferenceNo(string $refNo): ?array
    {
        return $this->db->fetchOne(
            "SELECT sr.*,
                    CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
             FROM ServiceRequest sr
             JOIN Resident r ON sr.ResidentID = r.ResidentID
             WHERE sr.ReferenceNo = ?",
            [$refNo]
        );
    }

    /**
     * Create a new service request.
     * Returns new RequestID.
     */
    public function create(array $data): int
    {
        $refNo = $this->generateReferenceNo();
        return $this->db->insert(
            "INSERT INTO ServiceRequest
                (ReferenceNo, ResidentID, RequestType,
                 Purpose, Status, CreatedBy)
             VALUES (?, ?, ?, ?, 'ForApproval', ?)",
            [
                $refNo,
                $data['resident_id'],
                $data['request_type'],
                $data['purpose']    ?? null,
                $data['created_by'] ?? null,
            ]
        );
    }

    /**
     * Update request status.
     */
    public function updateStatus(
        int $id,
        string $status,
        int $userId,
        string $remarks = ''
    ): bool {
        $sql    = "UPDATE ServiceRequest SET Status = ?, Remarks = ?";
        $params = [$status, $remarks];

        if ($status === STATUS_RELEASED) {
            $sql     .= ", ReleasedBy = ?, ReleasedAt = NOW()";
            $params[] = $userId;
        } elseif ($status === STATUS_CANCELLED) {
            $sql     .= ", CancelledBy = ?, CancelledAt = NOW(),
                          CancellationReason = ?";
            $params[] = $userId;
            $params[] = $remarks;
        } else {
            $sql     .= ", ProcessedBy = ?, ProcessedAt = NOW()";
            $params[] = $userId;
        }

        $sql     .= " WHERE RequestID = ?";
        $params[] = $id;

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Check if a facility is already booked on a date.
     */
    public function isFacilityBooked(
        int $facilityId,
        string $date,
        int $excludeId = 0
    ): bool {
        $row = $this->db->fetchOne(
            "SELECT fr.ReservationID
             FROM FacilityReservation fr
             JOIN ServiceRequest sr ON fr.RequestID = sr.RequestID
             WHERE fr.FacilityID      = ?
               AND fr.ReservationDate = ?
               AND sr.Status NOT IN ('Rejected','Cancelled')
               AND fr.ReservationID  != ?",
            [$facilityId, $date, $excludeId]
        );
        return $row !== null;
    }

    /**
     * Get overdue requests (past SLA).
     */
    public function getOverdue(): array
    {
        return $this->db->fetchAll(
            "SELECT sr.*,
                    CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
                    TIMESTAMPDIFF(HOUR, sr.CreatedAt, NOW()) AS HoursElapsed
             FROM ServiceRequest sr
             JOIN Resident r ON sr.ResidentID = r.ResidentID
             WHERE sr.Status IN ('Pending','ForApproval')
               AND (
                 (sr.RequestType = 'Clearance'          AND TIMESTAMPDIFF(HOUR,  sr.CreatedAt, NOW()) > ?)
                 OR (sr.RequestType = 'Indigency'        AND TIMESTAMPDIFF(HOUR,  sr.CreatedAt, NOW()) > ?)
                 OR (sr.RequestType = 'FacilityReservation' AND TIMESTAMPDIFF(HOUR, sr.CreatedAt, NOW()) > ?)
                 OR (sr.RequestType = 'Complaint'        AND TIMESTAMPDIFF(HOUR,  sr.CreatedAt, NOW()) > ?)
               )
             ORDER BY sr.CreatedAt ASC",
            [SLA_CLEARANCE, SLA_INDIGENCY, SLA_RESERVATION, SLA_COMPLAINT]
        );
    }
}
