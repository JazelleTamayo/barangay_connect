<?php
// Barangay Connect – Request Status Update
// staff/request_status_update.php
// FIXED: Added PHP query to load Status=Pending requests (previously hardcoded empty table)

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

// FIXED: Load all Pending requests for staff to process
$pdo = get_db();
$pendingRequests = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
            sr.Status, sr.CreatedAt,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'Pending'
     ORDER BY sr.CreatedAt ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Update Request Status';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Update Request Status</h1>
            <span class="page-subtitle">Move requests from Pending to For Approval</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">✅ Request status updated successfully.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Pending Requests</h3>
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
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Current Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- FIXED: Table now populated from DB query for Pending requests -->
                        <?php if (empty($pendingRequests)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No pending requests.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingRequests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['CreatedAt'])) ?></td>
                                    <td><span class="badge badge-pending"><?= $req['Status'] ?></span></td>
                                    <td>
                                        <a href="request_status_update.php?id=<?= $req['RequestID'] ?>"
                                           class="btn btn-small btn-primary">Update</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>