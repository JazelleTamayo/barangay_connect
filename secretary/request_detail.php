<?php
// Barangay Connect – Request Detail (for Secretary)
// secretary/request_detail.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo = get_db();
$user_id = $_SESSION['user_id'];

// Get request ID from URL
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$request_id) {
    header('Location: request_processing.php');
    exit;
}

// Fetch request details with resident info and additional data
$stmt = $pdo->prepare("
    SELECT sr.*, 
           CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
           r.Address, r.ContactNumber, r.Email as ResidentEmail,
           f.FacilityName, fr.ReservationDate, fr.TimeSlot,
           c.RespondentName, c.IncidentDate, c.IncidentLocation, c.MediationDate
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

// Handle approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    if ($action === 'approve') {
        $new_status = 'Approved';
        $log = "\n[" . date('Y-m-d H:i:s') . "] Secretary Approved: " . $remarks;
        $update = $pdo->prepare("
            UPDATE ServiceRequest 
            SET Status = ?, 
                Remarks = CONCAT(IFNULL(Remarks, ''), ?)
            WHERE RequestID = ?
        ");
        $update->execute([$new_status, $log, $request_id]);
        $msg = 'approved';

    } elseif ($action === 'reject') {
        $new_status = 'Rejected';
        $log = "\n[" . date('Y-m-d H:i:s') . "] Secretary Rejected: " . $remarks;
        $update = $pdo->prepare("
            UPDATE ServiceRequest 
            SET Status = ?,
                Remarks = CONCAT(IFNULL(Remarks, ''), ?)
            WHERE RequestID = ?
        ");
        $update->execute([$new_status, $log, $request_id]);
        $msg = 'rejected';

    } else {
        header("Location: request_detail.php?id=$request_id&msg=error");
        exit;
    }

    header("Location: request_processing.php?msg=$msg");
    exit;
}

$page_title = 'Review Request';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Review Request</h1>
            <span class="page-subtitle"><?= htmlspecialchars($request['ReferenceNo']) ?></span>
        </div>
        <div class="page-body">

            <!-- Request Details -->
            <div class="card">
                <div class="card-header">
                    <h3>Request Details</h3>
                </div>
                <div class="request-details">
                    <table class="info-table">
                        <tr>
                            <th>Reference No.</th>
                            <td><?= htmlspecialchars($request['ReferenceNo']) ?></td>
                        </tr>
                        <tr>
                            <th>Resident</th>
                            <td><?= htmlspecialchars($request['ResidentName']) ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?= nl2br(htmlspecialchars($request['Address'])) ?></td>
                        </tr>
                        <tr>
                            <th>Contact</th>
                            <td><?= htmlspecialchars($request['ContactNumber'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= htmlspecialchars($request['ResidentEmail'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th>Request Type</th>
                            <td><?= htmlspecialchars($request['RequestType']) ?></td>
                        </tr>
                        <tr>
                            <th>Purpose</th>
                            <td><?= nl2br(htmlspecialchars($request['Purpose'] ?? '—')) ?></td>
                        </tr>
                        <tr>
                            <th>Current Status</th>
                            <td><span
                                    class="badge badge-<?= strtolower($request['Status']) ?>"><?= $request['Status'] ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Submitted</th>
                            <td><?= date('M d, Y h:i A', strtotime($request['CreatedAt'])) ?></td>
                        </tr>
                        <?php if ($request['RequestType'] == 'FacilityReservation'): ?>
                            <tr>
                                <th>Facility</th>
                                <td><?= htmlspecialchars($request['FacilityName'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Reservation Date</th>
                                <td><?= htmlspecialchars($request['ReservationDate'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Time Slot</th>
                                <td><?= htmlspecialchars($request['TimeSlot'] ?? '—') ?></td>
                            </tr>
                        <?php elseif ($request['RequestType'] == 'Complaint'): ?>
                            <tr>
                                <th>Respondent</th>
                                <td><?= htmlspecialchars($request['RespondentName'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Incident Date</th>
                                <td><?= htmlspecialchars($request['IncidentDate'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Location</th>
                                <td><?= htmlspecialchars($request['IncidentLocation'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Mediation Date</th>
                                <td><?= htmlspecialchars($request['MediationDate'] ?? 'Pending') ?></td>
                            </tr>
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

                    // Keep only lines that are Staff lines, strip Secretary action logs
                    $lines = explode("\n", $raw_remarks);
                    $staff_lines = array_filter($lines, function ($line) {
                        $trimmed = trim($line);
                        if (empty($trimmed))
                            return false;
                        // Strip secretary log lines only
                        if (preg_match('/Secretary\s+(Approved|Rejected)/i', $trimmed))
                            return false;
                        return true;
                    });

                    // Clean up the timestamp prefix from staff lines for display
                    $display_lines = array_map(function ($line) {
                        // Remove [YYYY-MM-DD HH:MM:SS] Staff: prefix, keep just the message
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
            
            <!-- Approval/Rejection Form -->
            <?php if ($request['Status'] === 'ForApproval'): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Take Action</h3>
                    </div>
                    <div class="action-box">
                        <form method="POST" id="actionForm">
                            <input type="hidden" name="action" id="actionInput" value="">
                            <div class="form-group">
                                <label class="form-label">Remarks <span
                                        style="color:#6b7280;font-weight:400;">(optional)</span></label>
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
                                <a href="request_processing.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="margin-top: 1rem;">
                    This request is <strong><?= htmlspecialchars($request['Status']) ?></strong> and cannot be modified.
                    <a href="request_processing.php" style="margin-left:8px;">← Back to list</a>
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
    /* Info Table */
    .info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table th,
    .info-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
    }

    .info-table th {
        width: 180px;
        background: #f8fafc;
        color: #374151;
        font-weight: 600;
    }

    .info-table tr:last-child th,
    .info-table tr:last-child td {
        border-bottom: none;
    }

    /* Staff Remarks Box */
    .remarks-box {
        padding: 16px 20px;
    }

    .remarks-text {
        margin: 0 0 10px 0;
        padding: 14px 16px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        color: #1f2937;
        font-size: 0.95rem;
        line-height: 1.7;
        white-space: pre-wrap;
    }

    .remarks-empty {
        margin: 0 0 10px 0;
        color: #9ca3af;
        font-style: italic;
    }

    .remarks-hint {
        color: #6b7280;
        font-size: 0.8rem;
    }

    /* Action Box */
    .action-box {
        padding: 20px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        color: #374151;
    }

    .form-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.95rem;
        line-height: 1.5;
        resize: vertical;
        box-sizing: border-box;
        font-family: inherit;
        color: #1f2937;
    }

    .form-textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .form-hint {
        color: #6b7280;
        font-size: 0.8rem;
        display: block;
        margin-top: 6px;
        line-height: 1.5;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
        align-items: center;
    }

    /* Buttons */
    .btn {
        padding: 9px 18px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
    }

    .btn-success {
        background: #2e7d32;
        color: white;
    }

    .btn-success:hover {
        background: #1b5e20;
    }

    .btn-danger {
        background: #c62828;
        color: white;
    }

    .btn-danger:hover {
        background: #7f0000;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }
</style>

<?php include '../includes/footer.php'; ?>