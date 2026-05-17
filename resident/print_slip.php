<?php
// Barangay Connect – Print Submission Slip (Resident)
// resident/print_slip.php
// Opens a clean printable submission slip for a resident's own request.

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/settings.php';
require_role('resident');

$ref = trim($_GET['ref'] ?? '');
if (!$ref) { http_response_code(400); die('Missing reference number.'); }

$pdo     = get_db();
$user_id = $_SESSION['user_id'];

// Resolve resident ID
$stmt = $pdo->prepare("SELECT ResidentID FROM UserAccount WHERE UserAccountID = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch();
$resident_id = $row ? $row['ResidentID'] : null;

if (!$resident_id) {
    header('Location: ../public/login.php');
    exit;
}

// Load request — must belong to this resident
$clearance_fee = get_setting('clearance_fee', 50.00);
$stmt = $pdo->prepare("
    SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType, sr.CreatedAt, sr.Status,
           CASE sr.RequestType
               WHEN 'Clearance'           THEN :clearance_fee
               WHEN 'Indigency'           THEN 0
               WHEN 'Complaint'           THEN 0
               WHEN 'FacilityReservation' THEN COALESCE(f.ReservationFee, 0)
               ELSE 0
           END AS expected_amount
    FROM ServiceRequest sr
    LEFT JOIN FacilityReservation fr ON sr.RequestID = fr.RequestID
    LEFT JOIN Facility f             ON fr.FacilityID = f.FacilityID
    WHERE sr.ReferenceNo = :ref AND sr.ResidentID = :resident_id
");
$stmt->execute([':clearance_fee' => $clearance_fee, ':ref' => $ref, ':resident_id' => $resident_id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    http_response_code(403);
    die('Request not found or does not belong to your account.');
}

$settings      = load_settings();
$barangay_name = $settings['barangay_name'] ?? 'Barangay Connect';
$municipality  = $settings['municipality']  ?? '';

// Format request type for display
$type_labels = [
    'Clearance'           => 'Barangay Clearance',
    'Indigency'           => 'Certificate of Indigency',
    'Complaint'           => 'Complaint',
    'FacilityReservation' => 'Facility Reservation',
];
$type_display = $type_labels[$req['RequestType']] ?? $req['RequestType'];

$status_labels = [
    'Pending'     => 'Pending',
    'ForApproval' => 'For Approval',
    'Approved'    => 'Approved',
    'Prepared'    => 'Ready for Pickup',
    'Released'    => 'Released',
    'Rejected'    => 'Rejected',
    'Cancelled'   => 'Cancelled',
];
$status_display = $status_labels[$req['Status']] ?? $req['Status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submission Slip – <?= htmlspecialchars($req['ReferenceNo']) ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  @page { size: 8.5in 11in; margin: 1in; }

  body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    color: #000;
    background: #fff;
    padding: 40px;
    max-width: 680px;
    margin: 0 auto;
  }

  /* ── Print/Back bar (screen only) ── */
  .print-bar {
    display: flex; gap: 10px; margin-bottom: 28px;
    font-family: Arial, sans-serif;
  }
  .print-bar button, .print-bar a {
    padding: 8px 18px; border-radius: 6px; font-size: 13px;
    cursor: pointer; text-decoration: none; display: inline-block;
  }
  .btn-print { background: #1d4ed8; color: #fff; border: none; }
  .btn-back  { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
  @media print { .print-bar { display: none; } }

  /* ── Slip document ── */
  .document {
    border: 2px solid #000;
    padding: 36px 44px;
  }

  /* ── Header ── */
  .slip-header {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 16px;
    margin-bottom: 20px;
  }
  .slip-header .brgy-name {
    font-size: 16pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .slip-header .municipality {
    font-size: 10pt;
    margin-top: 2px;
  }
  .slip-title {
    font-size: 13pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
    padding: 6px 0;
    margin-top: 14px;
  }

  /* ── Details table ── */
  .slip-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11pt;
    margin-top: 4px;
  }
  .slip-table td {
    padding: 9px 10px;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
  }
  .slip-table td:first-child {
    width: 40%;
    color: #555;
    font-style: italic;
    white-space: nowrap;
  }
  .slip-table td:last-child {
    width: 60%;
    font-weight: bold;
  }
  .slip-table tr:last-child td {
    border-bottom: none;
  }

  /* ── Note footer ── */
  .slip-note {
    margin-top: 22px;
    padding-top: 14px;
    border-top: 1px solid #ccc;
    font-size: 9.5pt;
    color: #555;
    text-align: center;
    line-height: 1.6;
    font-style: italic;
  }

  /* ── Print date ── */
  .doc-footer {
    margin-top: 20px;
    font-size: 8.5pt;
    color: #888;
    text-align: right;
  }
  @media print { .doc-footer { color: #555; } }
</style>
</head>
<body>

<div class="print-bar">
  <button class="btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
  <a class="btn-back" href="track_request.php?ref=<?= urlencode($req['ReferenceNo']) ?>">← Back</a>
</div>

<div class="document">

  <div class="slip-header">
    <div class="brgy-name"><?= htmlspecialchars($barangay_name) ?></div>
    <?php if ($municipality): ?>
      <div class="municipality"><?= htmlspecialchars($municipality) ?></div>
    <?php endif; ?>
    <div class="slip-title">Request Submission Slip</div>
  </div>

  <table class="slip-table">
    <tr>
      <td>Reference No.</td>
      <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
    </tr>
    <tr>
      <td>Request Type</td>
      <td><?= htmlspecialchars($type_display) ?></td>
    </tr>
    <tr>
      <td>Date Submitted</td>
      <td><?= date('F d, Y g:i A', strtotime($req['CreatedAt'])) ?></td>
    </tr>
    <tr>
      <td>Status</td>
      <td><?= htmlspecialchars($status_display) ?></td>
    </tr>
  </table>

  <div class="slip-note">
    Present this slip or your reference number when following up at the Barangay Hall.<br>
    Keep this for your records.
  </div>

  <div class="doc-footer">Printed: <?= date('Y-m-d H:i') ?></div>

</div><!-- /document -->

</body>
</html>
