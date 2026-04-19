<?php
// Barangay Connect – AuditLog Class
// classes/AuditLog.php

require_once __DIR__ . '/../classes/Database.php';

class AuditLog
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Log an action.
     */
    public function log(
        string $action,
        string $recordAffected = ''
    ): void {
        $userId   = $_SESSION['user_id']  ?? null;
        $username = $_SESSION['username'] ?? 'system';
        $role     = $_SESSION['role']     ?? 'unknown';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? null;

        $this->db->execute(
            "INSERT INTO AuditLog
                (UserAccountID, Username, Role,
                 Action, RecordAffected, IPAddress)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $username,
                $role,
                $action,
                $recordAffected,
                $ip
            ]
        );
    }

    /**
     * Get all log entries with optional filters.
     */
    public function getAll(array $filters = []): array
    {
        $sql    = "SELECT * FROM AuditLog WHERE 1=1";
        $params = [];

        if (!empty($filters['username'])) {
            $sql     .= " AND Username LIKE ?";
            $params[] = '%' . $filters['username'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $sql     .= " AND DATE(LoggedAt) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql     .= " AND DATE(LoggedAt) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY LoggedAt DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get recent activity (last N entries).
     */
    public function getRecent(int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM AuditLog
             ORDER BY LoggedAt DESC
             LIMIT ?",
            [$limit]
        );
    }
}
