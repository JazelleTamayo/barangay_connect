<?php
// Barangay Connect – Request Status Update (with Cancel)
// staff/request_status_update.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$pdo     = get_db();
$user_id = $_SESSION['user_id'];

// --- Handle sending to ForApproval ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_to_approval'])) {
    $request_id = intval($_POST['request_id']);
    $remarks    = trim($_POST['remarks'] ?? '');

    $remark_line = "\n[" . date('Y-m-d H:i:s') . "] Staff: " . $remarks;

    $stmt = $pdo->prepare("
        UPDATE ServiceRequest
        SET Status      = 'ForApproval',
            ProcessedBy = ?,
            ProcessedAt = NOW(),
            Remarks     = CONCAT(IFNULL(Remarks, ''), ?)
        WHERE RequestID = ? AND Status = 'Pending'
    ");
    $stmt->execute([$user_id, $remark_line, $request_id]);

    header("Location: request_status_update.php?msg=updated");
    exit;
}

// --- Handle cancellation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    $request_id    = intval($_POST['request_id']);
    $cancel_reason = trim($_POST['cancel_reason'] ?? '');
    if (empty($cancel_reason)) {
        $cancel_reason = 'Cancelled by staff (no reason provided)';
    }

    $stmt = $pdo->prepare("
        UPDATE ServiceRequest
        SET Status             = 'Cancelled',
            CancelledBy        = ?,
            CancelledAt        = NOW(),
            CancellationReason = ?
        WHERE RequestID = ? AND Status = 'Pending'
    ");
    $stmt->execute([$user_id, $cancel_reason, $request_id]);

    header("Location: request_status_update.php?msg=cancelled");
    exit;
}

