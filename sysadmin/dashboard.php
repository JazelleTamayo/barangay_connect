<?php
// Barangay Connect – Sysadmin Dashboard
// sysadmin/dashboard.php
// FIXED: stat cards now exclude residents (admin roles only)
// FIXED: broken HTML on empty-row (double </td> and stray 


require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');

$pdo = get_db();


$stTotal = $pdo->query("SELECT COUNT(*) FROM useraccount WHERE Role IN ('captain','secretary','staff','sysadmin')");
$stActive = $pdo->query("SELECT COUNT(*) FROM useraccount WHERE Role IN ('captain','secretary','staff','sysadmin') AND AccountStatus = 'Active'");
$stPending = $pdo->query("SELECT COUNT(*) FROM useraccount WHERE Role IN ('captain','secretary','staff','sysadmin') AND AccountStatus = 'PendingVerification'");
$stDisabled = $pdo->query("SELECT COUNT(*) FROM useraccount WHERE Role IN ('captain','secretary','staff','sysadmin') AND AccountStatus = 'Inactive'");

$total = (int) ($stTotal ? $stTotal->fetchColumn() : 0);
$active = (int) ($stActive ? $stActive->fetchColumn() : 0);
$pending = (int) ($stPending ? $stPending->fetchColumn() : 0);
$disabled = (int) ($stDisabled ? $stDisabled->fetchColumn() : 0);

// ── Recent activity (last 10 audit log entries) ───────────────────────────────
$stmt = $pdo->prepare("
SELECT a.LoggedAt AS created_at, a.Action, a.RecordAffected,
a.Username, a.Role
FROM auditlog a
ORDER BY a.LoggedAt DESC
LIMIT 10
");
$stmt->execute();
$activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'System Admin Dashboard';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>System Admin Dashboard</h1>
            <span class="page-subtitle">Welcome, <?= current_user_name() ?></span>
        </div>
        <div class="page-body">

            <!-- Stats — admin accounts only -->
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= (int) $total ?></span>
                        <span class="stat-label">Total Admin Accounts</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= (int) $active ?></span>
                        <span class="stat-label">Active Accounts</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= (int) $pending ?></span>
                        <span class="stat-label">Pending Accounts</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">🚫</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= (int) $disabled ?></span>
                        <span class="stat-label">Disabled Accounts</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="quick-actions">
                    <a href="user_accounts.php" class="quick-action-btn"><span>👥</span><span>Manage Accounts</span></a>
                    <a href="audit_log.php" class="quick-action-btn"><span>📋</span><span>View Audit Log</span></a>
                    <a href="backup.php" class="quick-action-btn"><span>💾</span><span>Run Backup</span></a>
                    <a href="system_settings.php" class="quick-action-btn"><span>⚙️</span><span>System Settings</span></a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card mt-4">
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
                            <th>Record</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activity)): ?>
                            <tr>
                                <td colspan="5" class="empty-row">No recent activity.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activity as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    <td><?= htmlspecialchars($row['Username']       ?? '—') ?></td>
                                    <td><?= htmlspecialchars(ucfirst($row['Role'])  ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['Action']) ?></td>
                                    <td><?= htmlspecialchars($row['RecordAffected'] ?? '—') ?></td>
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