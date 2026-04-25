<?php
// config/settings.php

require_once __DIR__ . '/db.php';

function load_settings(): array
{
    global $pdo;

    $defaults = [
        'barangay_name'    => '',
        'municipality'     => '',
        'contact'          => '',
        'email'            => '',
        'clearance_fee'    => '0',
        'maintenance_mode' => '0',
    ];

    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM SystemSettings");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_merge($defaults, $rows);
    } catch (PDOException $e) {
        error_log('load_settings() failed: ' . $e->getMessage());
        return $defaults;
    }
}