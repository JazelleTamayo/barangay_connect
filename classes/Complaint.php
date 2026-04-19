<?php
// Barangay Connect – Complaint Class
// classes/Complaint.php

require_once __DIR__ . '/../classes/Database.php';

class Complaint
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Get all complaints with request and resident info.
     */
    public function getAll(string $status = ''): array
    {
        $sql    = "SELECT c.*,
                          sr.ReferenceNo,
                          sr.Status,
                          sr.CreatedAt,
                          CONCAT(r.FirstName,' ',r.LastName) AS ComplainantName
                   FROM Complaint c
                   JOIN ServiceRequest sr ON c.RequestID = sr.RequestID
                   JOIN Resident r        ON sr.ResidentID = r.ResidentID
                   WHERE 1=1";
        $params = [];

        if ($status) {
            $sql     .= " AND sr.Status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY sr.CreatedAt DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get a single complaint by RequestID.
     */
    public function getByRequestId(int $requestId): ?array
    {
        return $this->db->fetchOne(
            "SELECT c.*,
                    sr.ReferenceNo,
                    sr.Status,
                    CONCAT(r.FirstName,' ',r.LastName) AS ComplainantName
             FROM Complaint c
             JOIN ServiceRequest sr ON c.RequestID = sr.RequestID
             JOIN Resident r        ON sr.ResidentID = r.ResidentID
             WHERE c.RequestID = ?",
            [$requestId]
        );
    }

    /**
     * Create a new complaint record.
     */
    public function create(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO Complaint
                (RequestID, RespondentName, RespondentContact,
                 RespondentResidentID, IncidentDate,
                 IncidentLocation, Description)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['request_id'],
                $data['respondent_name']       ?? null,
                $data['respondent_contact']    ?? null,
                $data['respondent_resident_id'] ?? null,
                $data['incident_date']         ?? null,
                $data['incident_location']     ?? null,
                $data['description']           ?? null,
            ]
        );
    }

    /**
     * Update complaint mediation and actions.
     */
    public function update(int $requestId, array $data): bool
    {
        $rows = $this->db->execute(
            "UPDATE Complaint SET
                MediationDate = ?,
                ActionsTaken  = ?
             WHERE RequestID = ?",
            [
                $data['mediation_date'] ?? null,
                $data['actions_taken']  ?? null,
                $requestId,
            ]
        );
        return $rows > 0;
    }
}
