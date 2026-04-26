<?php
// Barangay Connect – Track Request (with amounts)
// resident/track_request.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../config/settings.php';   // provides get_setting()
require_role('resident');

$page_title = 'Track Request';
include '../includes/header.php';

// Get logged‑in resident's ResidentID
$user_id = $_SESSION['user_id'];
$pdo = get_db();

$stmt = $pdo->prepare("SELECT ResidentID FROM UserAccount WHERE UserAccountID = ?");
$stmt->execute([$user_id]);
$resident = $stmt->fetch();
$resident_id = $resident ? $resident['ResidentID'] : null;

// Load clearance fee from database (default 50.00)
$clearance_fee = get_setting('clearance_fee', 50.00);

// Search & filter parameters
$ref = trim($_GET['ref'] ?? '');
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';

// ---------- ALL REQUESTS (with expected amount) ----------
$all_requests = [];
if ($resident_id) {
    $sql = "
        SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType, sr.CreatedAt, sr.Status, sr.Remarks,
            CASE sr.RequestType
                WHEN 'Clearance' THEN :clearance_fee
                WHEN 'Indigency' THEN 0
                WHEN 'Complaint' THEN 0
                WHEN 'FacilityReservation' THEN COALESCE(f.ReservationFee, 0)
            END AS expected_amount
        FROM ServiceRequest sr
        LEFT JOIN FacilityReservation fr ON sr.RequestID = fr.RequestID
        LEFT JOIN Facility f ON fr.FacilityID = f.FacilityID
        WHERE sr.ResidentID = :resident_id
    ";
    $params = [':clearance_fee' => $clearance_fee, ':resident_id' => $resident_id];

    if (!empty($filter_type)) {
        $sql .= " AND sr.RequestType = :type";
        $params[':type'] = $filter_type;
    }
    if (!empty($filter_status)) {
        $sql .= " AND sr.Status = :status";
        $params[':status'] = $filter_status;
    }
    $sql .= " ORDER BY sr.CreatedAt DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_requests = $stmt->fetchAll();
}

