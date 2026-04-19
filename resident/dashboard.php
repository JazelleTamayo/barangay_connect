<?php
// Barangay Connect – Resident Dashboard
// resident/dashboard.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$page_title = 'My Dashboard';
include '../includes/header.php';

// Get the logged‑in resident's ResidentID from UserAccount
$user_id = $_SESSION['user_id'];
$pdo = get_db();

$stmt = $pdo->prepare("SELECT ResidentID FROM UserAccount WHERE UserAccountID = ?");
$stmt->execute([$user_id]);
$resident = $stmt->fetch();
$resident_id = $resident ? $resident['ResidentID'] : null;

// Initialize variables
$total_requests = 0;
$pending_requests = 0;
$released_requests = 0;
$complaints_filed = 0;
$recent_requests = [];
$my_complaints = [];
$my_reservations = [];

if ($resident_id) {
    // --- Stats ---
    // Total requests
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ServiceRequest WHERE ResidentID = ?");
    $stmt->execute([$resident_id]);
    $total_requests = $stmt->fetchColumn();

    // Pending (Pending or ForApproval)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ServiceRequest WHERE ResidentID = ? AND Status IN ('Pending', 'ForApproval')");
    $stmt->execute([$resident_id]);
    $pending_requests = $stmt->fetchColumn();

    // Released
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ServiceRequest WHERE ResidentID = ? AND Status = 'Released'");
    $stmt->execute([$resident_id]);
    $released_requests = $stmt->fetchColumn();

    // Complaints filed (RequestType = 'Complaint')
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ServiceRequest WHERE ResidentID = ? AND RequestType = 'Complaint'");
    $stmt->execute([$resident_id]);
    $complaints_filed = $stmt->fetchColumn();

    // --- Recent Requests (last 5) ---
    $stmt = $pdo->prepare("
        SELECT RequestID, ReferenceNo, RequestType, CreatedAt, Status
        FROM ServiceRequest
        WHERE ResidentID = ?
        ORDER BY CreatedAt DESC
        LIMIT 5
    ");
    $stmt->execute([$resident_id]);
    $recent_requests = $stmt->fetchAll();

    // --- My Complaints (join with Complaint table) ---
    $stmt = $pdo->prepare("
        SELECT sr.ReferenceNo, c.RespondentName, c.IncidentDate, c.MediationDate, sr.Status
        FROM ServiceRequest sr
        JOIN Complaint c ON sr.RequestID = c.RequestID
        WHERE sr.ResidentID = ? AND sr.RequestType = 'Complaint'
        ORDER BY sr.CreatedAt DESC
    ");
    $stmt->execute([$resident_id]);
    $my_complaints = $stmt->fetchAll();

    // --- My Facility Reservations ---
    $stmt = $pdo->prepare("
        SELECT sr.ReferenceNo, f.FacilityName, fr.ReservationDate, fr.TimeSlot, sr.Status
        FROM ServiceRequest sr
        JOIN FacilityReservation fr ON sr.RequestID = fr.RequestID
        JOIN Facility f ON fr.FacilityID = f.FacilityID
        WHERE sr.ResidentID = ? AND sr.RequestType = 'FacilityReservation'
        ORDER BY fr.ReservationDate DESC
    ");
    $stmt->execute([$resident_id]);
    $my_reservations = $stmt->fetchAll();
}
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>My Dashboard</h1>
            <span class="page-subtitle">Welcome, <?= current_user_name() ?></span>
        </div>
        <div class="page-body">

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $total_requests ?></span>
                        <span class="stat-label">Total Requests</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $pending_requests ?></span>
                        <span class="stat-label">Pending</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $released_requests ?></span>
                        <span class="stat-label">Released</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $complaints_filed ?></span>
                        <span class="stat-label">Complaints Filed</span>
                    </div>
                </div>
            </div>

            <!-- Recent Requests -->
            <div class="card">
                <div class="card-header">
                    <h3>My Recent Requests</h3>
                    <a href="new_request.php" class="btn btn-primary btn-small">+ New Request</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Reference No.</th><th>Type</th><th>Submitted</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_requests)): ?>
                            <tr><td colspan="5" class="empty-row">You have no requests yet. <a href="new_request.php">Create one now.</a></td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_requests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y', strtotime($req['CreatedAt'])) ?></td>
                                    <td><span class="status-badge status-<?= strtolower($req['Status']) ?>"><?= $req['Status'] ?></span></td>
                                    <td><a href="track_request.php?ref=<?= urlencode($req['ReferenceNo']) ?>" class="btn-link">Track</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- My Complaints -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>My Complaints</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Reference No.</th><th>Respondent</th><th>Incident Date</th><th>Status</th><th>Mediation Date</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_complaints)): ?>
                            <tr><td colspan="5" class="empty-row">No complaints filed.</td></tr>
                        <?php else: ?>
                            <?php foreach ($my_complaints as $comp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($comp['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($comp['RespondentName'] ?? 'N/A') ?></td>
                                    <td><?= $comp['IncidentDate'] ? date('M d, Y', strtotime($comp['IncidentDate'])) : '—' ?></td>
                                    <td><span class="status-badge status-<?= strtolower($comp['Status']) ?>"><?= $comp['Status'] ?></span></td>
                                    <td><?= $comp['MediationDate'] ? date('M d, Y', strtotime($comp['MediationDate'])) : 'Pending' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- My Facility Reservations -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>My Facility Reservations</h3>
                    <a href="facility_schedule.php" class="btn btn-secondary btn-small">View Schedule</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Reference No.</th><th>Facility</th><th>Date</th><th>Time Slot</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_reservations)): ?>
                            <tr><td colspan="5" class="empty-row">No reservations yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($my_reservations as $res): ?>
                                <tr>
                                    <td><?= htmlspecialchars($res['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($res['FacilityName']) ?></td>
                                    <td><?= date('M d, Y', strtotime($res['ReservationDate'])) ?></td>
                                    <td><?= htmlspecialchars($res['TimeSlot'] ?? '—') ?></td>
                                    <td><span class="status-badge status-<?= strtolower($res['Status']) ?>"><?= $res['Status'] ?></span></td>
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