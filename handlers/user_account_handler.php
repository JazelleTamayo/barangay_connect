<?php
// Barangay Connect – User Account Handler
// handlers/user_account_handler.php
// FIXED: cannot delete/disable own account
// FIXED: cannot delete last sysadmin
// FIXED: role whitelist on create (no resident allowed)

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/UserAccount.php';
require_once '../classes/AuditLog.php';
require_role('sysadmin');

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = trim($_POST['action']     ?? '');
    $account_id = (int) ($_POST['account_id'] ?? 0);
} else {
    $action     = trim($_GET['action'] ?? '');
    $account_id = (int) ($_GET['id']   ?? 0);
}

$ua    = new UserAccount();
$audit = new AuditLog();

// ── Allowed admin roles (residents cannot be created here) ─────────────────
$allowed_roles = ['captain', 'secretary', 'staff', 'sysadmin'];

// ── Self-action guard ──────────────────────────────────────────────────────
$current_user_id = (int) ($_SESSION['user_id'] ?? 0);

if ($action === 'create') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../sysadmin/user_accounts.php');
        exit;
    }

    $username  = trim($_POST['username']         ?? '');
    $full_name = trim($_POST['full_name']        ?? '');
    $password  = $_POST['password']              ?? '';
    $confirm   = $_POST['confirm_password']      ?? '';
    $role      = trim($_POST['role']             ?? '');
    $email     = trim($_POST['email']            ?? '');

    // Required fields
    if (empty($username) || empty($password) || empty($full_name) || empty($role)) {
        header('Location: ../sysadmin/user_accounts.php?msg=missing_fields');
        exit;
    }

    // Role must be an admin role — not resident
    if (!in_array($role, $allowed_roles)) {
        header('Location: ../sysadmin/user_accounts.php?msg=invalid_role');
        exit;
    }

    // Password match
    if ($password !== $confirm) {
        header('Location: ../sysadmin/user_accounts.php?msg=password_mismatch');
        exit;
    }

    // Password length
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

    $audit->log("Created user account: $username (Role: $role)", "UserAccountID: $id");
    header('Location: ../sysadmin/user_accounts.php?msg=created');

} elseif ($action === 'disable') {
    // Cannot disable own account
    if ($account_id === $current_user_id) {
        header('Location: ../sysadmin/user_accounts.php?msg=cannot_self');
        exit;
    }
    $ua->disable($account_id);
    $audit->log("Disabled user account", "UserAccountID: $account_id");
    header('Location: ../sysadmin/user_accounts.php?msg=disabled');

} elseif ($action === 'enable') {
    $ua->enable($account_id);
    $audit->log("Enabled user account", "UserAccountID: $account_id");
    header('Location: ../sysadmin/user_accounts.php?msg=enabled');

} elseif ($action === 'delete') {
    // Cannot delete own account
    if ($account_id === $current_user_id) {
        header('Location: ../sysadmin/user_accounts.php?msg=cannot_self');
        exit;
    }

    // Cannot delete the last sysadmin
    $sysadmin_count = (int) $pdo->query(
        "SELECT COUNT(*) FROM useraccount WHERE Role = 'sysadmin' AND AccountStatus = 'Active'"
    )->fetchColumn();

    $target_role = $pdo->prepare("SELECT Role FROM useraccount WHERE UserAccountID = :id");
    $target_role->execute([':id' => $account_id]);
    $target = $target_role->fetchColumn();

    if ($target === 'sysadmin' && $sysadmin_count <= 1) {
        header('Location: ../sysadmin/user_accounts.php?msg=last_sysadmin');
        exit;
    }

    $ua->deleteAccount($account_id);
    $audit->log("Deleted user account", "UserAccountID: $account_id");
    header('Location: ../sysadmin/user_accounts.php?msg=deleted');

} else {
    header('Location: ../sysadmin/user_accounts.php');
}
exit;
