<?php
// Barangay Connect – Request Create Handler
// handlers/request_create_handler.php
//
// FIXED Bug #6: Added Active account status check for resident-submitted
//               requests. BR-02 states a resident must have an Active account
//               before any service request can be created. Previously, an
//               Inactive resident could still submit requests because neither
//               Resident.Status nor UserAccount.AccountStatus was checked here.

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

// ===== CSRF VERIFICATION (ADDED) =====
csrf_verify();
// ======================================

$request_type  = trim($_POST['request_type']  ?? '');
$purpose       = trim($_POST['purpose']       ?? '');
$resident_id   = (int) ($_POST['resident_id'] ?? 0);
$submitted_by  = trim($_POST['submitted_by']  ?? '');

// Resolve submitted_by
if ($submitted_by !== 'staff') {
    $submitted_by = 'resident';
}

// If submitted by resident, get their resident ID from session
if ($submitted_by === 'resident') {
    $residentObj  = new Resident();
    $residentData = $residentObj->getByUserAccountId($_SESSION['user_id']);
    if (!$residentData) {
        header('Location: ../resident/new_request.php?msg=resident_not_found');
        exit;
    }
    $resident_id = (int) $residentData['ResidentID'];

    // FIXED Bug #6: Enforce Active status. BR-02 requires Active account before
    // any request can be submitted. Check both Resident.Status and
    // UserAccount.AccountStatus to be thorough.
    $pdo = get_db();
    $stmtStatus = $pdo->prepare("
        SELECT r.Status AS resident_status, ua.AccountStatus
        FROM Resident r
        JOIN UserAccount ua ON ua.ResidentID = r.ResidentID
        WHERE ua.UserAccountID = ?
        LIMIT 1
    ");
    $stmtStatus->execute([$_SESSION['user_id']]);
    $statusRow = $stmtStatus->fetch();

    if (
        !$statusRow ||
        $statusRow['resident_status'] !== 'Active' ||
        $statusRow['AccountStatus']   !== 'Active'
    ) {
        header('Location: ../resident/new_request.php?msg=account_inactive');
        exit;
    }
}

if (empty($request_type) || empty($purpose)) {
    header('Location: ../resident/new_request.php?msg=missing_fields');
    exit;
}

// Clearance – check good standing
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
        'request_id'              => $requestId,
        'respondent_name'         => trim($_POST['respondent_name']          ?? ''),
        'respondent_contact'      => trim($_POST['respondent_contact']       ?? ''),
        'respondent_relationship' => trim($_POST['respondent_relationship']  ?? ''),
        'incident_date'           => trim($_POST['incident_date']            ?? ''),
        'incident_location'       => trim($_POST['incident_location']        ?? ''),
        'description'             => $purpose,
        'witnesses'               => trim($_POST['witnesses']                ?? ''),
        'relief_sought'           => trim($_POST['relief_sought']            ?? ''),
    ]);
}

// Handle Indigency extra data – store financial assessment details
if ($request_type === 'Indigency') {
    $monthly_income      = !empty($_POST['monthly_income']) ? (float) $_POST['monthly_income'] : null;
    $household_size      = !empty($_POST['household_size']) ? (int) $_POST['household_size'] : null;
    $employment_status   = trim($_POST['employment_status'] ?? '');
    $income_source       = trim($_POST['income_source'] ?? '');
    $assistance_received = trim($_POST['assistance_received'] ?? '');

    $pdo = get_db();
    $stmt = $pdo->prepare("
        INSERT INTO IndigencyDetail
        (RequestID, MonthlyIncome, HouseholdSize, EmploymentStatus, IncomeSource, AssistanceReceived)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $requestId,
        $monthly_income,
        $household_size,
        $employment_status ?: null,
        $income_source ?: null,
        $assistance_received ?: null
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
            $back = ($submitted_by === 'staff')
                ? '../staff/request_acceptance.php?msg=lead_time_error'
                : '../resident/new_request.php?type=FacilityReservation&msg=lead_time_error';
            header('Location: ' . $back);
            exit;
        }

        if ($sr->isFacilityBooked($facilityId, $reservationDate)) {
            $back = ($submitted_by === 'staff')
                ? '../staff/request_acceptance.php?msg=double_booking'
                : '../resident/new_request.php?type=FacilityReservation&msg=double_booking';
            header('Location: ' . $back);
            exit;
        }

        // Capacity check — reject if expected attendees exceeds facility capacity
        $expectedAttendees = (int) ($_POST['expected_attendees'] ?? 0);
        $stmtCap = $pdo->prepare("SELECT Capacity FROM Facility WHERE FacilityID = ?");
        $stmtCap->execute([$facilityId]);
        $facilityCapacity = (int) $stmtCap->fetchColumn();
        if ($facilityCapacity > 0 && $expectedAttendees > $facilityCapacity) {
            $back = ($submitted_by === 'staff')
                ? '../staff/request_acceptance.php?msg=over_capacity'
                : '../resident/new_request.php?type=FacilityReservation&msg=over_capacity';
            header('Location: ' . $back);
            exit;
        }

        $eventName          = trim($_POST['event_name']            ?? '');
        $contactPerson      = trim($_POST['contact_person']         ?? '');
        $contactPersonNo    = trim($_POST['contact_person_number']  ?? '');
        $agreedToRules      = isset($_POST['agreed_to_rules']) ? 1 : 0;

        $stmt = $pdo->prepare(
            "INSERT INTO FacilityReservation
                (RequestID, FacilityID, ReservationDate, TimeSlot, EventPurpose,
                 EventName, ExpectedAttendees, ContactPerson, ContactPersonNumber, AgreedToRules)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $requestId,
            $facilityId,
            $reservationDate,
            $timeSlot,
            $purpose,
            $eventName,
            $expectedAttendees,
            $contactPerson,
            $contactPersonNo,
            $agreedToRules
        ]);
    }
}

$audit->log(
    "Created service request: $request_type",
    "RequestID: $requestId"
);

// Redirect based on who submitted
if ($submitted_by === 'resident') {
    header('Location: ../resident/track_request.php?msg=submitted');
} else {
    header('Location: ../staff/dashboard.php?msg=created');
}
exit;