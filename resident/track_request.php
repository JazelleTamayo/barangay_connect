<?php
// Barangay Connect – Track Request
// resident/track_request.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../config/settings.php';
require_role('resident');

$page_title = 'Track Request';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$pdo = get_db();

$stmt = $pdo->prepare("SELECT ResidentID FROM UserAccount WHERE UserAccountID = ?");
$stmt->execute([$user_id]);
$resident = $stmt->fetch();
$resident_id = $resident ? $resident['ResidentID'] : null;

$clearance_fee = get_setting('clearance_fee', 50.00);

$ref = trim($_GET['ref'] ?? '');
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';

// ---------- ALL REQUESTS ----------
$all_requests = [];
if ($resident_id) {
    $sql = "
        SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType, sr.CreatedAt, sr.Status, sr.Remarks,
            sr.CancellationReason,
            CASE sr.RequestType
                WHEN 'Clearance' THEN :clearance_fee
                WHEN 'Indigency' THEN 0
                WHEN 'Complaint' THEN 0
                WHEN 'FacilityReservation' THEN COALESCE(f.ReservationFee, 0)
                ELSE 0
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

// ---------- SEARCH RESULT ----------
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
                ELSE 0
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
    $stmt->execute([':clearance_fee' => $clearance_fee, ':ref' => $ref, ':resident_id' => $resident_id]);
    $search_result = $stmt->fetch();
}

// If ref is in URL = viewing details mode
$viewing_detail = !empty($ref);

function parseRemarks(string $raw): array
{
    $entries = [];
    $chunks = preg_split('/\n(?=\[)/', trim($raw));
    foreach ($chunks as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '') continue;
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*([^:]+):\s*(.*)$/s', $chunk, $m)) {
            $entries[] = ['timestamp' => $m[1], 'role' => trim($m[2]), 'message' => trim($m[3])];
        } else {
            $entries[] = ['timestamp' => '', 'role' => 'Note', 'message' => $chunk];
        }
    }
    return $entries;
}

function roleLabel(string $role): string
{
    $r = strtolower($role);
    if (str_contains($r, 'secretary')) return 'Secretary';
    if (str_contains($r, 'staff'))     return 'Staff';
    if (str_contains($r, 'captain'))   return 'Captain';
    return ucwords($role);
}

function roleBadgeClass(string $role): string
{
    $r = strtolower($role);
    if (str_contains($r, 'secretary')) return 'badge-role-secretary';
    if (str_contains($r, 'staff'))     return 'badge-role-staff';
    if (str_contains($r, 'captain'))   return 'badge-role-captain';
    return 'badge-role-system';
}

