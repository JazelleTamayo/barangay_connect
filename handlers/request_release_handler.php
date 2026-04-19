<?php
// Barangay Connect – Request Release Handler
// handlers/request_release_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/ServiceRequest.php';
require_once '../classes/AuditLog.php';
require_role('secretary');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../secretary/document_release.php');
    exit;
}

$request_id = (int) ($_POST['request_id'] ?? 0);
$remarks    = trim($_POST['remarks']      ?? '');

if (!$request_id) {
    header('Location: ../secretary/document_release.php?msg=invalid');
    exit;
}

$sr      = new ServiceRequest();
$audit   = new AuditLog();
$request = $sr->getById($request_id);

if (!$request) {
    header('Location: ../secretary/document_release.php?msg=not_found');
    exit;
}

if ($request['Status'] !== 'Approved') {
    header('Location: ../secretary/document_release.php?msg=not_approved');
    exit;
}

$sr->updateStatus(
    $request_id,
    'Released',
    (int) $_SESSION['user_id'],
    $remarks
);

$audit->log(
    "Released document",
    "RequestID: $request_id"
);

header('Location: ../secretary/document_release.php?msg=released');
exit;
