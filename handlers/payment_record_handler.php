<?php
// Barangay Connect – Payment Record Handler
// handlers/payment_record_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Payment.php';
require_once '../classes/AuditLog.php';
require_role('secretary');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../secretary/document_release.php');
    exit;
}

$request_id     = (int)   ($_POST['request_id']     ?? 0);
$amount         = (float) ($_POST['amount']         ?? 0);
$payment_method = trim($_POST['payment_method']     ?? 'Cash');

if (!$request_id) {
    header('Location: ../secretary/document_release.php?msg=invalid');
    exit;
}

$payment = new Payment();
$audit   = new AuditLog();

$payment->record([
    'request_id'     => $request_id,
    'amount'         => $amount,
    'payment_method' => $payment_method,
    'recorded_by'    => $_SESSION['user_id'],
]);

$audit->log(
    "Recorded payment",
    "RequestID: $request_id | Amount: $amount"
);

header('Location: ../secretary/document_release.php?msg=released');
exit;
