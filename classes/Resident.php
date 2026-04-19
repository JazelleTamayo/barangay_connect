<?php
// Barangay Connect – Resident Class
// classes/Resident.php

require_once __DIR__ . '/../classes/Database.php';

class Resident
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Get all residents with optional status filter.
     */
    public function getAll(string $status = ''): array
    {
        if ($status) {
            return $this->db->fetchAll(
                "SELECT * FROM Resident WHERE Status = ? ORDER BY LastName, FirstName",
                [$status]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM Resident ORDER BY LastName, FirstName"
        );
    }

    /**
     * Get a single resident by ID.
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM Resident WHERE ResidentID = ?",
            [$id]
        );
    }

    /**
     * Search residents by name or purok.
     */
    public function search(string $keyword): array
    {
        $like = '%' . $keyword . '%';
        return $this->db->fetchAll(
            "SELECT * FROM Resident
             WHERE FirstName  LIKE ?
                OR LastName   LIKE ?
                OR MiddleName LIKE ?
                OR Purok      LIKE ?
             ORDER BY LastName, FirstName",
            [$like, $like, $like, $like]
        );
    }

    /**
     * Check if a resident already exists (duplicate check).
     */
    public function isDuplicate(
        string $firstName,
        string $lastName,
        string $birthdate,
        string $address,
        int $excludeId = 0
    ): bool {
        $row = $this->db->fetchOne(
            "SELECT ResidentID FROM Resident
             WHERE FirstName = ? AND LastName = ?
               AND Birthdate = ? AND Address = ?
               AND ResidentID != ?",
            [$firstName, $lastName, $birthdate, $address, $excludeId]
        );
        return $row !== null;
    }

    /**
     * Create a new resident record.
     * Returns the new ResidentID.
     */
    public function create(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO Resident
                (FirstName, MiddleName, LastName, Birthdate, Sex,
                 Address, Purok, ContactNumber, Email,
                 GovIDType, GovIDNumber, GovIDImagePath, Status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')",
            [
                $data['first_name'],
                $data['middle_name']   ?? null,
                $data['last_name'],
                $data['birthdate'],
                $data['sex'],
                $data['address'],
                $data['purok']         ?? null,
                $data['contact']       ?? null,
                $data['email']         ?? null,
                $data['gov_id_type']   ?? null,
                $data['gov_id_number'] ?? null,
                $data['gov_id_path']   ?? null,
            ]
        );
    }

    /**
     * Update an existing resident record.
     */
    public function update(int $id, array $data): bool
    {
        $rows = $this->db->execute(
            "UPDATE Resident SET
                FirstName     = ?,
                MiddleName    = ?,
                LastName      = ?,
                Birthdate     = ?,
                Sex           = ?,
                Address       = ?,
                Purok         = ?,
                ContactNumber = ?,
                Email         = ?,
                GovIDType     = ?,
                GovIDNumber   = ?,
                Status        = ?
             WHERE ResidentID = ?",
            [
                $data['first_name'],
                $data['middle_name']   ?? null,
                $data['last_name'],
                $data['birthdate'],
                $data['sex'],
                $data['address'],
                $data['purok']         ?? null,
                $data['contact']       ?? null,
                $data['email']         ?? null,
                $data['gov_id_type']   ?? null,
                $data['gov_id_number'] ?? null,
                $data['status']        ?? 'Active',
                $id,
            ]
        );
        return $rows > 0;
    }

    /**
     * Mark a resident as inactive.
     */
    public function deactivate(int $id): bool
    {
        $rows = $this->db->execute(
            "UPDATE Resident SET Status = 'Inactive' WHERE ResidentID = ?",
            [$id]
        );
        return $rows > 0;
    }

    /**
     * Check if resident is in good standing for Clearance.
     * Good standing = no pending complaint as respondent
     * and no ordinance violations in last 6 months.
     */
    public function isInGoodStanding(int $residentId): bool
    {
        $complaint = $this->db->fetchOne(
            "SELECT c.ComplaintID
             FROM Complaint c
             JOIN ServiceRequest sr ON c.RequestID = sr.RequestID
             WHERE c.RespondentResidentID = ?
               AND sr.Status NOT IN ('Rejected','Cancelled')",
            [$residentId]
        );
        return $complaint === null;
    }

    /**
     * Get demographics summary.
     */
    public function getDemographics(): array
    {
        return [
            'total'    => $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM Resident WHERE Status = 'Active'"
            )['count'] ?? 0,
            'by_sex'   => $this->db->fetchAll(
                "SELECT Sex, COUNT(*) as count FROM Resident
                 WHERE Status = 'Active' GROUP BY Sex"
            ),
            'by_purok' => $this->db->fetchAll(
                "SELECT Purok, COUNT(*) as count FROM Resident
                 WHERE Status = 'Active' GROUP BY Purok ORDER BY Purok"
            ),
        ];
    }
}
