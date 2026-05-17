<?php
// Barangay Connect – Captain Request Detail
// captain/request_detail.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../classes/AuditLog.php';
require_role('captain');

$pdo = get_db();
$user_id = $_SESSION['user_id'];

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$request_id) {
    header('Location: final_approvals.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT sr.*, 
           CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
           r.Address, r.ContactNumber, r.Email as ResidentEmail,
           f.FacilityName, fr.ReservationDate, fr.TimeSlot,
           c.RespondentName, c.RespondentContact, c.RespondentRelationship,
           c.IncidentDate, c.IncidentLocation, c.MediationDate,
           c.Witnesses, c.ReliefSought,
           fr.EventName, fr.ExpectedAttendees, fr.ContactPerson, fr.ContactPersonNumber
    FROM ServiceRequest sr
    JOIN Resident r ON sr.ResidentID = r.ResidentID
    LEFT JOIN FacilityReservation fr ON sr.RequestID = fr.RequestID
    LEFT JOIN Facility f ON fr.FacilityID = f.FacilityID
    LEFT JOIN Complaint c ON sr.RequestID = c.RequestID
    WHERE sr.RequestID = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Request not found.");
}

$indigency = null;
if ($request['RequestType'] === 'Indigency') {
    $stmt = $pdo->prepare("SELECT * FROM IndigencyDetail WHERE RequestID = ?");
    $stmt->execute([$request_id]);
    $indigency = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');
    $audit = new AuditLog();

    if (($request['Status'] ?? '') !== 'ForApproval') {
        header("Location: request_detail.php?id=$request_id&msg=not_actionable");
        exit;
    }

    if ($action === 'approve') {
        if (($request['RequestType'] ?? '') === 'Clearance') {
            require_once '../classes/Resident.php';
            $residentClass = new Resident();
            if (!$residentClass->isInGoodStanding((int)$request['ResidentID'])) {
                header("Location: request_detail.php?id=$request_id&msg=not_good_standing");
                exit;
            }
        }

        if (($request['RequestType'] ?? '') === 'FacilityReservation') {
            $priority = trim($_POST['reservation_priority'] ?? '');
            $validPriorities = ['Official Barangay Activity', 'Community Event', 'Private Event'];
            if (!in_array($priority, $validPriorities, true)) {
                header("Location: request_detail.php?id=$request_id&msg=priority_required");
                exit;
            }
            $pdo->prepare("UPDATE FacilityReservation SET Priority = ? WHERE RequestID = ?")
                ->execute([$priority, $request_id]);
        }

        $new_status = 'Approved';
        $log = "\n[" . date('Y-m-d H:i:s') . "] Captain Approved: " . $remarks;
        $update = $pdo->prepare("
            UPDATE ServiceRequest 
            SET Status      = ?,
                ProcessedBy = ?,
                ProcessedAt = NOW(),
                Remarks     = CONCAT(IFNULL(Remarks, ''), ?)
            WHERE RequestID = ? AND Status = 'ForApproval'
        ");
        $update->execute([$new_status, $user_id, $log, $request_id]);
        if ($update->rowCount() === 0) {
            header("Location: request_detail.php?id=$request_id&msg=not_actionable");
            exit;
        }

        $audit->log(
            "Captain approved " . $request['RequestType'] . " request" . ($remarks ? ": $remarks" : ''),
            "RequestID: $request_id | RefNo: " . ($request['ReferenceNo'] ?? '')
        );
        $msg = 'approved';

    } elseif ($action === 'reject') {
        if ($remarks === '') {
            header("Location: request_detail.php?id=$request_id&msg=reason_required");
            exit;
        }
        $new_status = 'Rejected';
        $log = "\n[" . date('Y-m-d H:i:s') . "] Captain Rejected: " . $remarks;
        $update = $pdo->prepare("
            UPDATE ServiceRequest 
            SET Status          = ?,
                RejectionReason = ?,
                Remarks         = CONCAT(IFNULL(Remarks, ''), ?)
            WHERE RequestID = ? AND Status = 'ForApproval'
        ");
        $update->execute([$new_status, $remarks, $log, $request_id]);
        if ($update->rowCount() === 0) {
            header("Location: request_detail.php?id=$request_id&msg=not_actionable");
            exit;
        }

        $audit->log(
            "Captain rejected " . $request['RequestType'] . " request: $remarks",
            "RequestID: $request_id | RefNo: " . ($request['ReferenceNo'] ?? '')
        );
        $msg = 'rejected';

    } elseif ($action === 'cancel') {
        $cancel_reason = trim($_POST['cancel_reason'] ?? '');
        if ($cancel_reason === '') {
            header("Location: request_detail.php?id=$request_id&msg=reason_required");
            exit;
        }
        $update = $pdo->prepare("
            UPDATE ServiceRequest
            SET Status             = 'Cancelled',
                CancelledBy        = ?,
                CancelledAt        = NOW(),
                CancellationReason = ?
            WHERE RequestID = ? AND Status IN ('ForApproval', 'Approved', 'Pending')
        ");
        $update->execute([$user_id, $cancel_reason, $request_id]);
        if ($update->rowCount() === 0) {
            header("Location: request_detail.php?id=$request_id&msg=not_actionable");
            exit;
        }

        $audit->log(
            "Captain cancelled " . $request['RequestType'] . " request: $cancel_reason",
            "RequestID: $request_id | RefNo: " . ($request['ReferenceNo'] ?? '')
        );
        $msg = 'cancelled';

    } else {
        header("Location: request_detail.php?id=$request_id&msg=error");
        exit;
    }

    header("Location: final_approvals.php?msg=$msg");
    exit;
}

$page_title    = 'Review Request';
$page_back_url = 'final_approvals.php';
$page_subtitle = $request['ReferenceNo'] ?? '';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-body">

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'not_good_standing'): ?>
                    <div class="alert alert-error">⚠️ This resident has an unresolved complaint as respondent and cannot be issued a Clearance.</div>
                <?php elseif ($_GET['msg'] === 'priority_required'): ?>
                    <div class="alert alert-error">⚠️ Please select a reservation priority level before approving.</div>
                <?php elseif ($_GET['msg'] === 'reason_required'): ?>
                    <div class="alert alert-error">⚠️ A reason is required for rejection or cancellation.</div>
                <?php elseif ($_GET['msg'] === 'not_actionable'): ?>
                    <div class="alert alert-error">⚠️ This request could not be updated. It may have already been actioned.</div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Request Details -->
            <div class="card">
                <div class="card-header">
                    <h3>Request Details</h3>
                    <a href="final_approvals.php" class="btn btn-secondary btn-small">← Back</a>
                </div>
                <div class="request-details">
                    <table class="info-table">
                        <tr><th>Reference No.</th><td><?= htmlspecialchars($request['ReferenceNo'] ?? '—') ?></td></tr>
                        <tr><th>Resident</th><td><?= htmlspecialchars($request['ResidentName'] ?? '—') ?></td></tr>
                        <tr><th>Address</th><td><?= nl2br(htmlspecialchars($request['Address'] ?? '—')) ?></td></tr>
                        <tr><th>Contact</th><td><?= htmlspecialchars($request['ContactNumber'] ?? '—') ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($request['ResidentEmail'] ?? '—') ?></td></tr>
                        <tr><th>Request Type</th><td><?= htmlspecialchars($request['RequestType'] ?? '—') ?></td></tr>
                        <tr><th>Purpose</th><td><?= nl2br(htmlspecialchars($request['Purpose'] ?? '—')) ?></td></tr>
                        <tr><th>Current Status</th>
                            <td><span class="badge badge-<?= strtolower($request['Status'] ?? '') ?>"><?= htmlspecialchars($request['Status'] ?? '—') ?></span></td>
                        </tr>
                        <tr><th>Submitted</th><td><?= isset($request['CreatedAt']) ? date('M d, Y h:i A', strtotime($request['CreatedAt'])) : '—' ?></td></tr>
                        <?php if (($request['RequestType'] ?? '') == 'FacilityReservation'): ?>
                            <tr><th>Facility</th><td><?= htmlspecialchars($request['FacilityName'] ?? '—') ?></td></tr>
                            <tr><th>Reservation Date</th><td><?= htmlspecialchars($request['ReservationDate'] ?? '—') ?></td></tr>
                            <tr><th>Time Slot</th><td><?= htmlspecialchars($request['TimeSlot'] ?? '—') ?></td></tr>
                            <tr><th>Event Name</th><td><?= htmlspecialchars($request['EventName'] ?? '—') ?><tr></tr>
                            <tr><th>Expected Attendees</th><td><?= htmlspecialchars($request['ExpectedAttendees'] ?? '—') ?></td></tr>
                            <tr><th>Contact Person</th><td><?= htmlspecialchars($request['ContactPerson'] ?? '—') ?></td></tr>
                            <tr><th>Contact Person No.</th><td><?= htmlspecialchars($request['ContactPersonNumber'] ?? '—') ?></td></tr>
                        <?php elseif (($request['RequestType'] ?? '') == 'Complaint'): ?>
                            <tr><th>Respondent</th><td><?= htmlspecialchars($request['RespondentName'] ?? '—') ?></td></tr>
                            <tr><th>Respondent Contact</th><td><?= htmlspecialchars($request['RespondentContact'] ?? '—') ?></td></tr>
                            <tr><th>Relationship</th><td><?= htmlspecialchars($request['RespondentRelationship'] ?? '—') ?></td></tr>
                            <tr><th>Incident Date</th><td><?= htmlspecialchars($request['IncidentDate'] ?? '—') ?></td></tr>
                            <tr><th>Location</th><td><?= htmlspecialchars($request['IncidentLocation'] ?? '—') ?></td></tr>
                            <tr><th>Witnesses</th><td><?= htmlspecialchars($request['Witnesses'] ?? '—') ?></td></tr>
                            <tr><th>Relief Sought</th><td><?= htmlspecialchars($request['ReliefSought'] ?? '—') ?></td></tr>
                            <tr><th>Mediation Date</th><td><?= htmlspecialchars($request['MediationDate'] ?? 'Pending') ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Staff Remarks -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Staff Remarks</h3>
                </div>
                <div class="remarks-box">
                    <?php
                    $raw_remarks = trim($request['Remarks'] ?? '');
                    $lines = explode("\n", $raw_remarks);
                    $staff_lines = array_filter($lines, function ($line) {
                        $trimmed = trim($line);
                        if (empty($trimmed)) return false;
                        if (preg_match('/(?:Secretary|Captain)\s+(Approved|Rejected)/i', $trimmed)) return false;
                        return true;
                    });
                    $display_lines = array_map(function ($line) {
                        return preg_replace('/^\[?\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]?\s*Staff:\s*/i', '', trim($line));
                    }, $staff_lines);
                    $clean_remarks = trim(implode("\n", $display_lines));
                    ?>
                    <?php if (empty($clean_remarks)): ?>
                        <p class="remarks-empty">No remarks from staff.</p>
                    <?php else: ?>
                        <p class="remarks-text"><?= nl2br(htmlspecialchars($clean_remarks)) ?></p>
                    <?php endif; ?>
                    <small class="remarks-hint">📝 Remarks added by staff when they forwarded this request.</small>
                </div>
            </div>

            <!-- Indigency Details -->
            <?php if (($request['RequestType'] ?? '') === 'Indigency' && $indigency): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>📋 Financial Assessment Details</h3>
                    </div>
                    <div class="card-body">
                        <table class="info-table">
                            <tr><th>Monthly Income</th><td>₱<?= number_format($indigency['MonthlyIncome'] ?? 0, 2) ?></td></tr>
                            <tr><th>Household Size</th><td><?= htmlspecialchars($indigency['HouseholdSize'] ?? '—') ?></td></tr>
                            <tr><th>Employment Status</th><td><?= htmlspecialchars($indigency['EmploymentStatus'] ?? '—') ?></td></tr>
                            <tr><th>Source of Income</th><td><?= nl2br(htmlspecialchars($indigency['IncomeSource'] ?? '—')) ?></td></tr>
                            <tr><th>Government Assistance</th><td><?= nl2br(htmlspecialchars($indigency['AssistanceReceived'] ?? '—')) ?></td></tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Panel -->
            <?php if (in_array($request['Status'] ?? '', ['ForApproval', 'Approved', 'Pending'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Take Action</h3>
                    </div>
                    <div class="action-box">
                        <?php if (($request['Status'] ?? '') === 'ForApproval'): ?>
                        <form method="POST" id="actionForm">
                            <input type="hidden" name="action" id="actionInput" value="">

                            <?php if (($request['RequestType'] ?? '') === 'FacilityReservation'): ?>
                            <div class="form-group">
                                <label class="form-label">Reservation Priority <span style="color:#dc2626;">*</span></label>
                                <select name="reservation_priority" class="form-textarea" style="resize:none;padding:8px 12px;" required>
                                    <option value="">-- Select Priority Level --</option>
                                    <option value="Official Barangay Activity">Official Barangay Activity</option>
                                    <option value="Community Event">Community Event</option>
                                    <option value="Private Event">Private Event</option>
                                </select>
                                <small class="form-hint">BR-08: Priority determines scheduling preference for the facility.</small>
                            </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label class="form-label">Remarks <span style="color:#6b7280;font-weight:400;">(optional for approval, required for rejection)</span></label>
                                <textarea name="remarks" class="form-textarea" rows="3"
                                    placeholder="Add a reason for rejection, or a note for approval..."></textarea>
                                <small class="form-hint">
                                    For rejection: this reason will be visible to the resident.<br>
                                    For approval: this is an internal note only.
                                </small>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-success" onclick="submitAction('approve')">
                                    ✅ Approve Request
                                </button>
                                <button type="button" class="btn btn-danger" onclick="submitAction('reject')">
                                    ❌ Reject Request
                                </button>
                                <a href="final_approvals.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cancel -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>❌ Cancel Request</h3>
                        <span class="card-subtitle">Use only if the request is invalid, fraudulent, or filed in error.</span>
                    </div>
                    <div class="action-box">
                        <form method="POST" id="cancelForm">
                            <input type="hidden" name="action" value="cancel">
                            <div class="form-group">
                                <label class="form-label">Cancellation Reason <span style="color:#dc2626;">*</span></label>
                                <textarea name="cancel_reason" class="form-textarea" rows="3" required
                                    placeholder="Why is this request being cancelled?"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-danger"
                                    onclick="if(confirm('Cancel this request? This cannot be undone.')) document.getElementById('cancelForm').submit()">
                                    Cancel This Request
                                </button>
                                <a href="final_approvals.php" class="btn btn-secondary">Go Back</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-info" style="margin-top: 1rem;">
                    This request is <strong><?= htmlspecialchars($request['Status'] ?? 'Unknown') ?></strong> and cannot be modified.
                    Use <a href="system_override.php">System Override</a> if needed.
                    <a href="final_approvals.php" style="margin-left:8px;">← Back to list</a>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
    function submitAction(action) {
        if (action === 'reject') {
            if (!confirm('Are you sure you want to REJECT this request?')) return;
        } else {
            if (!confirm('Are you sure you want to APPROVE this request?')) return;
        }
        document.getElementById('actionInput').value = action;
        document.getElementById('actionForm').submit();
    }
</script>

<style>
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table th, .info-table td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
    .info-table th { width: 180px; background: #f8fafc; color: #374151; font-weight: 600; }
    .info-table tr:last-child th, .info-table tr:last-child td { border-bottom: none; }
    .remarks-box { padding: 16px 20px; }
    .remarks-text { margin: 0 0 10px 0; padding: 14px 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; color: #1f2937; font-size: 0.95rem; line-height: 1.7; white-space: pre-wrap; }
    .remarks-empty { margin: 0 0 10px 0; color: #9ca3af; font-style: italic; }
    .remarks-hint { color: #6b7280; font-size: 0.8rem; }
    .card-body { padding: 20px; }
    .action-box { padding: 20px; }
    .form-label { display: block; font-weight: 600; margin-bottom: 6px; color: #374151; }
    .form-textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; line-height: 1.5; resize: vertical; box-sizing: border-box; font-family: inherit; color: #1f2937; }
    .form-textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .form-hint { color: #6b7280; font-size: 0.8rem; display: block; margin-top: 6px; line-height: 1.5; }
    .form-actions { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; align-items: center; }
    .btn { padding: 9px 18px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9rem; font-weight: 500; text-decoration: none; display: inline-block; }
    .btn-success { background: #2e7d32; color: white; }
    .btn-success:hover { background: #1b5e20; }
    .btn-danger { background: #c62828; color: white; }
    .btn-danger:hover { background: #7f0000; }
    .btn-secondary { background: #6b7280; color: white; }
    .btn-secondary:hover { background: #4b5563; }
    .mt-4 { margin-top: 1rem; }
    .card-subtitle { font-size: 0.82rem; color: #6b7280; font-weight: 400; }
</style>

<?php include '../includes/footer.php'; ?>