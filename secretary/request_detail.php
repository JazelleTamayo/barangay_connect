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
        $msg = 'approved';
    } elseif ($action === 'reject') {
        $new_status = 'Rejected';
        $log = "\n[" . date('Y-m-d H:i:s') . "] Secretary Rejected: " . $remarks;
        $msg = 'rejected';
    } else {
        header("Location: request_detail.php?id=$request_id&msg=error");
        exit;
    }
    
    // Update status and append remarks
    $update = $pdo->prepare("
        UPDATE ServiceRequest 
        SET Status = ?, 
            Remarks = CONCAT(IFNULL(Remarks, ''), ?)
        WHERE RequestID = ?
    ");
    $update->execute([$new_status, $log, $request_id]);
    
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

            <div class="card">
                <div class="card-header">
                    <h3>Request Details</h3>
                </div>
                <div class="request-details">
                    <table class="info-table">
                        <tr><th>Reference No.</th><td><?= htmlspecialchars($request['ReferenceNo']) ?></td></tr>
                        <tr><th>Resident</th><td><?= htmlspecialchars($request['ResidentName']) ?></td></tr>
                        <tr><th>Address</th><td><?= nl2br(htmlspecialchars($request['Address'])) ?></td></tr>
                        <tr><th>Contact</th><td><?= htmlspecialchars($request['ContactNumber'] ?? '—') ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($request['ResidentEmail'] ?? '—') ?></td></tr>
                        <tr><th>Request Type</th><td><?= htmlspecialchars($request['RequestType']) ?></td></td>
                        <tr><th>Purpose</th><td><?= nl2br(htmlspecialchars($request['Purpose'] ?? '—')) ?></td></tr>
                        <tr><th>Current Status</th><td><span class="badge badge-<?= strtolower($request['Status']) ?>"><?= $request['Status'] ?></span></td></tr>
                        <tr><th>Submitted</th><td><?= date('M d, Y h:i A', strtotime($request['CreatedAt'])) ?></td></tr>
                        <?php if ($request['RequestType'] == 'FacilityReservation'): ?>
                            <tr><th>Facility</th><td><?= htmlspecialchars($request['FacilityName'] ?? '—') ?></td></tr>
                            <tr><th>Reservation Date</th><td><?= htmlspecialchars($request['ReservationDate'] ?? '—') ?></td></tr>
                            <tr><th>Time Slot</th><td><?= htmlspecialchars($request['TimeSlot'] ?? '—') ?></td></tr>
                        <?php elseif ($request['RequestType'] == 'Complaint'): ?>
                            <tr><th>Respondent</th><td><?= htmlspecialchars($request['RespondentName'] ?? '—') ?></td></tr>
                            <tr><th>Incident Date</th><td><?= htmlspecialchars($request['IncidentDate'] ?? '—') ?></td></tr>
                            <tr><th>Location</th><td><?= htmlspecialchars($request['IncidentLocation'] ?? '—') ?></td></tr>
                            <tr><th>Mediation Date</th><td><?= htmlspecialchars($request['MediationDate'] ?? 'Pending') ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Approval/Rejection Form (only if status is 'ForApproval') -->
            <?php if ($request['Status'] === 'ForApproval'): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Take Action</h3>
                </div>
                <form method="POST" class="form-vertical">
                    <div class="form-group">
                        <label>Remarks (reason if rejecting, optional if approving)</label>
                        <textarea name="remarks" class="form-textarea" rows="3" placeholder="Add any remarks..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="action" value="approve" class="btn btn-success">✅ Approve Request</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">❌ Reject Request</button>
                        <a href="request_processing.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                This request is <?= $request['Status'] ?> and cannot be changed here.
                <a href="request_processing.php">Back to list</a>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>
<style>
.info-table { width: 100%; border-collapse: collapse; }
.info-table th, .info-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
.info-table th { width: 180px; background: #f8fafc; }
.btn-success { background: #2e7d32; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
.btn-danger { background: #c62828; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
</style>
<?php include '../includes/footer.php'; ?>