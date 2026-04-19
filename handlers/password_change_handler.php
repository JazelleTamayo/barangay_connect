<?php
// Barangay Connect – Password Change Handler
// handlers/password_change_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/UserAccount.php';
require_once '../classes/AuditLog.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password     = $_POST['password']         ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role             = $_SESSION['role']          ?? '';

$ua    = new UserAccount();
$audit = new AuditLog();

if (
    empty($current_password) || empty($new_password) ||
    empty($confirm_password)
) {
    header('Location: ../resident/my_profile.php?msg=missing_fields');
    exit;
}

if ($new_password !== $confirm_password) {
    header('Location: ../resident/my_profile.php?msg=password_mismatch');
    exit;
}

if (strlen($new_password) < 8) {
    header('Location: ../resident/my_profile.php?msg=password_short');
    exit;
}

// Verify current password
if (!$ua->verifyPassword(
    (int) $_SESSION['user_id'],
    $current_password
)) {
    header('Location: ../resident/my_profile.php?msg=wrong_password');
    exit;
}

$ua->changePassword((int) $_SESSION['user_id'], $new_password);
$audit->log(
    "Changed own password",
    "UserAccountID: " . $_SESSION['user_id']
);

// FIXED: Role-based redirect after password change
switch ($role) {
    case 'secretary':
        header('Location: ../secretary/dashboard.php?msg=password_changed');
        break;
    case 'staff':
        header('Location: ../staff/dashboard.php?msg=password_changed');
        break;
    case 'captain':
        header('Location: ../captain/dashboard.php?msg=password_changed');
        break;
    case 'sysadmin':
        header('Location: ../sysadmin/dashboard.php?msg=password_changed');
        break;
    case 'resident':
    default:
        header('Location: ../resident/my_profile.php?msg=password_changed');
        break;
}
exit;