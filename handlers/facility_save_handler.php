<?php
// Barangay Connect – Facility Save Handler
// handlers/facility_save_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Facility.php';
require_once '../classes/AuditLog.php';
require_role('secretary');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../secretary/facility_management.php'); // CHANGED: was dashboard.php
    exit;
}

csrf_verify(); // ADDED: CSRF protection

$facility_id     = (int)   ($_POST['facility_id']     ?? 0);
$facility_name   = trim($_POST['facility_name']       ?? '');
$capacity        = (int)   ($_POST['capacity']        ?? 0);
$reservation_fee = (float) ($_POST['reservation_fee'] ?? 0);
$description     = trim($_POST['description']         ?? '');
$status          = trim($_POST['status']              ?? 'Active');

if (empty($facility_name)) {
    header('Location: ../secretary/facility_management.php?msg=missing_fields'); // CHANGED: was dashboard.php
    exit;
}

$facility = new Facility();
$audit    = new AuditLog();

if ($facility_id) {
    // Update existing
    $facility->update($facility_id, [
        'facility_name'   => $facility_name,
        'capacity'        => $capacity,
        'reservation_fee' => $reservation_fee,
        'description'     => $description,
        'status'          => $status,
    ]);
    $audit->log(
        "Updated facility",
        "FacilityID: $facility_id"
    );
} else {
    // Create new
    $id = $facility->create([
        'facility_name'   => $facility_name,
        'capacity'        => $capacity,
        'reservation_fee' => $reservation_fee,
        'description'     => $description,
    ]);
    $audit->log(
        "Created facility",
        "FacilityID: $id"
    );
}

// CHANGED: redirect to facility_management with correct msg (deactivated/activated/facility_saved)
$redirect_msg = trim($_POST['redirect_msg'] ?? 'facility_saved');
$allowed_msgs = ['facility_saved', 'deactivated', 'activated'];
if (!in_array($redirect_msg, $allowed_msgs, true)) {
    $redirect_msg = 'facility_saved';
}

header('Location: ../secretary/facility_management.php?msg=' . $redirect_msg);
exit;