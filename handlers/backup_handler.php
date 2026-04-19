<?php
// Barangay Connect – Backup Handler
// handlers/backup_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/AuditLog.php';
require_role('sysadmin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../sysadmin/backup.php');
    exit;
}

$audit       = new AuditLog();
$backup_dir  = __DIR__ . '/../database/backups/';
$db_name     = DB_NAME;
$db_user     = DB_USER;
$db_pass     = DB_PASS;
$db_host     = DB_HOST;
$filename    = "backup_{$db_name}_" . date('Ymd_His') . ".sql";
$filepath    = $backup_dir . $filename;

// Create backups directory if it doesn't exist
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Run mysqldump
$command = "mysqldump --user={$db_user} " .
    ($db_pass ? "--password={$db_pass} " : '') .
    "--host={$db_host} {$db_name} > \"{$filepath}\" 2>&1";

exec($command, $output, $return_code);

if ($return_code === 0) {
    $audit->log("Database backup created", "File: $filename");
    header('Location: ../sysadmin/backup.php?msg=success');
} else {
    header('Location: ../sysadmin/backup.php?msg=failed');
}
exit;
