<?php
// Barangay Connect – Staff Dashboard
// staff/dashboard.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$pdo = get_db();

// --- Stat counts ---
$pendingCount = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE Status = 'Pending'"
)->fetchColumn();

$prepareCount = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE Status = 'ForApproval'"
)->fetchColumn();

$processedToday = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest
     WHERE ProcessedBy IS NOT NULL
     AND DATE(ProcessedAt) = CURDATE()"
)->fetchColumn();

// Overdue = Pending/ForApproval past SLA hours
$overdueCount = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest
     WHERE Status IN ('Pending','ForApproval')
     AND (
         (RequestType = 'Clearance'            AND TIMESTAMPDIFF(HOUR, CreatedAt, NOW()) > " . SLA_CLEARANCE . ")
         OR (RequestType = 'Indigency'         AND TIMESTAMPDIFF(HOUR, CreatedAt, NOW()) > " . SLA_INDIGENCY . ")
         OR (RequestType = 'FacilityReservation' AND TIMESTAMPDIFF(HOUR, CreatedAt, NOW()) > " . SLA_RESERVATION . ")
         OR (RequestType = 'Complaint'         AND TIMESTAMPDIFF(HOUR, CreatedAt, NOW()) > " . SLA_COMPLAINT . ")
     )"
)->fetchColumn();

// --- Pending Requests (all pending for staff to work on) ---
$pendingRequests = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
            sr.Status, sr.CreatedAt,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'Pending'
     ORDER BY sr.CreatedAt ASC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

// --- Documents to Prepare (ForApproval = approved, needs document prep) ---
$docsToPrepare = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
            sr.ProcessedAt,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'ForApproval'
     ORDER BY sr.ProcessedAt ASC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Staff Dashboard';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Staff Dashboard</h1>
            <span class="page-subtitle">Welcome, <?= current_user_name() ?></span>
        </div>
        <div class="page-body">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">📥</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $pendingCount ?></span>
                        <span class="stat-label">Pending Requests</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $prepareCount ?></span>
                        <span class="stat-label">Docs to Prepare</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $processedToday ?></span>
                        <span class="stat-label">Processed Today</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $overdueCount ?></span>
                        <span class="stat-label">Overdue Requests</span>
                    </div>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="card">
                <div class="card-header">
                    <h3>Pending Requests</h3>
                    <a href="request_status_update.php" class="btn btn-primary btn-small">View All</a>
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
                                    <td><a href="request_status_update.php?id=<?= $req['RequestID'] ?>" class="btn btn-small btn-primary">Update</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Documents to Prepare -->
            <div class="card">
                <div class="card-header">
                    <h3>Documents to Prepare</h3>
                    <a href="document_preparation.php" class="btn btn-secondary btn-small">View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Approved Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($docsToPrepare)): ?>
                            <tr>
                                <td colspan="5" class="empty-row">No documents to prepare.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($docsToPrepare as $doc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($doc['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($doc['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($doc['RequestType']) ?></td>
                                    <td><?= $doc['ProcessedAt'] ? date('M d, Y', strtotime($doc['ProcessedAt'])) : '—' ?></td>
                                    <td><a href="document_preparation.php?id=<?= $doc['RequestID'] ?>" class="btn btn-small btn-secondary">Prepare</a></td>
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