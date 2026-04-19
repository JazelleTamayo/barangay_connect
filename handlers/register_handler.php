<?php
// Barangay Connect – Register Handler
// handlers/register_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Resident.php';
require_once '../classes/UserAccount.php';
require_once '../classes/AuditLog.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/register.php');
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
$username      = trim($_POST['username']      ?? '');
$password      = $_POST['password']           ?? '';
$confirm_pass  = $_POST['confirm_password']   ?? '';

// Validation
if (
    empty($first_name) || empty($last_name)  ||
    empty($birthdate)  || empty($sex)         ||
    empty($address)    || empty($username)    ||
    empty($password)
) {
    header('Location: ../public/register.php?msg=missing_fields');
    exit;
}

if ($password !== $confirm_pass) {
    header('Location: ../public/register.php?msg=password_mismatch');
    exit;
}

if (strlen($password) < 8) {
    header('Location: ../public/register.php?msg=password_short');
    exit;
}

$resident = new Resident();
$ua       = new UserAccount();
$audit    = new AuditLog();

// Check duplicate resident
if ($resident->isDuplicate(
    $first_name,
    $last_name,
    $birthdate,
    $address
)) {
    header('Location: ../public/register.php?msg=duplicate');
    exit;
}

// Check duplicate username
$existing = $ua->findByUsername($username);
if ($existing) {
    header('Location: ../public/register.php?msg=username_taken');
    exit;
}

// Handle government ID upload
$gov_id_path = null;
if (
    isset($_FILES['gov_id_image']) &&
    $_FILES['gov_id_image']['error'] === UPLOAD_ERR_OK
) {
    $upload_dir = __DIR__ . '/../uploads/government_ids/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $ext         = pathinfo(
        $_FILES['gov_id_image']['name'],
        PATHINFO_EXTENSION
    );
    $filename    = uniqid('gov_id_', true) . '.' . $ext;
    $destination = $upload_dir . $filename;

    if (move_uploaded_file(
        $_FILES['gov_id_image']['tmp_name'],
        $destination
    )) {
        $gov_id_path = 'uploads/government_ids/' . $filename;
    }
}

// Create resident record
$resident_id = $resident->create([
    'first_name'   => $first_name,
    'middle_name'  => $middle_name,
    'last_name'    => $last_name,
    'birthdate'    => $birthdate,
    'sex'          => $sex,
    'address'      => $address,
    'purok'        => $purok,
    'contact'      => $contact,
    'email'        => $email,
    'gov_id_path'  => $gov_id_path,
]);

// Create user account with PendingVerification status
$ua->create([
    'resident_id' => $resident_id,
    'username'    => $username,
    'password'    => $password,
    'role'        => 'resident',
    'status'      => 'PendingVerification',
    'full_name'   => trim("$first_name $last_name"),
    'email'       => $email,
]);

$audit->log(
    "New self-registration submitted",
    "ResidentID: $resident_id | Username: $username"
);

header('Location: ../public/register.php?msg=success');
exit;
