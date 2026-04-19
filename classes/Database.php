<?php
// Barangay Connect – Database Class
// classes/Database.php

require_once __DIR__ . '/../config/db.php';

class Database
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_db();
    }

    /**
     * Run a query and return all rows.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Run a query and return a single row.
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Run an INSERT, UPDATE, or DELETE query.
     * Returns number of affected rows.
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Run an INSERT and return the last inserted ID.
     */
    public function insert(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Get the raw PDO instance.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
