<?php
// Barangay Connect – Complaint Detail (Secretary)
// secretary/complaint_detail.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo     = get_db();
$user_id = $_SESSION['user_id'];

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$request_id) {
    header('Location: complaint_management.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT sr.*,
           CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
           r.Address, r.ContactNumber, r.Email AS ResidentEmail,
           c.RespondentName, c.RespondentContact, c.RespondentRelationship,
           c.IncidentDate, c.IncidentLocation, c.MediationDate,
           c.Witnesses, c.ReliefSought, c.Description
    FROM ServiceRequest sr
    JOIN Resident r       ON sr.ResidentID = r.ResidentID
    LEFT JOIN Complaint c ON sr.RequestID  = c.RequestID
    WHERE sr.RequestID = ?
      AND sr.RequestType = 'Complaint'
");
$stmt->execute([$request_id]);
$complaint = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$complaint) {
    header('Location: complaint_management.php?msg=not_found');
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    if ($action === 'approve') {
        $log = "\n[" . date('Y-m-d H:i:s') . "] Secretary Approved: " . $remarks;
        $pdo->prepare("
            UPDATE ServiceRequest
            SET Status  = 'Approved',
                Remarks = CONCAT(IFNULL(Remarks,''), ?)
            WHERE RequestID = ?
        ")->execute([$log, $request_id]);
        header("Location: complaint_management.php?msg=updated");
        exit;

    } elseif ($action === 'reject') {
        $reason = $remarks ?: 'No reason provided.';
        $log    = "\n[" . date('Y-m-d H:i:s') . "] Secretary Rejected: " . $remarks;
        $pdo->prepare("
            UPDATE ServiceRequest
            SET Status          = 'Rejected',
                RejectionReason = ?,
                Remarks         = CONCAT(IFNULL(Remarks,''), ?)
            WHERE RequestID = ?
        ")->execute([$reason, $log, $request_id]);
        header("Location: complaint_management.php?msg=updated");
        exit;

    } elseif ($action === 'cancel') {
        $reason = trim($_POST['cancel_reason'] ?? '') ?: 'Cancelled by Secretary.';
        $pdo->prepare("
            UPDATE ServiceRequest
            SET Status             = 'Cancelled',
                CancelledBy        = ?,
                CancelledAt        = NOW(),
                CancellationReason = ?
            WHERE RequestID = ?
        ")->execute([$user_id, $reason, $request_id]);
        header("Location: complaint_management.php?msg=updated");
        exit;
    }
}

$page_title = 'Complaint Detail';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-header" style="display:flex; align-items:center; gap:16px; justify-content:flex-start;">
            <a href="complaint_management.php" class="btn-back" title="Back">&#8592;</a>
            <div>
                <h1 style="margin:0;">Complaint Detail</h1>
                <span class="page-subtitle"><?= htmlspecialchars($complaint['ReferenceNo'] ?? '') ?></span>
            </div>
        </div>

        <div class="page-body">

            <!-- Complaint Info -->
            <div class="card">
                <div class="card-header"><h3>Complaint Information</h3></div>
                <table class="info-table">
                    <tr><th>Reference No.</th>     <td><?= htmlspecialchars($complaint['ReferenceNo'] ?? '—') ?></td></tr>
                    <tr><th>Complainant</th>        <td><?= htmlspecialchars($complaint['ResidentName'] ?? '—') ?></td></tr>
                    <tr><th>Address</th>            <td><?= nl2br(htmlspecialchars($complaint['Address'] ?? '—')) ?></td></tr>
                    <tr><th>Contact</th>            <td><?= htmlspecialchars($complaint['ContactNumber'] ?? '—') ?></td></tr>
                    <tr><th>Email</th>              <td><?= htmlspecialchars($complaint['ResidentEmail'] ?? '—') ?></td></tr>
                    <tr><th>Status</th>
                        <td>
                            <span class="badge badge-<?= strtolower($complaint['Status'] ?? '') ?>">
                                <?= htmlspecialchars($complaint['Status'] ?? '—') ?>
                            </span>
                        </td>
                    </tr>
                    <tr><th>Submitted</th>          <td><?= isset($complaint['CreatedAt']) ? date('M d, Y h:i A', strtotime($complaint['CreatedAt'])) : '—' ?></td></tr>
                    <tr><th>Respondent</th>         <td><?= htmlspecialchars($complaint['RespondentName'] ?? '—') ?></td></tr>
                    <tr><th>Respondent Contact</th> <td><?= htmlspecialchars($complaint['RespondentContact'] ?? '—') ?></td></tr>
                    <tr><th>Relationship</th>       <td><?= htmlspecialchars($complaint['RespondentRelationship'] ?? '—') ?></td></tr>
                    <tr><th>Incident Date</th>      <td><?= htmlspecialchars($complaint['IncidentDate'] ?? '—') ?></td></tr>
                    <tr><th>Incident Location</th>  <td><?= htmlspecialchars($complaint['IncidentLocation'] ?? '—') ?></td></tr>
                    <tr><th>Witnesses</th>          <td><?= nl2br(htmlspecialchars($complaint['Witnesses'] ?? '—')) ?></td></tr>
                    <tr><th>Relief Sought</th>      <td><?= nl2br(htmlspecialchars($complaint['ReliefSought'] ?? '—')) ?></td></tr>
                    <tr><th>Mediation Date</th>     <td><?= htmlspecialchars($complaint['MediationDate'] ?? 'Not yet scheduled') ?></td></tr>
                    <tr><th>Complaint Details</th>  <td><?= nl2br(htmlspecialchars($complaint['Description'] ?? '—')) ?></td></tr>
                    <tr><th>Purpose</th>            <td><?= nl2br(htmlspecialchars($complaint['Purpose'] ?? '—')) ?></td></tr>
                </table>
            </div>

            <!-- Staff Remarks -->
            <div class="card mt-4">
                <div class="card-header"><h3>Staff Remarks</h3></div>
                <div class="remarks-box">
                    <?php
                    $raw   = trim($complaint['Remarks'] ?? '');
                    $lines = array_filter(explode("\n", $raw), function($l) {
                        $t = trim($l);
                        return $t !== '' && !preg_match('/Secretary\s+(Approved|Rejected)/i', $t);
                    });
                    $clean = trim(implode("\n", array_map(fn($l) =>
                        preg_replace('/^\[?\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]?\s*Staff:\s*/i', '', trim($l)), $lines)));
                    ?>
                    <?php if (empty($clean)): ?>
                        <p class="remarks-empty">No remarks from staff.</p>
                    <?php else: ?>
                        <p class="remarks-text"><?= nl2br(htmlspecialchars($clean)) ?></p>
                    <?php endif; ?>
                    <small class="remarks-hint">📝 Remarks added by staff when they forwarded this complaint.</small>
                </div>
            </div>

            <!-- Action Form -->
           <?php if (in_array($complaint['Status'] ?? '', ['ForApproval', 'Pending'])): ?>
                <div class="card mt-4">
                    <div class="card-header"><h3>Take Action</h3></div>
                    <div class="action-box">
                        <form method="POST" id="actionForm">
                            <input type="hidden" name="action" id="actionInput" value="">
                            <div class="form-group">
                                <label class="form-label">Remarks <span style="color:#6b7280;font-weight:400;">(optional)</span></label>
                                <textarea name="remarks" class="form-textarea" rows="3"
                                    placeholder="Add a reason for rejection, or a note for approval..."></textarea>
                                <small class="form-hint">
                                    For rejection: this reason will be visible to the resident.<br>
                                    For approval: this is an internal note only.
                                </small>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-success" onclick="submitAction('approve')">Approve</button>
                                <button type="button" class="btn btn-danger"  onclick="submitAction('reject')">Reject</button>
                                <a href="complaint_management.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Cancel Complaint</h3>
                        <span class="card-subtitle">Use only if the complaint is invalid or filed in error.</span>
                    </div>
                    <div class="action-box">
                        <form method="POST" id="cancelForm">
                            <input type="hidden" name="action" value="cancel">
                            <div class="form-group">
                                <label class="form-label">Cancellation Reason <span style="color:#dc2626;">*</span></label>
                                <textarea name="cancel_reason" class="form-textarea" rows="3" required
                                    placeholder="Why is this complaint being cancelled?"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-danger"
                                    onclick="if(confirm('Cancel this complaint? This cannot be undone.')) document.getElementById('cancelForm').submit()">
                                    Cancel This Complaint
                                </button>
                                <a href="complaint_management.php" class="btn btn-secondary">Go Back</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-info" style="margin-top:1rem;">
                    This complaint is <strong><?= htmlspecialchars($complaint['Status'] ?? 'Unknown') ?></strong> and cannot be modified.
                    <a href="complaint_management.php" style="margin-left:8px;">← Back to list</a>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
function submitAction(action) {
    var msg = action === 'reject' ? 'REJECT this complaint?' : 'APPROVE this complaint?';
    if (!confirm('Are you sure you want to ' + msg)) return;
    document.getElementById('actionInput').value = action;
    document.getElementById('actionForm').submit();
}
</script>

<style>
.info-table { width:100%; border-collapse:collapse; }
.info-table th, .info-table td { padding:10px 14px; border-bottom:1px solid #e2e8f0; text-align:left; vertical-align:top; }
.info-table th { width:180px; background:#f8fafc; color:#374151; font-weight:600; }
.info-table tr:last-child th, .info-table tr:last-child td { border-bottom:none; }
.remarks-box { padding:16px 20px; }
.remarks-text { margin:0 0 10px; padding:14px 16px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; color:#1f2937; font-size:.95rem; line-height:1.7; white-space:pre-wrap; }
.remarks-empty { margin:0 0 10px; color:#9ca3af; font-style:italic; }
.remarks-hint { color:#6b7280; font-size:.8rem; }
.action-box { padding:20px; }
.form-label { display:block; font-weight:600; margin-bottom:6px; color:#374151; }
.form-textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:.95rem; line-height:1.5; resize:vertical; box-sizing:border-box; font-family:inherit; color:#1f2937; }
.form-textarea:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.form-hint { color:#6b7280; font-size:.8rem; display:block; margin-top:6px; line-height:1.5; }
.form-actions { display:flex; gap:10px; margin-top:20px; flex-wrap:wrap; align-items:center; }
.btn { padding:9px 18px; border-radius:6px; border:none; cursor:pointer; font-size:.9rem; font-weight:500; text-decoration:none; display:inline-block; }
.btn-success { background:#2e7d32; color:white; } .btn-success:hover { background:#1b5e20; }
.btn-danger  { background:#c62828; color:white; } .btn-danger:hover  { background:#7f0000; }
.btn-secondary { background:#6b7280; color:white; } .btn-secondary:hover { background:#4b5563; }
.btn-back { display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px; border-radius:50%; background:#f1f5f9; color:#374151; font-size:1.2rem; text-decoration:none; border:1px solid #e2e8f0; flex-shrink:0; transition:background .15s; }
.btn-back:hover { background:#e2e8f0; }
.mt-4 { margin-top:1rem; }
</style>

<?php include '../includes/footer.php'; ?>