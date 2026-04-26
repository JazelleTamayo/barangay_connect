<?php
// Barangay Connect – Settings Handler
// handlers/settings_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/AuditLog.php';
require_role('sysadmin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../sysadmin/system_settings.php');
    exit;
}

$brgy_name     = trim($_POST['brgy_name']     ?? '');
$municipality  = trim($_POST['municipality']  ?? '');
$contact       = trim($_POST['contact']       ?? '');
$email         = trim($_POST['email']         ?? '');
$clearance_fee = number_format((float)($_POST['clearance_fee'] ?? 0), 2, '.', '');
$maintenance   = in_array($_POST['maintenance'] ?? '0', ['0','1']) ? $_POST['maintenance'] : '0';

$pairs = [
    'barangay_name'    => $brgy_name,
    'municipality'     => $municipality,
    'contact'          => $contact,
    'email'            => $email,
    'clearance_fee'    => $clearance_fee,
    'maintenance_mode' => $maintenance,
];

try {
    $sql = "INSERT INTO systemsettings (`key`, `value`)
                VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE
                `value`    = VALUES(`value`),
                updated_at = CURRENT_TIMESTAMP";

    $stmt = $pdo->prepare($sql);

    $pdo->beginTransaction();
    foreach ($pairs as $key => $value) {
        $stmt->execute([':key' => $key, ':value' => $value]);
    }
    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('settings_handler error: ' . $e->getMessage());
    header('Location: ../sysadmin/system_settings.php?msg=error');
    exit;
}

$audit = new AuditLog();
$audit->log("Updated system settings");

header('Location: ../sysadmin/system_settings.php?msg=saved');
exit;