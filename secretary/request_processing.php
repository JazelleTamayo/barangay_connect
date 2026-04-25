<?php
// Barangay Connect – Request Processing
// secretary/resquest_processing.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

// FIX: Load all ServiceRequest rows with optional type/status filters.
// Filters come from GET params so the existing <select> dropdowns
// in the card-actions can be wired up client-side or as a form.
$pdo = get_db();

$filterType   = trim($_GET['type']   ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql    = "SELECT
               sr.RequestID,
               sr.ReferenceNo,
               CONCAT(r.FirstName, ' ', r.LastName) AS ResidentName,
               sr.RequestType,
               sr.CreatedAt,
               sr.Status,
               TIMESTAMPDIFF(HOUR, sr.CreatedAt, NOW()) AS HoursElapsed
           FROM ServiceRequest sr
           JOIN Resident r ON sr.ResidentID = r.ResidentID
           WHERE 1=1";
$params = [];

if ($filterType !== '') {
    $sql     .= " AND sr.RequestType = ?";
    $params[] = $filterType;
}
if ($filterStatus !== '') {
    $sql     .= " AND sr.Status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY sr.CreatedAt DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);


$page_title = 'Process Requests';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Process Requests</h1>
            <span class="page-subtitle">Review and approve or reject service requests</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
                <div class="alert alert-success">✅ Request approved successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'rejected'): ?>
                <div class="alert alert-error">❌ Request has been rejected.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>All Service Requests</h3>
                    <div class="card-actions">
                        <input type="text"
                            class="search-input"
                            placeholder="Search by reference no. or name..." />
                        <select class="filter-select">
                            <option value="">All Types</option>
                            <option value="Clearance">Clearance</option>
                            <option value="Indigency">Indigency</option>
                            <option value="FacilityReservation">Facility Reservation</option>
                            <option value="Complaint">Complaint</option>
                        </select>
                        <select class="filter-select">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="ForApproval">For Approval</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Released">Released</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>SLA</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="7" class="empty-row">No requests found.</td>
                            </tr>
                        <?php else: foreach ($requests as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars($req['ReferenceNo'])  ?></td>
                                <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                <td><?= htmlspecialchars($req['RequestType'])  ?></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($req['CreatedAt']))) ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($req['Status']) ?>">
                                        <?= htmlspecialchars($req['Status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    // Simple SLA indicator — hours elapsed vs type limit
                                    $slaMap = [
                                        'Clearance'           => SLA_CLEARANCE,
                                        'Indigency'           => SLA_INDIGENCY,
                                        'FacilityReservation' => SLA_RESERVATION,
                                        'Complaint'           => SLA_COMPLAINT,
                                    ];
                                    $limit   = $slaMap[$req['RequestType']] ?? 72;
                                    $elapsed = (int) $req['HoursElapsed'];
                                    $pct     = min(100, round($elapsed / $limit * 100));
                                    $cls     = $pct >= 100 ? 'danger' : ($pct >= 75 ? 'warning' : 'ok');
                                    echo "<span class=\"sla-badge sla-$cls\">{$elapsed}h / {$limit}h</span>";
                                    ?>
                                </td>
                                <td>
                                    <a href="request_detail.php?id=<?= $req['RequestID'] ?>"
                                       class="btn btn-sm btn-outline">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>