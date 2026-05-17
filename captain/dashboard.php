<?php
// Barangay Connect – Captain Dashboard
// captain/dashboard.php
// FIXED: Staff Performance table now populated from DB (was hardcoded "No data yet")

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

$pdo = get_db();

// Stat 1 — total active residents
$totalResidents = $pdo->query(
    "SELECT COUNT(*) FROM Resident WHERE Status = 'Active'"
)->fetchColumn();

// Stat 2 — pending requests (Pending + ForApproval)
$pendingRequests = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest WHERE Status IN ('Pending','ForApproval')"
)->fetchColumn();

// Stat 3 — open complaints
$openComplaints = $pdo->query(
    "SELECT COUNT(*) FROM ServiceRequest
     WHERE RequestType = 'Complaint'
       AND Status NOT IN ('Rejected','Cancelled','Released')"
)->fetchColumn();

// Stat 4 — active facilities
$activeFacilities = $pdo->query(
    "SELECT COUNT(*) FROM Facility WHERE Status = 'Active'"
)->fetchColumn();

// Approvals table — ForApproval requests
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

// FIXED: Staff Performance — last 7 days, query DB instead of hardcoded empty
$staffPerformance = $pdo->query(
    "SELECT ua.FullName,
            ua.Role,
            COUNT(sr.RequestID)                              AS Processed,
            AVG(TIMESTAMPDIFF(HOUR, sr.CreatedAt, sr.ProcessedAt)) AS AvgHours
     FROM UserAccount ua
     LEFT JOIN ServiceRequest sr
            ON ua.UserAccountID = sr.ProcessedBy
           AND sr.ProcessedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     WHERE ua.Role IN ('secretary', 'staff')
       AND ua.AccountStatus = 'Active'
     GROUP BY ua.UserAccountID, ua.FullName, ua.Role
     ORDER BY Processed DESC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

// Recent Activity — last 10 AuditLog entries
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
        <div class="page-body">

            <!-- Stats -->
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

            <!-- FIXED: Staff Performance — now populated from DB (last 7 days) -->
            <div class="card">
                <div class="card-header">
                    <h3>Staff Performance (This Week)</h3>
                    <a href="reports.php" class="btn btn-secondary btn-small">Full Report</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Role</th>
                            <th>Requests Processed</th>
                            <th>Avg. Processing Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staffPerformance)): ?>
                            <tr>
                                <td colspan="4" class="empty-row">No staff activity this week.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($staffPerformance as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['FullName'] ?? 'Unnamed') ?></td>
                                    <td><?= htmlspecialchars(ucfirst($s['Role'])) ?></td>
                                    <td><?= (int)$s['Processed'] ?></td>
                                    <td><?= $s['AvgHours'] !== null ? number_format((float)$s['AvgHours'], 1) . 'h' : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Activity -->
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