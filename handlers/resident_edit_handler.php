<?php
// Barangay Connect – Resident Edit Handler
// handlers/resident_edit_handler.php
//
// FIXED Bug #4: The guard query was logically broken. The previous query used
//               LEFT JOIN + WHERE ua.UserAccountID IS NULL, which means it only
//               returned a row when NO non-resident account exists — this is
//               correct for normal residents with a 'resident' role account, but
//               it BLOCKED editing of walk-in residents who have no UserAccount
//               at all (encoded before the account-creation fix), because the
//               LEFT JOIN on Role <> 'resident' produced no match AND
//               ua.UserAccountID was NULL for those rows too — so fetchColumn()
//               returned false for both cases, making them indistinguishable.
//
//               The fix: separate the two checks cleanly.
//               Step 1 — confirm the ResidentID exists.
//               Step 2 — if a non-resident account (captain/secretary/staff/
//                         sysadmin) is linked to this ResidentID, block editing.
//               Any resident account (role='resident') or no account at all
//               is allowed to be edited by the Secretary.

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Resident.php';
require_once '../classes/AuditLog.php';
require_role('secretary');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../secretary/resident_management.php');
    exit;
}

$resident_id   = (int)  ($_POST['resident_id']  ?? 0);
$first_name    = trim($_POST['first_name']    ?? '');
$middle_name   = trim($_POST['middle_name']   ?? '');
$last_name     = trim($_POST['last_name']     ?? '');
$birthdate     = trim($_POST['birthdate']     ?? '');
$sex           = trim($_POST['sex']           ?? '');
$address       = trim($_POST['address']       ?? '');
$purok         = trim($_POST['purok']         ?? '');
$contact       = trim($_POST['contact']       ?? '');
$email         = trim($_POST['email']         ?? '');
$gov_id_type   = trim($_POST['gov_id_type']   ?? '');
$gov_id_number = trim($_POST['gov_id_number'] ?? '');
$status        = trim($_POST['status']        ?? 'Active');

$back = "../secretary/resident_edit.php?id=$resident_id";

if (!$resident_id) {
    header('Location: ../secretary/resident_management.php');
    exit;
}

$allowed_sex    = ['Male', 'Female'];
$allowed_status = ['Active', 'Inactive'];

// Required field check
if (
    empty($first_name) || empty($last_name) ||
    empty($birthdate)  || empty($sex)        || empty($address)
) {
    header("Location: $back&msg=missing_fields");
    exit;
}

$birthdate_obj = DateTime::createFromFormat('Y-m-d', $birthdate);
if (
    !$birthdate_obj ||
    $birthdate_obj->format('Y-m-d') !== $birthdate ||
    $birthdate_obj > new DateTime('today') ||
    !in_array($sex, $allowed_sex, true) ||
    !in_array($status, $allowed_status, true)
) {
    header("Location: $back&msg=missing_fields");
    exit;
}

// Email format check (if provided)
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: $back&msg=missing_fields");
    exit;
}

$residentClass = new Resident();
$audit         = new AuditLog();
$pdo           = get_db();

// FIXED Bug #4: Step 1 — confirm the resident record exists at all.
$stmtExists = $pdo->prepare("SELECT ResidentID FROM Resident WHERE ResidentID = ?");
$stmtExists->execute([$resident_id]);
if (!$stmtExists->fetchColumn()) {
    header('Location: ../secretary/resident_management.php?msg=not_found');
    exit;
}

// FIXED Bug #4: Step 2 — block if a personnel account (non-resident role) is
// linked to this ResidentID. A resident-role account or no account is fine.
$stmtBlock = $pdo->prepare("
    SELECT UserAccountID
    FROM UserAccount
    WHERE ResidentID = ?
      AND Role NOT IN ('resident')
    LIMIT 1
");
$stmtBlock->execute([$resident_id]);
if ($stmtBlock->fetchColumn()) {
    // A captain/secretary/staff/sysadmin account is tied to this ResidentID — block.
    header('Location: ../secretary/resident_management.php?msg=not_allowed');
    exit;
}

// Duplicate check (exclude current resident)
if ($residentClass->isDuplicate($first_name, $last_name, $birthdate, $address, $resident_id)) {
    header("Location: $back&msg=duplicate");
    exit;
}

$updated = $residentClass->update($resident_id, [
    'first_name'    => $first_name,
    'middle_name'   => $middle_name,
    'last_name'     => $last_name,
    'birthdate'     => $birthdate,
    'sex'           => $sex,
    'address'       => $address,
    'purok'         => $purok,
    'contact'       => $contact,
    'email'         => $email,
    'gov_id_type'   => $gov_id_type,
    'gov_id_number' => $gov_id_number,
    'status'        => $status,
]);

if (!$updated) {
    header("Location: $back&msg=error");
    exit;
}

$audit->log(
    "Updated resident record",
    "ResidentID: $resident_id | Updated by secretary"
);

header("Location: $back&msg=updated");
exit;