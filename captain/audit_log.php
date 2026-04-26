<?php
// Barangay Connect – Captain Audit Log
// captain/audit_log.php
// FIXED: Added PHP query to load AuditLog entries (previously hardcoded empty table)

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

// FIXED: Load all audit log entries, newest first
$pdo = get_db();
$auditLogs = $pdo->query(
    "SELECT LogID, Username, Role, Action, RecordAffected, IPAddress, LoggedAt
     FROM AuditLog
     ORDER BY LoggedAt DESC
     LIMIT 200"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Audit Log';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Audit Log</h1>
            <span class="page-subtitle">Read-only system activity trail</span>
        </div>
        <div class="page-body">

            <div class="card">
                <div class="card-header">
                    <h3>System Activity Log</h3>
                    <div class="card-actions">
                        <input type="text" class="search-input" placeholder="Search by user or action..." />
                        <input type="date" class="date-input" />
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Record Affected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- FIXED: Table now populated from AuditLog DB query -->
                        <?php if (empty($auditLogs)): ?>
                            <tr>
                                <td colspan="5" class="empty-row">No log entries yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td><?= date('M d, Y h:i A', strtotime($log['LoggedAt'])) ?></td>
                                    <td><?= htmlspecialchars($log['Username']) ?></td>
                                    <td><?= htmlspecialchars($log['Role']) ?></td>
                                    <td><?= htmlspecialchars($log['Action']) ?></td>
                                    <td><?= htmlspecialchars($log['RecordAffected'] ?? '—') ?></td>
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