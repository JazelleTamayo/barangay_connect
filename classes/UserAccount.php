<?php
// Barangay Connect – UserAccount Class
// classes/UserAccount.php

require_once __DIR__ . '/../classes/Database.php';

class UserAccount
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Find a user by username.
     */
    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM UserAccount WHERE Username = ? LIMIT 1",
            [$username]
        );
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM UserAccount WHERE UserAccountID = ?",
            [$id]
        );
    }

    /**
     * Get all accounts with optional role filter.
     */
    public function getAll(string $role = ''): array
    {
        if ($role) {
            return $this->db->fetchAll(
                "SELECT * FROM UserAccount WHERE Role = ? ORDER BY FullName",
                [$role]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM UserAccount ORDER BY Role, FullName"
        );
    }

    /**
     * Get all pending verification accounts.
     */
    public function getPending(): array
    {
        return $this->db->fetchAll(
            "SELECT ua.*, r.FirstName, r.LastName, r.GovIDImagePath
             FROM UserAccount ua
             LEFT JOIN Resident r ON ua.ResidentID = r.ResidentID
             WHERE ua.AccountStatus = 'PendingVerification'
             ORDER BY ua.CreatedAt ASC"
        );
    }

    /**
     * Create a new user account.
     * Returns new UserAccountID.
     */
    public function create(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        return $this->db->insert(
            "INSERT INTO UserAccount
                (ResidentID, Username, PasswordHash, Role,
                 AccountStatus, FullName, Email)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['resident_id']    ?? null,
                $data['username'],
                $hash,
                $data['role'],
                $data['status']         ?? 'Active',
                $data['full_name']      ?? null,
                $data['email']          ?? null,
            ]
        );
    }

    /**
     * Approve a pending account.
     */
    public function approve(int $id, int $verifiedBy): bool
    {
        // If verifiedBy is 0 or invalid, skip setting it
        $rows = $this->db->execute(
            "UPDATE UserAccount SET
            AccountStatus = 'Active',
            VerifiedBy    = NULLIF(?, 0),
            VerifiedAt    = NOW()
         WHERE UserAccountID = ?",
            [$verifiedBy, $id]
        );
        return $rows > 0;
    }

    /**
     * Reject a pending account.
     */
    public function reject(
        int $id,
        int $verifiedBy,
        string $reason
    ): bool {
        $rows = $this->db->execute(
            "UPDATE UserAccount SET
            AccountStatus   = 'Rejected',
            VerifiedBy      = NULLIF(?, 0),
            VerifiedAt      = NOW(),
            RejectionReason = ?
         WHERE UserAccountID = ?",
            [$verifiedBy, $reason, $id]
        );
        return $rows > 0;
    }

    /**
     * Disable an account.
     */
    public function disable(int $id): bool
    {
        $rows = $this->db->execute(
            "UPDATE UserAccount SET AccountStatus = 'Inactive'
             WHERE UserAccountID = ?",
            [$id]
        );
        return $rows > 0;
    }

    /**
     * Enable an account.
     */
    public function enable(int $id): bool
    {
        $rows = $this->db->execute(
            "UPDATE UserAccount SET AccountStatus = 'Active'
             WHERE UserAccountID = ?",
            [$id]
        );
        return $rows > 0;
    }

    /**
     * Change password.
     */
    public function changePassword(
        int $id,
        string $newPassword
    ): bool {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $rows = $this->db->execute(
            "UPDATE UserAccount SET PasswordHash = ?
             WHERE UserAccountID = ?",
            [$hash, $id]
        );
        return $rows > 0;
    }

    /**
     * Verify current password.
     */
    public function verifyPassword(
        int $id,
        string $password
    ): bool {
        $user = $this->findById($id);
        if (!$user) return false;
        return password_verify($password, $user['PasswordHash']);
    }

    /**
     * Permanently delete a user account.
     */
    public function deleteAccount(int $id): bool
    {
        $rows = $this->db->execute(
            "DELETE FROM useraccount WHERE UserAccountID = ?",
            [$id]
        );
        return $rows > 0;
    }
}
