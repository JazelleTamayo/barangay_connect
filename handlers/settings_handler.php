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

$brgy_name    = trim($_POST['brgy_name']    ?? '');
$municipality = trim($_POST['municipality'] ?? '');
$contact      = trim($_POST['contact']      ?? '');
$email        = trim($_POST['email']        ?? '');
$clearance_fee = (float) ($_POST['clearance_fee'] ?? 0);
$maintenance  = (int) ($_POST['maintenance'] ?? 0);

$audit = new AuditLog();

// TODO: Save settings to a settings table or config file
// For now we just log and redirect
$audit->log("Updated system settings");

header('Location: ../sysadmin/system_settings.php?msg=saved');
exit;