function statusLabel(string $status): string
{
    $map = [
        'Pending'     => 'Pending',
        'ForApproval' => 'For Approval',
        'Approved'    => 'Approved',
        'Prepared'    => '📦 Ready for Pickup',
        'Released'    => 'Released',
        'Rejected'    => 'Rejected',
        'Cancelled'   => 'Cancelled',
    ];
    return $map[$status] ?? $status;
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

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'submitted'): ?>
                    <div class="alert alert-success">✅ Request submitted successfully! It is now pending staff review. You can track it below using your reference number.</div>
                <?php elseif ($_GET['msg'] === 'cancelled'): ?>
                    <div class="alert alert-warning">🚫 Your request has been cancelled.</div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($viewing_detail): ?>
                <!-- ===== DETAIL VIEW ===== -->
                <div class="back-bar">
                    <a href="track_request.php" class="btn btn-secondary">← Back to My Requests</a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Request Details</h3>
                        <span class="ref-chip"><?= htmlspecialchars($ref) ?></span>
                    </div>

                    <?php if ($search_result): ?>

                        <?php if ($search_result['Status'] === 'Prepared'): ?>
                            <?php if ($search_result['RequestType'] === 'FacilityReservation'): ?>
                                <div class="pickup-banner pickup-banner--facility">
                                    🏛️ <strong>Your Facility Use Permit is ready!</strong>
                                    Go to the Barangay Hall in person, bring a valid ID, and pay the reservation fee of
                                    <strong>₱<?= number_format($search_result['expected_amount'] ?? 0, 2) ?></strong> (Cash or GCash).
                                    You will receive the official permit — <strong>bring it on the day of your event.</strong>
                                </div>
                            <?php elseif ($search_result['RequestType'] === 'Clearance'): ?>
                                <div class="pickup-banner pickup-banner--clearance">
                                    📄 <strong>Your Barangay Clearance is ready!</strong>
                                    Go to the Barangay Hall in person, bring a valid ID, and pay
                                    <strong>₱<?= number_format($search_result['expected_amount'] ?? 0, 2) ?></strong> (Cash or GCash) to receive your signed clearance.
                                </div>
                            <?php elseif ($search_result['RequestType'] === 'Indigency'): ?>
                                <div class="pickup-banner pickup-banner--indigency">
                                    📄 <strong>Your Certificate of Indigency is ready!</strong>
                                    Go to the Barangay Hall in person and bring a valid ID. This document is <strong>free of charge</strong>.
                                </div>
                            <?php else: ?>
                                <div class="pickup-banner">
                                    📦 <strong>Your document is ready for pickup!</strong>
                                    Go to the Barangay Hall in person and bring a valid ID to claim your document.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="track-result-details">
                            <table class="data-table">
                                <tr>
                                    <th>Reference No.</th>
                                    <td><?= htmlspecialchars($search_result['ReferenceNo']) ?></td>
                                </tr>
                                <tr>
                                    <th>Request Type</th>
                                    <td><?= htmlspecialchars($search_result['RequestType']) ?></td>
                                </tr>
                                <tr>
                                    <th>Purpose</th>
                                    <td><?= nl2br(htmlspecialchars($search_result['Purpose'] ?? '—')) ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($search_result['Status']) ?>">
                                            <?= statusLabel($search_result['Status']) ?>
                                        </span>
                                    </td>
                                </tr>

                                <?php if ($search_result['Status'] === 'Cancelled' && !empty($search_result['CancellationReason'])): ?>
                                    <tr>
                                        <th>Cancellation Reason</th>
                                        <td><?= nl2br(htmlspecialchars($search_result['CancellationReason'])) ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if ($search_result['Status'] === 'Rejected' && !empty($search_result['RejectionReason'])): ?>
                                    <tr>
                                        <th>Rejection Reason</th>
                                        <td style="color:#991b1b;"><?= nl2br(htmlspecialchars($search_result['RejectionReason'])) ?></td>
                                    </tr>
                                <?php endif; ?>

                                <tr>
                                    <th>Submitted</th>
                                    <td><?= date('M d, Y h:i A', strtotime($search_result['CreatedAt'])) ?></td>
                                </tr>

                                <tr>
                                    <th>Remarks</th>
                                    <td>
                                        <?php
                                        $rawRemarks = trim($search_result['Remarks'] ?? '');
                                        if ($rawRemarks === '') {
                                            echo '<span class="remark-none">No remarks yet.</span>';
                                        } else {
                                            $entries = parseRemarks($rawRemarks);
                                            $visible = array_filter($entries, function ($e) {
                                                return str_contains(strtolower($e['role']), 'staff');
                                            });
                                            if (empty($visible)) {
                                                echo '<span class="remark-none">No remarks yet.</span>';
                                            } else {
                                                echo '<div class="remarks-log">';
                                                foreach ($visible as $entry) {
                                                    $entryClass = strtolower(roleLabel($entry['role']));
                                                    echo '<div class="remark-entry remark-entry--' . $entryClass . '">';
                                                    echo '<div class="remark-meta">';
                                                    echo '<span class="remark-badge ' . roleBadgeClass($entry['role']) . '">' . htmlspecialchars(roleLabel($entry['role'])) . '</span>';
                                                    if ($entry['timestamp']) {
                                                        echo '<span class="remark-time">' . date('M d, Y h:i A', strtotime($entry['timestamp'])) . '</span>';
                                                    }
                                                    echo '</div>';
                                                    echo '<div class="remark-message">' . nl2br(htmlspecialchars($entry['message'])) . '</div>';
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Expected Amount</th>
                                    <td>₱<?= number_format($search_result['expected_amount'] ?? 0, 2) ?></td>
                                </tr>

                                <?php if (!empty($search_result['paid_amount'])): ?>
                                    <tr>
                                        <th>Amount Paid</th>
                                        <td>₱<?= number_format($search_result['paid_amount'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Receipt No.</th>
                                        <td><?= htmlspecialchars($search_result['ReceiptNo']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Method</th>
                                        <td><?= htmlspecialchars($search_result['PaymentMethod']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Date</th>
                                        <td><?= date('M d, Y h:i A', strtotime($search_result['RecordedAt'])) ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <th>Payment Status</th>
                                        <td><span class="status-badge status-pending">Not yet paid (pay upon release)</span></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if ($search_result['RequestType'] === 'FacilityReservation'): ?>
                                    <tr>
                                        <th>Facility</th>
                                        <td><?= htmlspecialchars($search_result['FacilityName'] ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Reservation Date</th>
                                        <td><?= htmlspecialchars($search_result['ReservationDate'] ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Time Slot</th>
                                        <td><?= htmlspecialchars($search_result['TimeSlot'] ?? '—') ?></td>
                                    </tr>
                                <?php elseif ($search_result['RequestType'] === 'Complaint'): ?>
                                    <tr>
                                        <th>Respondent</th>
                                        <td><?= htmlspecialchars($search_result['RespondentName'] ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Incident Date</th>
                                        <td><?= htmlspecialchars($search_result['IncidentDate'] ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Mediation Date</th>
                                        <td><?= htmlspecialchars($search_result['MediationDate'] ?? 'To be scheduled') ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                    <?php else: ?>
                        <div class="track-result">
                            <p class="empty-row">No request found with that reference number. Please check and try again.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- ===== LIST VIEW ===== -->

                <!-- All My Requests with live search -->
                <div class="card">
                    <div class="card-header">
                        <h3>All My Requests</h3>
                    </div>
                    <div class="search-filter-bar">
                        <div class="search-wrap">
                            <span class="search-icon">🔍</span>
                            <input type="text" id="live-search"
                                placeholder="Search by reference no., type, or status…"
                                autocomplete="off" />
                        </div>
                        <select id="filter-type" class="filter-select">
                            <option value="">All Types</option>
                            <option value="Clearance">Clearance</option>
                            <option value="Indigency">Indigency</option>
                            <option value="FacilityReservation">Facility Reservation</option>
                            <option value="Complaint">Complaint</option>
                        </select>
                        <select id="filter-status" class="filter-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="forapproval">For Approval</option>
                            <option value="approved">Approved</option>
                            <option value="prepared">Ready for Pickup</option>
                            <option value="released">Released</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button class="btn-clear-search" id="clear-search" style="display:none;" onclick="clearSearch()">✕ Clear</button>
                    </div>
                    <div id="no-results" class="no-results-msg" style="display:none;">No requests match your search.</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference No.</th>
                                <th>Type</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Cancellation Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="requests-tbody">
                            <?php if (empty($all_requests)): ?>
                                <tr>
                                    <td colspan="7" class="empty-row">No requests found. <a href="new_request.php">Create one now.</a></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_requests as $req): ?>
                                    <tr
                                        data-ref="<?= strtolower($req['ReferenceNo']) ?>"
                                        data-type="<?= strtolower($req['RequestType']) ?>"
                                        data-status="<?= strtolower($req['Status']) ?>">
                                        <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                        <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                        <td><?= date('M d, Y', strtotime($req['CreatedAt'])) ?></td>
                                        <td>
                                            <span class="req-status-badge status-<?= strtolower($req['Status']) ?>">
                                                <?= statusLabel($req['Status']) ?>
                                            </span>
                                        </td>
                                        <td>₱<?= number_format($req['expected_amount'] ?? 0, 2) ?></td>
                                        <td>
                                            <?php if ($req['Status'] === 'Cancelled' && !empty($req['CancellationReason'])): ?>
                                                <?= nl2br(htmlspecialchars(substr($req['CancellationReason'], 0, 50))) ?>...
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td><a href="track_request.php?ref=<?= urlencode($req['ReferenceNo']) ?>" class="btn btn-small btn-primary">View Details</a></td>
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
<style>
    .back-bar { margin-bottom: 1rem; }

    .ref-chip {
        font-family: monospace;
        font-size: 0.82rem;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 3px 10px;
        border-radius: 999px;
        color: #475569;
    }

    .search-filter-bar {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        padding: 14px 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    .search-wrap {
        position: relative;
        flex: 1;
        min-width: 200px;
    }

    .search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.9rem;
        pointer-events: none;
    }

    #live-search {
        width: 100%;
        padding: 8px 12px 8px 34px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.88rem;
        outline: none;
        box-sizing: border-box;
    }

    #live-search:focus {
        border-color: var(--color-primary, #3b82f6);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }

    .filter-select {
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        background: white;
        font-size: 0.88rem;
        cursor: pointer;
    }

    .btn-clear-search {
        padding: 7px 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #f9fafb;
        color: #6b7280;
        font-size: 0.82rem;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-clear-search:hover { background: #f3f4f6; }

    .no-results-msg {
        text-align: center;
        padding: 30px;
        color: #9ca3af;
        font-size: 0.9rem;
        font-style: italic;
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
        vertical-align: top;
    }

    .track-result-details th {
        width: 180px;
        background: #f8f9fa;
        font-weight: 600;
    }

    .pickup-banner {
        margin: 12px 20px;
        padding: 14px 18px;
        background: #d1fae5;
        border: 1px solid #6ee7b7;
        border-radius: 8px;
        color: #065f46;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    .pickup-banner--facility  { background: #eff6ff; border-color: #93c5fd; color: #1e3a5f; }
    .pickup-banner--clearance { background: #fefce8; border-color: #fde047; color: #713f12; }
    .pickup-banner--indigency { background: #f0fdf4; border-color: #86efac; color: #14532d; }

    .status-badge,
    .req-status-badge {
        display: inline-block !important;
        padding: 3px 12px !important;
        border-radius: 999px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        border: 1px solid transparent !important;
    }

    .status-pending,
    .req-status-badge.status-pending {
        background: #fef9c3 !important;
        color: #854d0e !important;
        border-color: #fde047 !important;
    }

    .status-forapproval,
    .req-status-badge.status-forapproval {
        background: #dbeafe !important;
        color: #1e40af !important;
        border-color: #93c5fd !important;
    }

    .status-approved,
    .req-status-badge.status-approved {
        background: #dcfce7 !important;
        color: #166534 !important;
        border-color: #86efac !important;
    }

    .status-prepared,
    .req-status-badge.status-prepared {
        background: #d1fae5 !important;
        color: #065f46 !important;
        border-color: #34d399 !important;
        font-weight: 700 !important;
    }

    .status-released,
    .req-status-badge.status-released {
        background: #ede9fe !important;
        color: #5b21b6 !important;
        border-color: #c4b5fd !important;
    }

    .status-rejected,
    .req-status-badge.status-rejected {
        background: #fee2e2 !important;
        color: #991b1b !important;
        border-color: #fca5a5 !important;
    }

    .status-cancelled,
    .req-status-badge.status-cancelled {
        background: #f3f4f6 !important;
        color: #6b7280 !important;
        border-color: #d1d5db !important;
    }

    .remarks-log {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .remark-entry {
        border-left: 4px solid #ccc;
        border-radius: 0 6px 6px 0;
        background: #fafafa;
        padding: 8px 12px;
    }

    .remark-entry--staff     { border-left-color: #1565c0; background: #f0f5ff; }
    .remark-entry--secretary { border-left-color: #2e7d32; background: #f0faf0; }
    .remark-entry--captain   { border-left-color: #e65100; background: #fff8f0; }
    .remark-entry--note      { border-left-color: #888;    background: #f8f8f8; }

    .remark-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
        flex-wrap: wrap;
    }

    .remark-badge {
        font-size: 0.70rem;
        font-weight: 700;
        padding: 2px 9px;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .badge-role-staff     { background: #dbeafe; color: #1d4ed8; }
    .badge-role-secretary { background: #dcfce7; color: #166534; }
    .badge-role-captain   { background: #ffedd5; color: #9a3412; }
    .badge-role-system    { background: #f3f4f6; color: #4b5563; }

    .remark-time    { font-size: 0.78rem; color: #888; }
    .remark-message { font-size: 0.9rem; color: #333; line-height: 1.5; }
    .remark-none    { color: #999; font-style: italic; font-size: 0.88rem; }
</style>
<script>
function filterTable() {
    const search = document.getElementById('live-search').value.toLowerCase().trim();
    const type   = document.getElementById('filter-type').value.toLowerCase();
    const status = document.getElementById('filter-status').value.toLowerCase();
    const rows   = document.querySelectorAll('#requests-tbody tr[data-ref]');
    const clearBtn = document.getElementById('clear-search');
    const noResults = document.getElementById('no-results');

    clearBtn.style.display = (search || type || status) ? 'inline-block' : 'none';

    let visible = 0;
    rows.forEach(row => {
        const ref     = row.getAttribute('data-ref') || '';
        const rowType = row.getAttribute('data-type') || '';
        const rowStat = row.getAttribute('data-status') || '';

        const matchSearch = !search || ref.includes(search) || rowType.includes(search) || rowStat.includes(search);
        const matchType   = !type   || rowType === type;
        const matchStatus = !status || rowStat === status;

        const show = matchSearch && matchType && matchStatus;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    noResults.style.display = visible === 0 ? 'block' : 'none';
}

function clearSearch() {
    document.getElementById('live-search').value = '';
    document.getElementById('filter-type').value = '';
    document.getElementById('filter-status').value = '';
    filterTable();
}

document.getElementById('live-search').addEventListener('input', filterTable);
document.getElementById('filter-type').addEventListener('change', filterTable);
document.getElementById('filter-status').addEventListener('change', filterTable);
</script>
<?php include '../includes/footer.php'; ?>