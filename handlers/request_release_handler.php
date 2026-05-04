<?php
// Barangay Connect – Request Release Handler
// handlers/request_release_handler.php
// FIXED Bug #5: Accepts both 'staff' and 'secretary' roles.
//               staff/release_document.php requires 'staff', but this
//               handler previously required 'secretary', making the
//               staff release page completely non-functional.

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/ServiceRequest.php';
require_once '../classes/AuditLog.php';
require_login();

// FIXED: allow both staff and secretary to release documents.
$allowed_roles = ['secretary', 'staff'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    redirect_to_dashboard();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect to the correct page based on role
    $back = ($_SESSION['role'] === 'staff')
        ? '../staff/release_document.php'
        : '../secretary/document_release.php';
    header("Location: $back");
    exit;
}

$request_id = (int) ($_POST['request_id'] ?? 0);
$remarks    = trim($_POST['remarks']      ?? '');

if (!$request_id) {
    $back = ($_SESSION['role'] === 'staff')
        ? '../staff/release_document.php?msg=invalid'
        : '../secretary/document_release.php?msg=invalid';
    header("Location: $back");
    exit;
}

$sr      = new ServiceRequest();
$audit   = new AuditLog();
$request = $sr->getById($request_id);

if (!$request) {
    $back = ($_SESSION['role'] === 'staff')
        ? '../staff/release_document.php?msg=not_found'
        : '../secretary/document_release.php?msg=not_found';
    header("Location: $back");
    exit;
}

if ($request['Status'] !== 'Approved') {
    $back = ($_SESSION['role'] === 'staff')
        ? '../staff/release_document.php?msg=not_approved'
        : '../secretary/document_release.php?msg=not_approved';
    header("Location: $back");
    exit;
}

$sr->updateStatus(
    $request_id,
    'Released',
    (int) $_SESSION['user_id'],
    $remarks
);

$audit->log(
    "Released document",
    "RequestID: $request_id"
);

$back = ($_SESSION['role'] === 'staff')
    ? '../staff/release_document.php?msg=released'
    : '../secretary/document_release.php?msg=released';
header("Location: $back");
exit;
