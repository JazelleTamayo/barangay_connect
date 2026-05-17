<?php
// Barangay Connect – Captain Approval Action Handler
// handlers/approval_action_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../classes/ServiceRequest.php';
require_once '../classes/AuditLog.php';
require_role('captain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../captain/final_approvals.php');
    exit;
}

csrf_verify(); // ADDED: CSRF protection

$id      = (int)  ($_POST['request_id'] ?? 0);
$action  = trim(  $_POST['action']      ?? '');
$remarks = trim(  $_POST['remarks']     ?? '');

if (!$id || !in_array($action, ['approve', 'reject']) || empty($remarks)) {
    header('Location: ../captain/final_approvals.php?msg=invalid');
    exit;
}

$sr      = new ServiceRequest();
$audit   = new AuditLog();
$request = $sr->getById($id);

if (!$request) {
    header('Location: ../captain/final_approvals.php?msg=not_found');
    exit;
}

if ($request['Status'] !== 'ForApproval') {
    header('Location: ../captain/final_approvals.php?msg=invalid');
    exit;
}

$new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
$label      = ($action === 'approve') ? 'Captain Approved' : 'Captain Rejected';

$existing  = $request['Remarks'] ?? '';
$timestamp = date('[Y-m-d H:i:s]');
$full_remarks = $existing
    ? $existing . "\n$timestamp $label: $remarks"
    : "$timestamp $label: $remarks";

$sr->updateStatus($id, $new_status, (int) $_SESSION['user_id'], $full_remarks);

$audit->log(
    "Captain " . ucfirst($action) . "d request — $remarks",
    "RequestID: $id | ReferenceNo: " . ($request['ReferenceNo'] ?? '')
);

header('Location: ../captain/final_approvals.php?msg=' . $action . 'd');
exit;
