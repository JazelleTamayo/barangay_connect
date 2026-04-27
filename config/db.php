<?php
// Barangay Connect – Database Connection
// config/db.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'barangay_connect');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('
                <div style="font-family:\'DM Sans\',sans-serif;padding:40px;background:#f0faf4;min-height:100vh;">
                    <div style="max-width:500px;margin:auto;background:#fff;padding:32px;border-radius:14px;border-left:5px solid #2d6a4f;">
                        <h2 style="color:#1a4731;margin-bottom:12px;">Database Connection Failed</h2>
                        <p style="color:#3d5a47;">Could not connect to MySQL. Please make sure XAMPP is running and the database is set up.</p>
                        <p style="color:#6b8f76;font-size:14px;margin-top:12px;">Check <code>config/db.php</code> and verify your database credentials.</p>
                        <p style="color:#c0392b;font-size:13px;margin-top:8px;">' . htmlspecialchars($e->getMessage()) . '</p>
                    </div>
                </div>
            ');
        }
    }
    return $pdo;
}

// Global $pdo available to all files that include config/db.php
$pdo = get_db();
