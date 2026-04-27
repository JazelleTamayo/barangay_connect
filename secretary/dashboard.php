<?php
// Barangay Connect – Secretary Dashboard
// secretary/dashboard.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo = get_db();

// --- Stat counts ---
$pendingVerif = $pdo->query(
    "SELECT COUNT(*) FROM UserAccount WHERE AccountStatus = 'PendingVerification'"
)->fetchColumn();

$forApproval = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE Status = 'ForApproval'"
)->fetchColumn();

$readyRelease = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE Status = 'Prepared'"
)->fetchColumn();

$openComplaints = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE RequestType = 'Complaint'
     AND Status NOT IN ('Rejected','Cancelled','Released')"
)->fetchColumn();

// --- Requests For Approval (only ForApproval, not Pending) ---
$approvalRequests = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType, sr.Purpose,
            sr.Status, sr.CreatedAt,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'ForApproval'
     ORDER BY sr.CreatedAt ASC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

// --- Pending Verifications ---
$pendingResidents = $pdo->query(
    "SELECT ua.UserAccountID, ua.CreatedAt, ua.AccountStatus,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
            r.GovIDType
     FROM UserAccount ua
     JOIN Resident r ON ua.ResidentID = r.ResidentID
     WHERE ua.AccountStatus = 'PendingVerification'
     ORDER BY ua.CreatedAt ASC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

// --- Documents Ready for Release (Prepared documents) ---
$readyDocs = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
            sr.PreparedAt,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'Prepared'
     ORDER BY sr.PreparedAt ASC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Secretary Dashboard';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Secretary Dashboard</h1>
            <span class="page-subtitle">Welcome, <?= current_user_name() ?></span>
        </div>
        <div class="page-body">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $pendingVerif ?></span>
                        <span class="stat-label">Pending Verification</span>
                    </div>
                </div>
                <div class="stat-card stat-blue">
                    <div class="stat-icon">📥</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $forApproval ?></span>
                        <span class="stat-label">For Approval</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">📬</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $readyRelease ?></span>
                        <span class="stat-label">Ready for Release</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $openComplaints ?></span>
                        <span class="stat-label">Open Complaints</span>
                    </div>
                </div>
            </div>

            <!-- Requests For Approval -->
            <div class="card">
                <div class="card-header">
                    <h3>Requests For Approval</h3>
                    <a href="request_processing.php" class="btn btn-primary btn-small">View All</a>
                </div>
                <table class="data-table">
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
                        <?php if (empty($approvalRequests)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No requests waiting for approval.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($approvalRequests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['CreatedAt'])) ?></td>
                                    <td><span class="badge badge-<?= strtolower($req['Status']) ?>"><?= $req['Status'] ?></span></td>
                                    <td><a href="request_processing.php?id=<?= $req['RequestID'] ?>" class="btn btn-small btn-primary">Process</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pending Verifications -->
            <div class="card">
                <div class="card-header">
                    <h3>Pending Resident Verifications</h3>
                    <a href="resident_verification.php" class="btn btn-secondary btn-small">View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Submitted</th>
                            <th>ID Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendingResidents)): ?>
                            <tr>
                                <td colspan="4" class="empty-row">No pending verifications.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingResidents as $res): ?>
                                <tr>
                                    <td><?= htmlspecialchars($res['ResidentName']) ?></td>
                                    <td><?= date('M d, Y', strtotime($res['CreatedAt'])) ?></td>
                                    <td><?= htmlspecialchars($res['GovIDType'] ?? '—') ?></td>
                                    <td><a href="resident_verification.php?id=<?= $res['UserAccountID'] ?>" class="btn btn-small btn-secondary">Review</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Documents Ready for Release (Prepared) -->
            <div class="card">
                <div class="card-header">
                    <h3>Documents Ready for Release</h3>
                    <a href="document_release.php" class="btn btn-secondary btn-small">View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Prepared Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($readyDocs)): ?>
                            <tr>
                                <td colspan="5" class="empty-row">No documents ready for release.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($readyDocs as $doc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($doc['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($doc['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($doc['RequestType']) ?></td>
                                    <td><?= $doc['PreparedAt'] ? date('M d, Y', strtotime($doc['PreparedAt'])) : '—' ?></td>
                                    <td><a href="document_release.php?id=<?= $doc['RequestID'] ?>" class="btn btn-small btn-secondary">Release</a></td>
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