// ---------- SEARCH RESULT (with payment details) ----------
$search_result = null;
if (!empty($ref) && $resident_id) {
    $sql = "
        SELECT sr.*,
            r.FirstName, r.LastName,
            f.FacilityName, fr.ReservationDate, fr.TimeSlot, f.ReservationFee AS facility_fee,
            c.RespondentName, c.IncidentDate, c.MediationDate,
            p.Amount AS paid_amount, p.ReceiptNo, p.PaymentMethod, p.RecordedAt,
            CASE sr.RequestType
                WHEN 'Clearance' THEN :clearance_fee
                WHEN 'Indigency' THEN 0
                WHEN 'Complaint' THEN 0
                WHEN 'FacilityReservation' THEN COALESCE(f.ReservationFee, 0)
            END AS expected_amount
        FROM ServiceRequest sr
        LEFT JOIN Resident r ON sr.ResidentID = r.ResidentID
        LEFT JOIN FacilityReservation fr ON sr.RequestID = fr.RequestID
        LEFT JOIN Facility f ON fr.FacilityID = f.FacilityID
        LEFT JOIN Complaint c ON sr.RequestID = c.RequestID
        LEFT JOIN Payment p ON sr.RequestID = p.RequestID
        WHERE sr.ReferenceNo = :ref AND sr.ResidentID = :resident_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':clearance_fee' => $clearance_fee,
        ':ref' => $ref,
        ':resident_id' => $resident_id
    ]);
    $search_result = $stmt->fetch();
}
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Track Request</h1>
            <span class="page-subtitle">Track your request using a reference number</span>
        </div>
        <div class="page-body">

            <!-- Search by Reference -->
            <div class="card">
                <div class="card-header">
                    <h3>Search by Reference Number</h3>
                </div>
                <form method="GET" class="form-inline">
                    <input type="text"
                        name="ref"
                        class="form-input"
                        placeholder="e.g. BRGY-20250101-00001"
                        value="<?= htmlspecialchars($ref) ?>"
                        style="max-width: 340px;" />
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <!-- Search Result -->
            <?php if (!empty($ref)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Result for: <?= htmlspecialchars($ref) ?></h3>
                    </div>
                    <?php if ($search_result): ?>
                        <div class="track-result-details">
                            <table class="data-table">
                                <tr><th>Reference No.</th><td><?= htmlspecialchars($search_result['ReferenceNo']) ?></td></tr>
                                <tr><th>Request Type</th><td><?= htmlspecialchars($search_result['RequestType']) ?></td></tr>
                                <tr><th>Purpose</th><td><?= nl2br(htmlspecialchars($search_result['Purpose'] ?? '—')) ?></td></tr>
                                <tr><th>Status</th><td><span class="status-badge status-<?= strtolower($search_result['Status']) ?>"><?= $search_result['Status'] ?></span></td></tr>
                                <tr><th>Submitted</th><td><?= date('M d, Y h:i A', strtotime($search_result['CreatedAt'])) ?></td></tr>
                                <tr><th>Remarks</th><td><?= nl2br(htmlspecialchars($search_result['Remarks'] ?? '—')) ?></td></tr>

                                <!-- Expected Amount -->
                                <tr><th>Expected Amount</th><td>₱<?= number_format($search_result['expected_amount'] ?? 0, 2) ?></td></tr>

                                <!-- Paid Amount (if already paid) -->
                                <?php if (!empty($search_result['paid_amount'])): ?>
                                    <tr><th>Amount Paid</th><td>₱<?= number_format($search_result['paid_amount'], 2) ?></td></tr>
                                    <tr><th>Receipt No.</th><td><?= htmlspecialchars($search_result['ReceiptNo']) ?></td></tr>
                                    <tr><th>Payment Method</th><td><?= htmlspecialchars($search_result['PaymentMethod']) ?></td></tr>
                                    <tr><th>Payment Date</th><td><?= date('M d, Y h:i A', strtotime($search_result['RecordedAt'])) ?></td></tr>
                                <?php else: ?>
                                    <tr><th>Payment Status</th><td><span class="status-badge status-pending">Not yet paid (pay upon release)</span></td></tr>
                                <?php endif; ?>

                                <?php if ($search_result['RequestType'] == 'FacilityReservation'): ?>
                                    <tr><th>Facility</th><td><?= htmlspecialchars($search_result['FacilityName'] ?? '—') ?></td></tr>
                                    <tr><th>Reservation Date</th><td><?= htmlspecialchars($search_result['ReservationDate'] ?? '—') ?></td></tr>
                                    <tr><th>Time Slot</th><td><?= htmlspecialchars($search_result['TimeSlot'] ?? '—') ?></td></tr>
                                <?php elseif ($search_result['RequestType'] == 'Complaint'): ?>
                                    <tr><th>Respondent</th><td><?= htmlspecialchars($search_result['RespondentName'] ?? '—') ?></td></tr>
                                    <tr><th>Incident Date</th><td><?= htmlspecialchars($search_result['IncidentDate'] ?? '—') ?></td></tr>
                                    <tr><th>Mediation Date</th><td><?= htmlspecialchars($search_result['MediationDate'] ?? 'Pending') ?></td></tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="track-result">
                            <p class="empty-row">No request found with that reference number. Please check and try again.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- All My Requests (with Amount column) -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>All My Requests</h3>
                    <form method="GET" class="filter-form">
                        <select name="type" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="Clearance" <?= $filter_type == 'Clearance' ? 'selected' : '' ?>>Clearance</option>
                            <option value="Indigency" <?= $filter_type == 'Indigency' ? 'selected' : '' ?>>Indigency</option>
                            <option value="FacilityReservation" <?= $filter_type == 'FacilityReservation' ? 'selected' : '' ?>>Facility Reservation</option>
                            <option value="Complaint" <?= $filter_type == 'Complaint' ? 'selected' : '' ?>>Complaint</option>
                        </select>
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="ForApproval" <?= $filter_status == 'ForApproval' ? 'selected' : '' ?>>For Approval</option>
                            <option value="Approved" <?= $filter_status == 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Released" <?= $filter_status == 'Released' ? 'selected' : '' ?>>Released</option>
                            <option value="Rejected" <?= $filter_status == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="Cancelled" <?= $filter_status == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <?php if (!empty($filter_type) || !empty($filter_status)): ?>
                            <a href="track_request.php" class="btn-link">Clear Filters</a>
                        <?php endif; ?>
                    </form>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_requests)): ?>
                            <td><td colspan="7" class="empty-row">No requests found. <a href="new_request.php">Create one now.</a></td></tr>
                        <?php else: ?>
                            <?php foreach ($all_requests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y', strtotime($req['CreatedAt'])) ?></td>
                                    <td><span class="status-badge status-<?= strtolower($req['Status']) ?>"><?= $req['Status'] ?></span></td>
                                    <td>₱<?= number_format($req['expected_amount'] ?? 0, 2) ?></td>
                                    <td><?= htmlspecialchars(substr($req['Remarks'] ?? '', 0, 50)) ?>...</td>
                                    <td><a href="track_request.php?ref=<?= urlencode($req['ReferenceNo']) ?>" class="btn-link">View Details</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<style>
.filter-form {
    display: flex;
    gap: 10px;
    align-items: center;
}
.filter-select {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    background: white;
}
.btn-link {
    color: var(--green-dark);
    text-decoration: none;
    font-size: 0.85rem;
}
.track-result-details table {
    width: 100%;
    border-collapse: collapse;
}
.track-result-details th,
.track-result-details td {
    padding: 10px;
    border-bottom: 1px solid var(--border-color);
    text-align: left;
}
.track-result-details th {
    width: 180px;
    background: #f8f9fa;
}
</style>
<?php include '../includes/footer.php'; ?>