<?php
// Barangay Connect – Override Handler
// handlers/override_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/ServiceRequest.php';
require_once '../classes/AuditLog.php';
require_role('captain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../captain/system_override.php');
    exit;
}

$reference_no = trim($_POST['reference_no'] ?? '');
$new_status   = trim($_POST['new_status']   ?? '');
$reason       = trim($_POST['reason']       ?? '');

if (empty($reference_no) || empty($new_status) || empty($reason)) {
    header('Location: ../captain/system_override.php?msg=missing_fields');
    exit;
}

$sr      = new ServiceRequest();
$audit   = new AuditLog();
$request = $sr->getByReferenceNo($reference_no);

if (!$request) {
    header('Location: ../captain/system_override.php?msg=not_found');
    exit;
}

$sr->updateStatus(
    $request['RequestID'],
    $new_status,
    (int) $_SESSION['user_id'],
    $reason
);

$audit->log(
    "CAPTAIN OVERRIDE: Status changed to $new_status | Reason: $reason",
    "ReferenceNo: $reference_no"
);

header('Location: ../captain/system_override.php?msg=success');
exit;
