<?php
// Barangay Connect – Resident Save Handler
// handlers/resident_save_handler.php
//
// FIXED Bug #7: Added username uniqueness check before calling
//               UserAccount::create(). Previously, if the auto-generated
//               username (firstname.lastnameID) already existed in the
//               UserAccount table, the INSERT would fail silently — the
//               Resident record was already saved but had no linked account,
//               and the staff member was shown a success message anyway.
//               Now: if the first username attempt is taken, a numeric suffix
//               is appended and retried up to 5 times. If all attempts fail,
//               the handler redirects with a clear error message.

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Resident.php';
require_once '../classes/UserAccount.php';
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

// Create a linked UserAccount with a temporary password
// FIXED Bug #7: Find a unique username before inserting.
// Base username: firstname.lastnameID (lowercase, no spaces)
$userAccount  = new UserAccount();
$tempPassword = 'Barangay@' . $id;
$fullName     = trim("$first_name $middle_name $last_name");

$baseUsername = strtolower(
    preg_replace('/\s+/', '', $first_name) . '.' .
    preg_replace('/\s+/', '', $last_name) . $id
);

// Try baseUsername first, then baseUsername2, baseUsername3, … up to 5 attempts
$username = $baseUsername;
$attempt  = 1;
$maxTries = 5;

while ($attempt <= $maxTries) {
    if (!$userAccount->findByUsername($username)) {
        break; // username is free — use it
    }
    $attempt++;
    $username = $baseUsername . $attempt;
}

if ($attempt > $maxTries) {
    // Extremely unlikely — all 5 variants taken. Show error; Resident record
    // was already created so redirect with a specific message so staff can
    // manually handle it or contact the sysadmin.
    $audit->log("Resident created but UserAccount NOT created — username collision", "ResidentID: $id");
    header('Location: ../staff/resident_encoding.php?msg=username_taken');
    exit;
}

$userAccount->create([
    'resident_id' => $id,
    'username'    => $username,
    'password'    => $tempPassword,
    'role'        => 'resident',
    'status'      => 'Active',
    'full_name'   => $fullName,
    'email'       => $email ?: null,
]);

$audit->log("Created resident record", "ResidentID: $id");
$audit->log("Created UserAccount for resident", "ResidentID: $id | Username: $username");

header('Location: ../staff/resident_encoding.php?msg=saved&id=' . $id . '&user=' . $username);
exit;