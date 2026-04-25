<?php
// Barangay Connect – Request Create Handler
// handlers/request_create_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/ServiceRequest.php';
require_once '../classes/Complaint.php';
require_once '../classes/Resident.php';
require_once '../classes/AuditLog.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$request_type  = trim($_POST['request_type']  ?? '');
$purpose       = trim($_POST['purpose']       ?? '');
$resident_id   = (int) ($_POST['resident_id'] ?? 0);
$submitted_by  = trim($_POST['submitted_by']  ?? ''); //FIXED: default to empty string, not 'staff'

//FIXED: explicitly resolve submitted_by — only treat as staff if form sends 'staff'
if ($submitted_by !== 'staff') {
    $submitted_by = 'resident';
}
//FIXED end

// If submitted by resident, get their resident ID from session
if ($submitted_by === 'resident') {
    $residentObj  = new Resident();
    $residentData = $residentObj->getByUserAccountId($_SESSION['user_id']);
    if (!$residentData) {
        header('Location: ../resident/new_request.php?msg=resident_not_found');
        exit;
    }
    $resident_id = (int) $residentData['ResidentID'];
}

if (empty($request_type) || empty($purpose)) {
    header('Location: ../resident/new_request.php?msg=missing_fields');
    exit;
}

// Clearance — check good standing
if ($request_type === 'Clearance' && $resident_id) {
    $residentClass = new Resident();
    if (!$residentClass->isInGoodStanding($resident_id)) {
        header('Location: ../resident/new_request.php?msg=not_good_standing');
        exit;
    }
}

$sr    = new ServiceRequest();
$audit = new AuditLog();

// Create the base request
$requestId = $sr->create([
    'resident_id'  => $resident_id,
    'request_type' => $request_type,
    'purpose'      => $purpose,
    'created_by'   => $_SESSION['user_id'] ?? null,
]);

// Handle complaint extra data
if ($request_type === 'Complaint') {
    $complaint = new Complaint();
    $complaint->create([
        'request_id'         => $requestId,
        'respondent_name'    => trim($_POST['respondent_name']    ?? ''),
        'respondent_contact' => trim($_POST['respondent_contact'] ?? ''),
        'incident_date'      => trim($_POST['incident_date']      ?? ''),
        'incident_location'  => trim($_POST['incident_location']  ?? ''),
        'description'        => $purpose,
    ]);
}

// Handle facility reservation extra data
if ($request_type === 'FacilityReservation') {
    $facilityId      = (int) ($_POST['facility_id']    ?? 0);
    $reservationDate = trim($_POST['reservation_date'] ?? '');
    $timeSlot        = trim($_POST['time_slot']        ?? '');

    if ($facilityId && $reservationDate) {

        require_once '../config/constants.php';

        $today       = new DateTime('today');
        $workingDays = 0;
        $check       = clone $today;

        while ($workingDays < RESERVATION_LEAD_DAYS) {
            $check->modify('+1 day');
            $dayOfWeek = (int) $check->format('N');
            if ($dayOfWeek < 6) {
                $workingDays++;
            }
        }

        $earliest      = $check;
        $requestedDate = new DateTime($reservationDate);

        if ($requestedDate < $earliest) {
            header('Location: ../resident/new_request.php'
                 . '?type=FacilityReservation&msg=lead_time_error');
            exit;
        }

        if ($sr->isFacilityBooked($facilityId, $reservationDate)) {
            header('Location: ../resident/new_request.php'
                    . '?type=FacilityReservation&msg=double_booking');
            exit;
        }

        $pdo  = get_db();
        $stmt = $pdo->prepare(
            "INSERT INTO FacilityReservation
                (RequestID, FacilityID, ReservationDate,
                 TimeSlot, EventPurpose)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $requestId,
            $facilityId,
            $reservationDate,
            $timeSlot,
            $purpose
        ]);
    }
}

$audit->log(
    "Created service request: $request_type",
    "RequestID: $requestId"
);

// Redirect based on who submitted
if ($submitted_by === 'resident') {
    header('Location: ../resident/new_request.php?msg=created');
} else {
    header('Location: ../staff/request_acceptance.php?msg=created');
}
exit;