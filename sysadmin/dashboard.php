<?php
// Barangay Connect – Sysadmin Dashboard
// sysadmin/dashboard.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');

// ── Stat counts ───────────────────────────────────────────────────────────────
$total    = $pdo->query("SELECT COUNT(*) FROM useraccount")->fetchColumn();
$active   = $pdo->query("SELECT COUNT(*) FROM useraccount WHERE status = 'Active'")->fetchColumn();
$pending  = $pdo->query("SELECT COUNT(*) FROM useraccount WHERE status = 'PendingVerification'")->fetchColumn();
$disabled = $pdo->query("SELECT COUNT(*) FROM useraccount WHERE status = 'Inactive'")->fetchColumn();

// ── Recent activity (last 10) ─────────────────────────────────────────────────
$activity = $pdo->query("
    SELECT a.created_at, a.action, a.record_affected,
           u.username, u.role
    FROM   auditlog a
    LEFT JOIN useraccount u ON u.id = a.user_id
    ORDER  BY a.created_at DESC
    LIMIT  10
")->fetchAll(PDO::FETCH_ASSOC);

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

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $total ?></span>
                        <span class="stat-label">Total Accounts</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $active ?></span>
                        <span class="stat-label">Active Accounts</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $pending ?></span>
                        <span class="stat-label">Pending Accounts</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">🚫</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $disabled ?></span>
                        <span class="stat-label">Disabled Accounts</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header"><h3>Quick Actions</h3></div>
                <div class="quick-actions">
                    <a href="user_accounts.php" class="quick-action-btn"><span>👥</span><span>Manage Accounts</span></a>
                    <a href="audit_log.php"      class="quick-action-btn"><span>📋</span><span>View Audit Log</span></a>
                    <a href="backup.php"          class="quick-action-btn"><span>💾</span><span>Run Backup</span></a>
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
                        <tr><td colspan="5" class="empty-row">No recent activity.</td></tr>
                    <?php else: ?>
                        <?php foreach ($activity as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td><?= htmlspecialchars($row['username'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['role']     ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['action']) ?></td>
                            <td><?= htmlspecialchars($row['record_affected'] ?? '—') ?></td>
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