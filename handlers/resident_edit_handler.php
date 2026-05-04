<?php
// Barangay Connect – Resident Edit Handler
// handlers/resident_edit_handler.php

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

// Required field check
if (
    empty($first_name) || empty($last_name) ||
    empty($birthdate)  || empty($sex)        || empty($address)
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
