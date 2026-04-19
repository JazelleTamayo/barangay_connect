<?php
// Barangay Connect – Payment Class
// classes/Payment.php

require_once __DIR__ . '/../classes/Database.php';

class Payment
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Generate a unique receipt number.
     * Format: OR-YYYYMMDD-XXXXX
     */
    public function generateReceiptNo(): string
    {
        $date = date('Ymd');
        $last = $this->db->fetchOne(
            "SELECT ReceiptNo FROM Payment
             WHERE ReceiptNo LIKE ?
             ORDER BY ReceiptNo DESC LIMIT 1",
            ["OR-{$date}-%"]
        );
        if ($last) {
            $parts = explode('-', $last['ReceiptNo']);
            $seq   = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }
        return "OR-{$date}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Record a payment for a released request.
     */
    public function record(array $data): int
    {
        $receiptNo = $this->generateReceiptNo();
        return $this->db->insert(
            "INSERT INTO Payment
                (RequestID, ReceiptNo, Amount,
                 PaymentMethod, RecordedBy)
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['request_id'],
                $receiptNo,
                $data['amount']         ?? 0.00,
                $data['payment_method'] ?? 'Cash',
                $data['recorded_by']    ?? null,
            ]
        );
    }

    /**
     * Get payment by RequestID.
     */
    public function getByRequestId(int $requestId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM Payment WHERE RequestID = ?",
            [$requestId]
        );
    }

    /**
     * Get payment history with filters.
     */
    public function getHistory(array $filters = []): array
    {
        $sql    = "SELECT p.*,
                          sr.ReferenceNo,
                          sr.RequestType,
                          CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
                   FROM Payment p
                   JOIN ServiceRequest sr ON p.RequestID  = sr.RequestID
                   JOIN Resident r        ON sr.ResidentID = r.ResidentID
                   WHERE 1=1";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql     .= " AND DATE(p.RecordedAt) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql     .= " AND DATE(p.RecordedAt) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY p.RecordedAt DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get daily collection total.
     */
    public function getDailyTotal(string $date = ''): float
    {
        $date = $date ?: date('Y-m-d');
        $row  = $this->db->fetchOne(
            "SELECT SUM(Amount) as total FROM Payment
             WHERE DATE(RecordedAt) = ?",
            [$date]
        );
        return (float) ($row['total'] ?? 0);
    }

    /**
     * Get monthly collection summary.
     */
    public function getMonthlySummary(int $year, int $month): array
    {
        return $this->db->fetchAll(
            "SELECT sr.RequestType,
                    COUNT(*) AS Count,
                    SUM(p.Amount) AS Total
             FROM Payment p
             JOIN ServiceRequest sr ON p.RequestID = sr.RequestID
             WHERE YEAR(p.RecordedAt)  = ?
               AND MONTH(p.RecordedAt) = ?
             GROUP BY sr.RequestType",
            [$year, $month]
        );
    }
}
