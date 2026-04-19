<?php
// Barangay Connect – Resident Verify Handler
// handlers/resident_verify_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/UserAccount.php';
require_once '../classes/AuditLog.php';
require_role('secretary');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../secretary/resident_verification.php');
    exit;
}

$account_id = (int) ($_POST['account_id'] ?? 0);
$action     = trim($_POST['action']       ?? '');
$reason     = trim($_POST['reason']       ?? '');

if (!$account_id || !in_array($action, ['approve', 'reject'])) {
    header('Location: ../secretary/resident_verification.php?msg=invalid');
    exit;
}

$ua    = new UserAccount();
$audit = new AuditLog();

if ($action === 'approve') {
    $ua->approve($account_id, (int) $_SESSION['user_id']);
    $audit->log(
        "Approved resident account",
        "UserAccountID: $account_id"
    );
    header('Location: ../secretary/resident_verification.php?msg=approved');
} else {
    if (empty($reason)) {
        header('Location: ../secretary/resident_verification.php?msg=reason_required');
        exit;
    }
    $ua->reject($account_id, (int) $_SESSION['user_id'], $reason);
    $audit->log(
        "Rejected resident account",
        "UserAccountID: $account_id"
    );
    header('Location: ../secretary/resident_verification.php?msg=rejected');
}
exit;
