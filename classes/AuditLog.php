<?php
// Barangay Connect – AuditLog Class
// classes/AuditLog.php
// FIXED: Added archiveOldLogs() method for BR-11 retention requirement.

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

    /**
     * Delete log entries older than the specified number of days.
     * BR-11: Logs must be retained for a minimum of 1 year (365 days).
     * This method enforces that floor — it will not delete logs newer than 365 days
     * regardless of the $days argument.
     *
     * @param  int $days  Number of days to retain. Minimum enforced: 365.
     * @return int        Number of rows deleted.
     */
    public function archiveOldLogs(int $days = 365): int
    {
        // Enforce minimum 1-year retention per BR-11
        $days = max(365, $days);

        $this->db->execute(
            "DELETE FROM AuditLog
             WHERE LoggedAt < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );

        // Return count of remaining rows as a sanity check
        $result = $this->db->fetchOne("SELECT ROW_COUNT() AS deleted");
        return (int)($result['deleted'] ?? 0);
    }
}
