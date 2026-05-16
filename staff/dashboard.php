<?php
// Barangay Connect – Staff Dashboard
// staff/dashboard.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$pdo = get_db();

// --- Stat counts ---
// FIX: Count actual NEW Pending requests that staff needs to forward (was wrongly counting ForApproval)
$pendingCount = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE Status = 'Pending'"
)->fetchColumn();

// Docs to Prepare = Approved (physical document not yet prepared)
$prepareCount = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE Status = 'Approved'"
)->fetchColumn();

// Ready for Release = Prepared (document ready, waiting for payment & release)
$readyReleaseCount = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE Status = 'Prepared'"
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
         (RequestType = 'Clearance'              AND TIMESTAMPDIFF(HOUR, CreatedAt, NOW()) > " . SLA_CLEARANCE . ")
         OR (RequestType = 'Indigency'           AND TIMESTAMPDIFF(HOUR, CreatedAt, NOW()) > " . SLA_INDIGENCY . ")
         OR (RequestType = 'FacilityReservation' AND TIMESTAMPDIFF(HOUR, CreatedAt, NOW()) > " . SLA_RESERVATION . ")
         OR (RequestType = 'Complaint'           AND TIMESTAMPDIFF(HOUR, CreatedAt, NOW()) > " . SLA_COMPLAINT . ")
     )"
)->fetchColumn();

// FIX: Pending requests = Status 'Pending' — staff must review and forward these to Secretary
// ORIGINAL BUG: was querying Status = 'Approved', so new requests were invisible to staff
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

// Documents to Prepare = Approved by Secretary, staff now physically prepares them
$docsToPrepare = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
            sr.ProcessedAt,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'Approved'
     ORDER BY sr.ProcessedAt ASC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

// Ready for Release = Prepared, waiting for resident to claim
$readyForRelease = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
            sr.PreparedAt,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'Prepared'
     ORDER BY sr.PreparedAt ASC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Staff Dashboard';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-body">

            <!-- Stats -->
            <div class="stats-grid">
                <!-- FIX: label updated to reflect Pending (needs forwarding), not ForApproval -->
                <div class="stat-card stat-blue">
                    <div class="stat-icon">📥</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $pendingCount ?></span>
                        <span class="stat-label">Pending — Needs Forwarding</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $prepareCount ?></span>
                        <span class="stat-label">Docs to Prepare</span>
                    </div>
                </div>
                <div class="stat-card stat-purple">
                    <div class="stat-icon">📬</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $readyReleaseCount ?></span>
                        <span class="stat-label">Ready for Release</span>
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

            <!-- FIXED: Shows Pending requests staff must review and forward to Secretary -->
            <!-- ORIGINAL BUG: this section was titled "Approved - Awaiting Preparation" and
                 linked to document_preparation.php — staff could never see or act on new requests -->
            <div class="card">
                <div class="card-header">
                    <h3>⚠️ Pending — Review &amp; Forward to Secretary</h3>
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
                                <td colspan="6" class="empty-row">No pending requests to forward.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingRequests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['CreatedAt'])) ?></td>
                                    <td><span class="badge badge-pending"><?= $req['Status'] ?></span></td>
                                    <!-- FIX: was href="document_preparation.php?id=..." — now correctly goes to forwarding page -->
                                    <td><a href="request_status_update.php?id=<?= $req['RequestID'] ?>" class="btn btn-small btn-primary">Review &amp; Forward</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Documents to Prepare (Approved by Secretary) -->
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

            <!-- Ready for Release (Prepared — waiting for resident pickup) -->
            <div class="card">
                <div class="card-header">
                    <h3>Ready for Release</h3>
                    <a href="release_document.php" class="btn btn-primary btn-small">View All</a>
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
                        <?php if (empty($readyForRelease)): ?>
                            <tr>
                                <td colspan="5" class="empty-row">No documents ready for release.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($readyForRelease as $doc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($doc['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($doc['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($doc['RequestType']) ?></td>
                                    <td><?= $doc['PreparedAt'] ? date('M d, Y', strtotime($doc['PreparedAt'])) : '—' ?></td>
                                    <td><a href="release_document.php?id=<?= $doc['RequestID'] ?>" class="btn btn-small btn-primary">Release</a></td>
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
    .stat-purple {
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
    }
</style>

<?php include '../includes/footer.php'; ?>