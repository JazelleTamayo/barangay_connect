<?php
// Barangay Connect – Captain Approval Action
// captain/approval_action.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../classes/ServiceRequest.php';
require_once '../classes/AuditLog.php';
require_role('captain');

$id     = (int) ($_GET['id']     ?? 0);
$action = trim($_GET['action']   ?? '');

if (!$id || !in_array($action, ['approve', 'reject'])) {
    header('Location: final_approvals.php?msg=invalid');
    exit;
}

$sr      = new ServiceRequest();
$audit   = new AuditLog();
$request = $sr->getById($id);

if (!$request) {
    header('Location: final_approvals.php?msg=not_found');
    exit;
}

if ($request['Status'] !== 'ForApproval') {
    header('Location: final_approvals.php?msg=invalid');
    exit;
}

$new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
$remark     = ($action === 'approve')
    ? '[Captain Approved]'
    : '[Captain Rejected]';

$existing_remarks = $request['Remarks'] ?? '';
$full_remarks = $existing_remarks
    ? $existing_remarks . "\n" . date('[Y-m-d H:i:s]') . " Captain: $remark"
    : date('[Y-m-d H:i:s]') . " Captain: $remark";

$sr->updateStatus($id, $new_status, (int) $_SESSION['user_id'], $full_remarks);

$audit->log(
    "Captain " . ucfirst($action) . "d request",
    "RequestID: $id | ReferenceNo: " . ($request['ReferenceNo'] ?? '')
);

header('Location: final_approvals.php?msg=' . $action . 'd');
exit;
