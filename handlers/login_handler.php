<?php
// Barangay Connect – Login Handler
// handlers/login_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/UserAccount.php';
require_once '../classes/AuditLog.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password']      ?? '';

if (empty($username) || empty($password)) {
    header('Location: ../public/login.php?msg=missing_fields');
    exit;
}

$ua   = new UserAccount();
$user = $ua->findByUsername($username);

if (!$user) {
    header('Location: ../public/login.php?msg=invalid');
    exit;
}

// Check account status
if ($user['AccountStatus'] === 'PendingVerification') {
    header('Location: ../public/login.php?error=account_inactive');
    exit;
}
if ($user['AccountStatus'] === 'Rejected') {
    header('Location: ../public/login.php?error=account_inactive');
    exit;
}
if ($user['AccountStatus'] === 'Inactive') {
    header('Location: ../public/login.php?error=account_inactive');
    exit;
}

// Verify password
if (!password_verify($password, $user['PasswordHash'])) {
    header('Location: ../public/login.php?msg=invalid');
    exit;
}

// Set session
$_SESSION['user_id']        = $user['UserAccountID'];
$_SESSION['username']       = $user['Username'];
$_SESSION['full_name']      = $user['FullName'];
$_SESSION['role']           = strtolower($user['Role']);
$_SESSION['account_status'] = $user['AccountStatus'];

// Log the login
$audit = new AuditLog();
$audit->log("User logged in", "UserAccountID: " . $user['UserAccountID']);

redirect_to_dashboard();
