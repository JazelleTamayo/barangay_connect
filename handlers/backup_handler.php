<?php
// Barangay Connect – Backup Handler
// handlers/backup_handler.php
// REWRITTEN: pure PHP/PDO backup — no mysqldump needed, works on all XAMPP setups

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/AuditLog.php';
require_role('sysadmin');

$backup_dir = __DIR__ . '/../backups/';

// ── Download ──────────────────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'download') {
    $file     = basename($_GET['file'] ?? '');
    $filepath = $backup_dir . $file;
    if ($file && file_exists($filepath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
    header('Location: ../sysadmin/backup.php');
    exit;
}

// ── Delete ────────────────────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'delete') {
    $file     = basename($_GET['file'] ?? '');
    $filepath = $backup_dir . $file;
    if ($file && file_exists($filepath)) {
        unlink($filepath);
    }
    header('Location: ../sysadmin/backup.php?msg=deleted');
    exit;
}

// ── Create Backup (POST only) ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../sysadmin/backup.php');
    exit;
}

try {
    $pdo      = get_db();
    $audit    = new AuditLog();
    $db_name  = DB_NAME;
    $filename = "backup_{$db_name}_" . date('Ymd_His') . ".sql";
    $filepath = $backup_dir . $filename;

    // Create backups directory if needed
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    $output   = [];
    $output[] = "-- Barangay Connect Database Backup";
    $output[] = "-- Generated: " . date('Y-m-d H:i:s');
    $output[] = "-- Database: {$db_name}";
    $output[] = "";
    $output[] = "SET FOREIGN_KEY_CHECKS=0;";
    $output[] = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';";
    $output[] = "";

    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $output[] = "-- --------------------------------------------------------";
        $output[] = "-- Table: `{$table}`";
        $output[] = "-- --------------------------------------------------------";
        $output[] = "DROP TABLE IF EXISTS `{$table}`;";

        $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $output[]  = $createRow['Create Table'] . ";";
        $output[]  = "";

        // Insert data rows
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $output[] = "-- Data for table `{$table}`";
            foreach ($rows as $row) {
                $values = array_map(function ($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, array_values($row));
                $cols     = implode('`, `', array_keys($row));
                $vals     = implode(', ', $values);
                $output[] = "INSERT INTO `{$table}` (`{$cols}`) VALUES ({$vals});";
            }
            $output[] = "";
        }
    }

    $output[] = "SET FOREIGN_KEY_CHECKS=1;";
    $output[] = "";
    $output[] = "-- End of backup";

    // Write file
    file_put_contents($filepath, implode("\n", $output));

    $audit->log("Database backup created", "File: $filename");
    header('Location: ../sysadmin/backup.php?msg=success');
} catch (Exception $e) {
    header('Location: ../sysadmin/backup.php?msg=failed');
}
exit;
