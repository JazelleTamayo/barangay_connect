<?php
// Barangay Connect – Request Update Handler
// handlers/request_update_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/ServiceRequest.php';
require_once '../classes/AuditLog.php';
require_role('staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff/request_status_update.php');
    exit;
}

$request_id = (int) ($_POST['request_id'] ?? 0);
$new_status = trim($_POST['new_status']   ?? '');
$remarks    = trim($_POST['remarks']      ?? '');

if (!$request_id || empty($new_status)) {
    header('Location: ../staff/request_status_update.php?msg=invalid');
    exit;
}

// Staff can only move to ForApproval
if ($new_status !== 'ForApproval') {
    header('Location: ../staff/request_status_update.php?msg=unauthorized');
    exit;
}

$sr    = new ServiceRequest();
$audit = new AuditLog();

$sr->updateStatus(
    $request_id,
    $new_status,
    (int) $_SESSION['user_id'],
    $remarks
);

$audit->log(
    "Updated request status to $new_status",
    "RequestID: $request_id"
);

header('Location: ../staff/request_status_update.php?msg=updated');
exit;
