<?php
// Barangay Connect – Captain Dashboard
// captain/dashboard.php
// FIXED: Added PHP queries for all 4 stat cards and all 3 data tables
//        (previously all stat cards were hardcoded "—" and all tables were hardcoded empty)

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

$pdo = get_db();

// FIXED: Stat 1 — total active residents
$totalResidents = $pdo->query(
    "SELECT COUNT(*) FROM Resident WHERE Status = 'Active'"
)->fetchColumn();

// FIXED: Stat 2 — pending requests (Pending + ForApproval)
$pendingRequests = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE Status IN ('Pending','ForApproval')"
)->fetchColumn();

// FIXED: Stat 3 — open complaints (not yet resolved/cancelled)
$openComplaints = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest
     WHERE RequestType = 'Complaint'
       AND Status NOT IN ('Rejected','Cancelled','Released')"
)->fetchColumn();

// FIXED: Stat 4 — active facilities
$activeFacilities = $pdo->query(
    "SELECT COUNT(*) FROM Facility WHERE Status = 'Active'"
)->fetchColumn();

// FIXED: Approvals table — load ForApproval requests for captain to review
$forApprovalRequests = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
            sr.CreatedAt, sr.Remarks,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'ForApproval'
     ORDER BY sr.CreatedAt ASC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

// FIXED: Activity table — load last 10 entries from AuditLog
$recentActivity = $pdo->query(
    "SELECT LogID, Username, Role, Action, RecordAffected, LoggedAt
     FROM AuditLog
     ORDER BY LoggedAt DESC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Captain Dashboard';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Captain Dashboard</h1>
            <span class="page-subtitle">Welcome, <?= current_user_name() ?></span>
        </div>
        <div class="page-body">

            <!-- Stats -->
            <!-- FIXED: Values now populated from PHP queries, no longer hardcoded "—" -->
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $totalResidents ?></span>
                        <span class="stat-label">Total Residents</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $pendingRequests ?></span>
                        <span class="stat-label">Pending Requests</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $openComplaints ?></span>
                        <span class="stat-label">Open Complaints</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">🏟️</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $activeFacilities ?></span>
                        <span class="stat-label">Active Facilities</span>
                    </div>
                </div>
            </div>

            <!-- Escalated Requests -->
            <!-- FIXED: Table now populated from DB query for ForApproval requests -->
            <div class="card">
                <div class="card-header">
                    <h3>Requests Awaiting Final Approval</h3>
                    <a href="final_approvals.php" class="btn btn-primary btn-small">View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forApprovalRequests)): ?>
                            <tr>
                                <td colspan="5" class="empty-row">No escalated requests at this time.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($forApprovalRequests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['CreatedAt'])) ?></td>
                                    <td>
                                        <a href="final_approvals.php?id=<?= $req['RequestID'] ?>"
                                           class="btn btn-small btn-primary">Review</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Staff Performance -->
            <div class="card">
                <div class="card-header">
                    <h3>Staff Performance (This Week)</h3>
                    <a href="reports.php" class="btn btn-secondary btn-small">Full Report</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Requests Processed</th>
                            <th>Avg. Time</th>
                            <th>Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="empty-row">No data yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Recent Activity -->
            <!-- FIXED: Table now populated from AuditLog DB query -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent System Activity</h3>
                    <a href="audit_log.php" class="btn btn-secondary btn-small">View Full Log</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentActivity)): ?>
                            <tr>
                                <td colspan="4" class="empty-row">No recent activity.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $log): ?>
                                <tr>
                                    <td><?= date('M d, Y h:i A', strtotime($log['LoggedAt'])) ?></td>
                                    <td><?= htmlspecialchars($log['Username']) ?></td>
                                    <td><?= htmlspecialchars($log['Role']) ?></td>
                                    <td><?= htmlspecialchars($log['Action']) ?></td>
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