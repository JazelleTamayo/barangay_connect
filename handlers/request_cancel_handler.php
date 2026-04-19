<?php
// Barangay Connect – Request Cancel Handler
// handlers/request_cancel_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/ServiceRequest.php';
require_once '../classes/AuditLog.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$request_id = (int) ($_POST['request_id'] ?? 0);
$reason     = trim($_POST['reason']       ?? '');
$role       = $_SESSION['role']           ?? '';

if (!$request_id || empty($reason)) {
    header('Location: ../resident/track_request.php?msg=missing_fields');
    exit;
}

$sr      = new ServiceRequest();
$audit   = new AuditLog();
$request = $sr->getById($request_id);

if (!$request) {
    header('Location: ../resident/track_request.php?msg=not_found');
    exit;
}

// Residents can only cancel their own pending requests
if (
    $role === 'resident' &&
    $request['Status'] !== 'Pending'
) {
    header('Location: ../resident/track_request.php?msg=cannot_cancel');
    exit;
}

$sr->updateStatus(
    $request_id,
    'Cancelled',
    (int) $_SESSION['user_id'],
    $reason
);

$audit->log(
    "Cancelled request",
    "RequestID: $request_id | Reason: $reason"
);

// Redirect based on role
if ($role === 'resident') {
    header('Location: ../resident/track_request.php?msg=cancelled');
} else {
    header('Location: ../secretary/request_processing.php?msg=cancelled');
}
exit;
