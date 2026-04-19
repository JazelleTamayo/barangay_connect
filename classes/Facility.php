<?php
// Barangay Connect – Facility Class
// classes/Facility.php

require_once __DIR__ . '/../classes/Database.php';

class Facility
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Get all facilities.
     */
    public function getAll(string $status = 'Active'): array
    {
        if ($status) {
            return $this->db->fetchAll(
                "SELECT * FROM Facility WHERE Status = ? ORDER BY FacilityName",
                [$status]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM Facility ORDER BY FacilityName"
        );
    }

    /**
     * Get a facility by ID.
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM Facility WHERE FacilityID = ?",
            [$id]
        );
    }

    /**
     * Create a new facility.
     */
    public function create(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO Facility
                (FacilityName, Capacity, ReservationFee,
                 Description, Status)
             VALUES (?, ?, ?, ?, 'Active')",
            [
                $data['facility_name'],
                $data['capacity']        ?? null,
                $data['reservation_fee'] ?? 0.00,
                $data['description']     ?? null,
            ]
        );
    }

    /**
     * Update a facility.
     */
    public function update(int $id, array $data): bool
    {
        $rows = $this->db->execute(
            "UPDATE Facility SET
                FacilityName   = ?,
                Capacity       = ?,
                ReservationFee = ?,
                Description    = ?,
                Status         = ?
             WHERE FacilityID = ?",
            [
                $data['facility_name'],
                $data['capacity']        ?? null,
                $data['reservation_fee'] ?? 0.00,
                $data['description']     ?? null,
                $data['status']          ?? 'Active',
                $id,
            ]
        );
        return $rows > 0;
    }

    /**
     * Get approved reservations for calendar display.
     */
    public function getApprovedReservations(int $facilityId = 0): array
    {
        $sql    = "SELECT fr.*,
                          f.FacilityName,
                          sr.ReferenceNo,
                          sr.Status,
                          CONCAT(r.FirstName,' ',r.LastName) AS ReservedBy
                   FROM FacilityReservation fr
                   JOIN Facility f        ON fr.FacilityID  = f.FacilityID
                   JOIN ServiceRequest sr ON fr.RequestID   = sr.RequestID
                   JOIN Resident r        ON sr.ResidentID  = r.ResidentID
                   WHERE sr.Status IN ('Approved','Released')";
        $params = [];

        if ($facilityId) {
            $sql     .= " AND fr.FacilityID = ?";
            $params[] = $facilityId;
        }

        $sql .= " ORDER BY fr.ReservationDate ASC";
        return $this->db->fetchAll($sql, $params);
    }
}
