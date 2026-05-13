<?php
// Barangay Connect – Complaint Update Handler
// handlers/complaint_update_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/ServiceRequest.php';
require_once '../classes/Complaint.php';
require_once '../classes/AuditLog.php';
require_role('secretary');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../secretary/complaint_management.php');
    exit;
}

$reference_no   = trim($_POST['reference_no']   ?? '');
$mediation_date = trim($_POST['mediation_date'] ?? '');
$actions_taken  = trim($_POST['actions_taken']  ?? '');
$new_status     = trim($_POST['status']         ?? '');

$allowed_statuses = ['ForApproval', 'Approved', 'Rejected'];

if (empty($reference_no) || empty($mediation_date)) {
    header('Location: ../secretary/complaint_management.php?msg=missing_fields');
    exit;
}

$date = DateTime::createFromFormat('Y-m-d', $mediation_date);
if (!$date || $date->format('Y-m-d') !== $mediation_date) {
    header('Location: ../secretary/complaint_management.php?msg=invalid');
    exit;
}

if ($new_status !== '' && !in_array($new_status, $allowed_statuses, true)) {
    header('Location: ../secretary/complaint_management.php?msg=invalid');
    exit;
}

$sr       = new ServiceRequest();
$complaint = new Complaint();
$audit    = new AuditLog();

$request = $sr->getByReferenceNo($reference_no);

if (!$request) {
    header('Location: ../secretary/complaint_management.php?msg=not_found');
    exit;
}

if (($request['RequestType'] ?? '') !== 'Complaint') {
    header('Location: ../secretary/complaint_management.php?msg=invalid');
    exit;
}

// Update complaint details
$complaint->update($request['RequestID'], [
    'mediation_date' => $mediation_date ?: null,
    'actions_taken'  => $actions_taken  ?: null,
]);

// Update status if changed
if (!empty($new_status)) {
    $sr->updateStatus(
        $request['RequestID'],
        $new_status,
        (int) $_SESSION['user_id'],
        $actions_taken
    );
}

$audit->log(
    "Updated complaint record",
    "ReferenceNo: $reference_no"
);

header('Location: ../secretary/complaint_management.php?msg=updated');
exit;