// --- Load specific request for detail view ---
$update_request  = null;
$indigency_detail = null;
$complaint_detail = null;
$facility_detail  = null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("
        SELECT sr.*,
               CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
               r.ContactNumber, r.Address, r.Purok
        FROM ServiceRequest sr
        JOIN Resident r ON sr.ResidentID = r.ResidentID
        WHERE sr.RequestID = ? AND sr.Status = 'Pending'
    ");
    $stmt->execute([$id]);
    $update_request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($update_request) {
        $type = $update_request['RequestType'];

        // Load Indigency extra details
        if ($type === 'Indigency') {
            $stmt = $pdo->prepare("
                SELECT * FROM IndigencyDetail WHERE RequestID = ?
            ");
            $stmt->execute([$id]);
            $indigency_detail = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Load Complaint extra details
        if ($type === 'Complaint') {
            $stmt = $pdo->prepare("
                SELECT * FROM Complaint WHERE RequestID = ?
            ");
            $stmt->execute([$id]);
            $complaint_detail = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Load Facility Reservation extra details
        if ($type === 'FacilityReservation') {
            $stmt = $pdo->prepare("
                SELECT fr.*, f.FacilityName, f.ReservationFee
                FROM FacilityReservation fr
                JOIN Facility f ON fr.FacilityID = f.FacilityID
                WHERE fr.RequestID = ?
            ");
            $stmt->execute([$id]);
            $facility_detail = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// --- List all pending requests ---
$pending_requests = $pdo->query("
    SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
           sr.Status, sr.CreatedAt,
           CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
    FROM ServiceRequest sr
    JOIN Resident r ON sr.ResidentID = r.ResidentID
    WHERE sr.Status = 'Pending'
    ORDER BY sr.CreatedAt ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Update Request Status';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Update Request Status</h1>
            <span class="page-subtitle">
                <?= $update_request
                    ? 'Review request details before sending to Secretary'
                    : 'Move requests from Pending to For Approval' ?>
            </span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">✅ Request sent to Secretary for approval.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
                <div class="alert alert-warning">❌ Request has been cancelled.</div>
            <?php endif; ?>

            <?php if ($update_request): ?>
            <!-- ============================================================ -->
            <!--  DETAIL / ACTION VIEW                                         -->
            <!-- ============================================================ -->
            <div class="mb-3">
                <a href="request_status_update.php" class="btn btn-secondary">← Back to Pending Requests</a>
            </div>

            <!-- Request Details Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Request Details</h3>
                    <span class="ref-tag">Ref: <?= htmlspecialchars($update_request['ReferenceNo']) ?></span>
                </div>
                <div class="card-body">

                    <!-- Base info -->
                    <div class="detail-section">
                        <div class="detail-section-title">Resident Information</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Name</span>
                                <span class="detail-value"><?= htmlspecialchars($update_request['ResidentName']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Contact</span>
                                <span class="detail-value"><?= htmlspecialchars($update_request['ContactNumber'] ?? '—') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Address</span>
                                <span class="detail-value"><?= htmlspecialchars($update_request['Address'] ?? '—') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Purok</span>
                                <span class="detail-value"><?= htmlspecialchars($update_request['Purok'] ?? '—') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <div class="detail-section-title">Request Information</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Type</span>
                                <span class="detail-value">
                                    <span class="type-badge type-<?= strtolower($update_request['RequestType']) ?>">
                                        <?= htmlspecialchars($update_request['RequestType']) ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Submitted</span>
                                <span class="detail-value"><?= date('M d, Y h:i A', strtotime($update_request['CreatedAt'])) ?></span>
                            </div>
                            <div class="detail-item detail-item--full">
                                <span class="detail-label">Purpose</span>
                                <span class="detail-value"><?= nl2br(htmlspecialchars($update_request['Purpose'] ?? '—')) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- ===== INDIGENCY EXTRA DETAILS ===== -->
                    <?php if ($update_request['RequestType'] === 'Indigency' && $indigency_detail): ?>
                    <div class="detail-section">
                        <div class="detail-section-title">Financial Assessment Details</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Monthly Income</span>
                                <span class="detail-value">
                                    <?= $indigency_detail['MonthlyIncome'] !== null
                                        ? '₱' . number_format($indigency_detail['MonthlyIncome'], 2)
                                        : '—' ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Household Size</span>
                                <span class="detail-value"><?= htmlspecialchars($indigency_detail['HouseholdSize'] ?? '—') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Employment Status</span>
                                <span class="detail-value"><?= htmlspecialchars($indigency_detail['EmploymentStatus'] ?? '—') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Income Source</span>
                                <span class="detail-value"><?= htmlspecialchars($indigency_detail['IncomeSource'] ?? '—') ?></span>
                            </div>
                            <div class="detail-item detail-item--full">
                                <span class="detail-label">Government Assistance</span>
                                <span class="detail-value"><?= htmlspecialchars($indigency_detail['AssistanceReceived'] ?? '—') ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ===== COMPLAINT EXTRA DETAILS ===== -->
                    <?php if ($update_request['RequestType'] === 'Complaint' && $complaint_detail): ?>
                    <div class="detail-section">
                        <div class="detail-section-title">Complaint Details</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Respondent Name</span>
                                <span class="detail-value"><?= htmlspecialchars($complaint_detail['RespondentName'] ?? '—') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Respondent Contact</span>
                                <span class="detail-value"><?= htmlspecialchars($complaint_detail['RespondentContact'] ?? '—') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Incident Date</span>
                                <span class="detail-value">
                                    <?= $complaint_detail['IncidentDate']
                                        ? date('M d, Y', strtotime($complaint_detail['IncidentDate']))
                                        : '—' ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Incident Location</span>
                                <span class="detail-value"><?= htmlspecialchars($complaint_detail['IncidentLocation'] ?? '—') ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ===== FACILITY RESERVATION EXTRA DETAILS ===== -->
                    <?php if ($update_request['RequestType'] === 'FacilityReservation' && $facility_detail): ?>
                    <div class="detail-section">
                        <div class="detail-section-title">Facility Reservation Details</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Facility</span>
                                <span class="detail-value"><?= htmlspecialchars($facility_detail['FacilityName'] ?? '—') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Reservation Date</span>
                                <span class="detail-value">
                                    <?= $facility_detail['ReservationDate']
                                        ? date('M d, Y', strtotime($facility_detail['ReservationDate']))
                                        : '—' ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Time Slot</span>
                                <span class="detail-value"><?= htmlspecialchars($facility_detail['TimeSlot'] ?? '—') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Reservation Fee</span>
                                <span class="detail-value">₱<?= number_format($facility_detail['ReservationFee'] ?? 0, 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- /card-body -->
            </div><!-- /card -->

            <!-- Send to Secretary -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>✅ Send to Secretary for Approval</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-vertical">
                        <input type="hidden" name="send_to_approval" value="1">
                        <input type="hidden" name="request_id" value="<?= $update_request['RequestID'] ?>">
                        <div class="form-group">
                            <label>Remarks <span class="form-hint">(optional)</span></label>
                            <textarea name="remarks" class="form-textarea" rows="3"
                                      placeholder="Any notes for the Secretary..."></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Confirm &amp; Send to Secretary</button>
                            <a href="request_status_update.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cancel Request -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>❌ Cancel Request</h3>
                    <span class="card-subtitle">Use only if the request is invalid or fraudulent.</span>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-vertical">
                        <input type="hidden" name="cancel_request" value="1">
                        <input type="hidden" name="request_id" value="<?= $update_request['RequestID'] ?>">
                        <div class="form-group">
                            <label>Cancellation Reason <span style="color:#dc2626;">*</span></label>
                            <textarea name="cancel_reason" class="form-textarea" rows="3" required
                                      placeholder="Why is this request being cancelled?"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-danger">Cancel This Request</button>
                            <a href="request_status_update.php" class="btn btn-secondary">Go Back</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <!-- ============================================================ -->
            <!--  TABLE VIEW                                                   -->
            <!-- ============================================================ -->
            <div class="card">
                <div class="card-header">
                    <h3>Pending Requests</h3>
                    <div class="card-actions">
                        <input type="text" id="searchInput" class="search-input"
                               placeholder="Search by reference no. or name..." />
                        <select id="typeFilter" class="filter-select">
                            <option value="">All Types</option>
                            <option value="Clearance">Clearance</option>
                            <option value="Indigency">Indigency</option>
                            <option value="FacilityReservation">Facility Reservation</option>
                            <option value="Complaint">Complaint</option>
                        </select>
                    </div>
                </div>
                <table class="data-table" id="requestsTable">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_requests)): ?>
                            <tr><td colspan="6" class="empty-row">No pending requests.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $req): ?>
                                <tr data-type="<?= htmlspecialchars($req['RequestType']) ?>"
                                    data-name="<?= strtolower(htmlspecialchars($req['ResidentName'])) ?>"
                                    data-ref="<?= strtolower(htmlspecialchars($req['ReferenceNo'])) ?>">
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['CreatedAt'])) ?></td>
                                    <td><span class="badge badge-pending"><?= htmlspecialchars($req['Status']) ?></span></td>
                                    <td>
                                        <a href="request_status_update.php?id=<?= $req['RequestID'] ?>"
                                           class="btn btn-small btn-primary">Review</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const typeFilter  = document.getElementById('typeFilter');
const tableRows   = document.querySelectorAll('#requestsTable tbody tr');

function filterTable() {
    const searchTerm   = searchInput ? searchInput.value.toLowerCase() : '';
    const selectedType = typeFilter  ? typeFilter.value : '';
    tableRows.forEach(row => {
        const ref  = row.getAttribute('data-ref')  || '';
        const name = row.getAttribute('data-name') || '';
        const type = row.getAttribute('data-type') || '';
        const matchesSearch = ref.includes(searchTerm) || name.includes(searchTerm);
        const matchesType   = selectedType === '' || type === selectedType;
        row.style.display   = (matchesSearch && matchesType) ? '' : 'none';
    });
}
if (searchInput) searchInput.addEventListener('keyup', filterTable);
if (typeFilter)  typeFilter.addEventListener('change', filterTable);
</script>

<style>
/* Detail card layout */
.card-body         { padding: 20px 24px 24px; }
.ref-tag           { font-size: 0.8rem; color: #6b7280; display: block; margin-top: 2px; }
.card-subtitle     { font-size: 0.82rem; color: #6b7280; }

.detail-section        { margin-bottom: 24px; }
.detail-section:last-child { margin-bottom: 0; }
.detail-section-title  {
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 6px; margin-bottom: 12px;
}
.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px 24px;
}
.detail-item          { display: flex; flex-direction: column; gap: 3px; }
.detail-item--full    { grid-column: 1 / -1; }
.detail-label         { font-size: 0.75rem; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.detail-value         { font-size: 0.9rem; color: #111827; line-height: 1.5; }

/* Type badge */
.type-badge           { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600; }
.type-clearance       { background: #dbeafe; color: #1d4ed8; }
.type-indigency       { background: #fef9c3; color: #854d0e; }
.type-facilityreservation { background: #f3e8ff; color: #7e22ce; }
.type-complaint       { background: #fee2e2; color: #b91c1c; }

/* Buttons */
.btn-danger           { background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
.btn-danger:hover     { background: #b91c1c; }
.mb-3                 { margin-bottom: 1rem; }
.form-hint            { color: #9ca3af; font-size: 0.8rem; }
</style>

<?php include '../includes/footer.php'; ?>