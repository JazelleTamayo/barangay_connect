<?php
// Barangay Connect – Print Document
// staff/print_document.php
// Generates a printable Barangay Clearance or Certificate of Indigency.
// Accessible by staff, secretary, and captain for Released requests.

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/settings.php';

// Allow staff, secretary, captain
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','secretary','captain'])) {
    header('Location: ../public/login.php');
    exit;
}

$id  = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); die('Missing request ID.'); }

$pdo  = get_db();

// Load the service request + resident
$stmt = $pdo->prepare("
    SELECT sr.*,
           r.FirstName, r.MiddleName, r.LastName,
           r.Birthdate, r.Sex, r.Address, r.Purok,
           r.ContactNumber,
           CONCAT(r.FirstName,' ',IFNULL(r.MiddleName,''),' ',r.LastName) AS FullName,
           ua_released.FullName  AS ReleasedByName,
           ua_prepared.FullName  AS PreparedByName,
           p.ReceiptNo, p.Amount, p.PaymentMethod, p.RecordedAt AS PaymentDate
    FROM ServiceRequest sr
    JOIN Resident r          ON sr.ResidentID   = r.ResidentID
    LEFT JOIN UserAccount ua_released ON sr.ReleasedBy  = ua_released.UserAccountID
    LEFT JOIN UserAccount ua_prepared ON sr.PreparedBy  = ua_prepared.UserAccountID
    LEFT JOIN Payment p               ON p.RequestID    = sr.RequestID
    WHERE sr.RequestID = ?
      AND sr.RequestType IN ('Clearance','Indigency')
");
$stmt->execute([$id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) { http_response_code(404); die('Document not found or not a printable type.'); }

// Load indigency details if applicable
$indigency = null;
if ($req['RequestType'] === 'Indigency') {
    $s2 = $pdo->prepare("SELECT * FROM IndigencyDetail WHERE RequestID = ?");
    $s2->execute([$id]);
    $indigency = $s2->fetch(PDO::FETCH_ASSOC);
}

// Load captain name
$captain = $pdo->query("
    SELECT CONCAT(FirstName,' ',LastName) AS Name, ContactNumber
    FROM CaptainProfile LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$settings      = load_settings();
$barangay_name = $settings['barangay_name'] ?? 'Barangay';
$municipality  = $settings['municipality']  ?? '';
$contact       = $settings['contact']       ?? '';

$is_clearance = $req['RequestType'] === 'Clearance';
$doc_title    = $is_clearance ? 'Barangay Clearance' : 'Certificate of Indigency';
$or_number    = $req['ReceiptNo'] ?? 'N/A';

// Age calculation
$age = '';
if (!empty($req['Birthdate'])) {
    $age = (int) date_diff(date_create($req['Birthdate']), date_create('today'))->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($doc_title) ?> – <?= htmlspecialchars($req['ReferenceNo']) ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  @page { size: 8.5in 11in; margin: 0.75in; }
  body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    color: #000;
    background: #fff;
    padding: 40px;
    max-width: 760px;
    margin: 0 auto;
  }

  /* ── Print button (screen only) ── */
  .print-bar {
    display: flex; gap: 10px; margin-bottom: 24px;
    font-family: Arial, sans-serif;
  }
  .print-bar button, .print-bar a {
    padding: 8px 18px; border-radius: 6px; font-size: 13px;
    cursor: pointer; text-decoration: none; display: inline-block;
  }
  .btn-print  { background: #1d4ed8; color: #fff; border: none; }
  .btn-back   { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
  @media print { .print-bar { display: none; } }

  /* ── Document wrapper ── */
  .document {
    border: 2px solid #000;
    padding: 32px 40px;
    position: relative;
  }

  /* ── Header ── */
  .doc-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 16px; }
  .republic-line { font-size: 10pt; letter-spacing: 1px; text-transform: uppercase; }
  .barangay-name { font-size: 18pt; font-weight: bold; text-transform: uppercase; margin: 6px 0 2px; }
  .municipality  { font-size: 11pt; }
  .doc-title {
    font-size: 15pt; font-weight: bold; text-transform: uppercase;
    letter-spacing: 2px; margin: 18px 0 4px;
    border-top: 1px solid #000; border-bottom: 1px solid #000;
    padding: 6px 0;
  }
  .doc-subtitle { font-size: 10pt; font-style: italic; }

  /* ── Reference + Date ── */
  .meta-row {
    display: flex; justify-content: space-between;
    font-size: 10pt; margin: 14px 0 20px;
  }

  /* ── Body ── */
  .salutation { margin-bottom: 12px; }
  .body-text  { line-height: 1.9; text-align: justify; margin-bottom: 14px; }
  .body-text .field {
    font-weight: bold; text-transform: uppercase;
    border-bottom: 1px solid #000; padding: 0 4px;
    display: inline-block; min-width: 140px; text-align: center;
  }

  /* ── Details table (Indigency) ── */
  .detail-table { width: 100%; border-collapse: collapse; margin: 14px 0; font-size: 11pt; }
  .detail-table td { padding: 5px 10px; border: 1px solid #aaa; }
  .detail-table td:first-child { font-weight: bold; width: 38%; background: #f5f5f5; }

  /* ── Purpose box ── */
  .purpose-box {
    border: 1px solid #888; padding: 10px 14px; margin: 14px 0;
    font-size: 11pt; background: #fafafa;
  }
  .purpose-box strong { display: block; margin-bottom: 4px; }

  /* ── Signature area ── */
  .sig-section { margin-top: 40px; }
  .sig-row { display: flex; justify-content: space-between; gap: 20px; }
  .sig-block { flex: 1; text-align: center; }
  .sig-line { border-top: 1px solid #000; margin: 50px auto 4px; width: 80%; }
  .sig-name { font-weight: bold; text-transform: uppercase; font-size: 11pt; }
  .sig-title { font-size: 10pt; }

  /* ── Footer ── */
  .doc-footer {
    margin-top: 28px; padding-top: 10px;
    border-top: 1px solid #888;
    font-size: 9pt; color: #444;
    display: flex; justify-content: space-between;
  }

  /* ── DRAFT watermark for non-released ── */
  <?php if ($req['Status'] !== 'Released'): ?>
  .document::after {
    content: 'DRAFT';
    position: fixed; top: 40%; left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    font-size: 80pt; color: rgba(220,38,38,0.12);
    font-weight: 900; pointer-events: none; white-space: nowrap;
    z-index: 0;
  }
  <?php endif; ?>
</style>
</head>
<body>

<div class="print-bar">
  <button class="btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
  <a class="btn-back" href="javascript:history.back()">← Back</a>
</div>

<div class="document">

  <!-- Header -->
  <div class="doc-header">
    <div class="republic-line">Republic of the Philippines</div>
    <div class="barangay-name"><?= htmlspecialchars($barangay_name) ?></div>
    <div class="municipality"><?= htmlspecialchars($municipality) ?></div>
    <?php if ($contact): ?>
      <div style="font-size:10pt;margin-top:3px;">Tel: <?= htmlspecialchars($contact) ?></div>
    <?php endif; ?>
    <div class="doc-title"><?= htmlspecialchars($doc_title) ?></div>
    <?php if ($is_clearance): ?>
      <div class="doc-subtitle">This certifies that the person named herein is a bonafide resident of this barangay.</div>
    <?php else: ?>
      <div class="doc-subtitle">This certifies that the person named herein is an indigent resident of this barangay.</div>
    <?php endif; ?>
  </div>

  <!-- Reference + Date -->
  <div class="meta-row">
    <span>Reference No.: <strong><?= htmlspecialchars($req['ReferenceNo']) ?></strong></span>
    <span>Date Issued: <strong><?= date('F d, Y', strtotime($req['ReleasedAt'] ?? $req['UpdatedAt'])) ?></strong></span>
  </div>

  <!-- Salutation -->
  <div class="salutation">To Whom It May Concern:</div>

  <?php if ($is_clearance): ?>
  <!-- CLEARANCE BODY -->
  <p class="body-text">
    This is to certify that
    <span class="field"><?= htmlspecialchars(trim($req['FullName'])) ?></span>,
    <?= $age ? $age . ' years old, ' : '' ?>
    <span class="field"><?= htmlspecialchars($req['Sex']) ?></span>,
    a resident of
    <span class="field"><?= htmlspecialchars($req['Address']) ?><?= $req['Purok'] ? ', Purok ' . htmlspecialchars($req['Purok']) : '' ?></span>,
    <?= htmlspecialchars($barangay_name) ?>, <?= htmlspecialchars($municipality) ?>,
    is known to be a person of good moral character and has no derogatory record in this barangay
    as of the date of this certification.
  </p>
  <p class="body-text">
    This certification is issued upon the request of the above-named person
    for the purpose of:
  </p>
  <div class="purpose-box">
    <strong>Purpose:</strong>
    <?= htmlspecialchars($req['Purpose'] ?? 'General purposes') ?>
  </div>
  <p class="body-text">
    This clearance is valid for
    <span class="field">six (6) months</span>
    from the date of issue unless sooner revoked for cause.
  </p>

  <?php else: ?>
  <!-- INDIGENCY BODY -->
  <p class="body-text">
    This is to certify that
    <span class="field"><?= htmlspecialchars(trim($req['FullName'])) ?></span>,
    <?= $age ? $age . ' years old, ' : '' ?>
    <span class="field"><?= htmlspecialchars($req['Sex']) ?></span>,
    a resident of
    <span class="field"><?= htmlspecialchars($req['Address']) ?><?= $req['Purok'] ? ', Purok ' . htmlspecialchars($req['Purok']) : '' ?></span>,
    <?= htmlspecialchars($barangay_name) ?>, <?= htmlspecialchars($municipality) ?>,
    belongs to an indigent family and is in need of assistance.
  </p>

  <?php if ($indigency): ?>
  <table class="detail-table">
    <tr><td>Monthly Income</td><td>₱<?= number_format((float)($indigency['MonthlyIncome'] ?? 0), 2) ?></td></tr>
    <tr><td>Household Size</td><td><?= htmlspecialchars($indigency['HouseholdSize'] ?? '—') ?> member(s)</td></tr>
    <tr><td>Employment Status</td><td><?= htmlspecialchars($indigency['EmploymentStatus'] ?? '—') ?></td></tr>
    <?php if (!empty($indigency['IncomeSource'])): ?>
    <tr><td>Source of Income</td><td><?= htmlspecialchars($indigency['IncomeSource']) ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($indigency['AssistanceReceived'])): ?>
    <tr><td>Government Assistance</td><td><?= htmlspecialchars($indigency['AssistanceReceived']) ?></td></tr>
    <?php endif; ?>
  </table>
  <?php endif; ?>

  <p class="body-text">
    This certification is issued upon the request of the above-named person
    for the purpose of:
  </p>
  <div class="purpose-box">
    <strong>Purpose:</strong>
    <?= htmlspecialchars($req['Purpose'] ?? 'General purposes') ?>
  </div>
  <?php endif; ?>

  <!-- Signature block -->
  <div class="sig-section">
    <div class="sig-row">
      <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-name"><?= htmlspecialchars($captain['Name'] ?? 'Punong Barangay') ?></div>
        <div class="sig-title">Punong Barangay</div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="doc-footer">
    <span>
      <?php if ($req['ReceiptNo']): ?>
        O.R. No.: <?= htmlspecialchars($req['ReceiptNo']) ?> &nbsp;|&nbsp;
        Amount: ₱<?= number_format((float)($req['Amount'] ?? 0), 2) ?> &nbsp;|&nbsp;
        Method: <?= htmlspecialchars($req['PaymentMethod'] ?? '—') ?>
      <?php else: ?>
        No fee collected
      <?php endif; ?>
    </span>
    <span>Printed: <?= date('Y-m-d H:i') ?></span>
  </div>

</div><!-- /document -->
</body>
</html>
