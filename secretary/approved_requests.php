<?php
// Barangay Connect – Secretary: View Approved Requests
// secretary/approved_requests.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo = get_db();

// Optional search/filter
$search = $_GET['search'] ?? '';
$filter_type = $_GET['type'] ?? '';

$sql = "
    SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType, sr.Purpose,
           sr.Status, sr.CreatedAt, sr.ProcessedAt,
           CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
    FROM ServiceRequest sr
    JOIN Resident r ON sr.ResidentID = r.ResidentID
    WHERE sr.Status = 'Approved'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (sr.ReferenceNo LIKE ? OR CONCAT(r.FirstName,' ',r.LastName) LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
}
if (!empty($filter_type)) {
    $sql .= " AND sr.RequestType = ?";
    $params[] = $filter_type;
}
$sql .= " ORDER BY sr.ProcessedAt DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$approvedRequests = $stmt->fetchAll();

$page_title = 'Approved Requests';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Approved Requests</h1>
            <span class="page-subtitle">All requests that have been approved by the secretary</span>
        </div>
        <div class="page-body">
            <div class="card">
                <div class="card-header">
                    <h3>Approved Requests List</h3>
                    <form method="GET" class="filter-form">
                        <input type="text" name="search" class="form-input" placeholder="Search by ref or name" value="<?= htmlspecialchars($search) ?>">
                        <select name="type" class="filter-select">
                            <option value="">All Types</option>
                            <option value="Clearance" <?= $filter_type == 'Clearance' ? 'selected' : '' ?>>Clearance</option>
                            <option value="Indigency" <?= $filter_type == 'Indigency' ? 'selected' : '' ?>>Indigency</option>
                            <option value="FacilityReservation" <?= $filter_type == 'FacilityReservation' ? 'selected' : '' ?>>Facility Reservation</option>
                            <option value="Complaint" <?= $filter_type == 'Complaint' ? 'selected' : '' ?>>Complaint</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-small">Filter</button>
                        <a href="approved_requests.php" class="btn-link">Clear</a>
                    </form>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Approved Date</th>
                            <th>Purpose</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approvedRequests)): ?>
                            <tr><td colspan="6" class="empty-row">No approved requests found.<?php else: ?>
                            <?php foreach ($approvedRequests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['ProcessedAt'] ?? $req['CreatedAt'])) ?></td>
                                    <td><?= htmlspecialchars(substr($req['Purpose'] ?? '', 0, 60)) ?>...</td>
                                    <td><a href="request_detail.php?id=<?= $req['RequestID'] ?>" class="btn btn-small btn-secondary">View</a></td>
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
.filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.btn-link { color: #2563eb; text-decoration: none; }
</style>
<?php include '../includes/footer.php'; ?>