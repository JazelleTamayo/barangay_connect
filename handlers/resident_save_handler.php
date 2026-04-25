<?php
// Barangay Connect – Resident Save Handler
// handlers/resident_save_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Resident.php';
require_once '../classes/UserAccount.php'; //FIXED: added UserAccount include
require_once '../classes/AuditLog.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff/resident_encoding.php');
    exit;
}

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

// Basic validation
if (
    empty($first_name) || empty($last_name) ||
    empty($birthdate)  || empty($sex)        || empty($address)
) {
    header('Location: ../staff/resident_encoding.php?msg=missing_fields');
    exit;
}

$resident = new Resident();
$audit    = new AuditLog();

// Check for duplicate
if ($resident->isDuplicate(
    $first_name,
    $last_name,
    $birthdate,
    $address
)) {
    header('Location: ../staff/resident_encoding.php?msg=duplicate');
    exit;
}

// Create resident
$id = $resident->create([
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
]);

//FIXED: create a linked UserAccount with a temporary password after inserting Resident
$userAccount  = new UserAccount();
$tempPassword = 'Barangay@' . $id;
$username     = strtolower($first_name . '.' . $last_name . $id);
$fullName     = trim("$first_name $middle_name $last_name");

$userAccount->create([
    'resident_id' => $id,
    'username'    => $username,
    'password'    => $tempPassword,
    'role'        => 'resident',
    'status'      => 'Active',
    'full_name'   => $fullName,
    'email'       => $email ?: null,
]);
//FIXED end

$audit->log("Created resident record", "ResidentID: $id");
$audit->log("Created UserAccount for resident", "ResidentID: $id | Username: $username"); //FIXED: added audit log for new UserAccount

header('Location: ../staff/resident_encoding.php?msg=saved');
exit;