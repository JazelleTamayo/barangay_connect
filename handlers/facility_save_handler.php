<?php
// Barangay Connect – Facility Save Handler
// handlers/facility_save_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Facility.php';
require_once '../classes/AuditLog.php';
require_role('secretary');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../secretary/dashboard.php');
    exit;
}

$facility_id     = (int)   ($_POST['facility_id']     ?? 0);
$facility_name   = trim($_POST['facility_name']       ?? '');
$capacity        = (int)   ($_POST['capacity']        ?? 0);
$reservation_fee = (float) ($_POST['reservation_fee'] ?? 0);
$description     = trim($_POST['description']         ?? '');
$status          = trim($_POST['status']              ?? 'Active');

if (empty($facility_name)) {
    header('Location: ../secretary/dashboard.php?msg=missing_fields');
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

header('Location: ../secretary/dashboard.php?msg=facility_saved');
exit;
