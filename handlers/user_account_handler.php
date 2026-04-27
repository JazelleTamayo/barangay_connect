<?php
// Barangay Connect – User Account Handler
// handlers/user_account_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/UserAccount.php';
require_once '../classes/AuditLog.php';
require_role('sysadmin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = trim($_POST['action']   ?? '');
    $account_id = (int) ($_POST['account_id'] ?? 0);
} else {
    // GET-based actions from action links (disable, enable, delete)
    $action     = trim($_GET['action'] ?? '');
    $account_id = (int) ($_GET['id']   ?? 0);
}

$ua    = new UserAccount();
$audit = new AuditLog();

if ($action === 'create') {
    // Create must be POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../sysadmin/user_accounts.php');
        exit;
    }
    $username        = trim($_POST['username']         ?? '');
    $full_name       = trim($_POST['full_name']        ?? '');
    $password        = $_POST['password']              ?? '';
    $confirm         = $_POST['confirm_password']      ?? '';
    $role            = trim($_POST['role']             ?? '');
    $email           = trim($_POST['email']            ?? '');

    if (
        empty($username) || empty($password) ||
        empty($full_name) || empty($role)
    ) {
        header('Location: ../sysadmin/user_accounts.php?msg=missing_fields');
        exit;
    }
    if ($password !== $confirm) {
        header('Location: ../sysadmin/user_accounts.php?msg=password_mismatch');
        exit;
    }
    if (strlen($password) < 8) {
        header('Location: ../sysadmin/user_accounts.php?msg=password_short');
        exit;
    }

    $id = $ua->create([
        'username'  => $username,
        'full_name' => $full_name,
        'password'  => $password,
        'role'      => $role,
        'email'     => $email,
        'status'    => 'Active',
    ]);

    $audit->log(
        "Created user account: $username",
        "UserAccountID: $id"
    );
    header('Location: ../sysadmin/user_accounts.php?msg=created');
} elseif ($action === 'disable') {
    $ua->disable($account_id);
    $audit->log(
        "Disabled user account",
        "UserAccountID: $account_id"
    );
    header('Location: ../sysadmin/user_accounts.php?msg=disabled');
} elseif ($action === 'enable') {
    $ua->enable($account_id);
    $audit->log(
        "Enabled user account",
        "UserAccountID: $account_id"
    );
    header('Location: ../sysadmin/user_accounts.php?msg=enabled');
} elseif ($action === 'delete') {
    $ua->deleteAccount($account_id);
    $audit->log(
        "Deleted user account",
        "UserAccountID: $account_id"
    );
    header('Location: ../sysadmin/user_accounts.php?msg=deleted');
} else {
    header('Location: ../sysadmin/user_accounts.php');
}
exit;
