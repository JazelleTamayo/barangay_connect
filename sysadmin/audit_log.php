<?php
// Barangay Connect – Sysadmin Audit Log
// sysadmin/audit_log.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');

// ── Filters ───────────────────────────────────────────────────────────────────
$search    = trim($_GET['search']    ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to']   ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = "(a.Username LIKE :search OR a.Action LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($date_from !== '') {
    $where[]  = "DATE(a.LoggedAt) >= :date_from";
    $params[':date_from'] = $date_from;
}
if ($date_to !== '') {
    $where[]  = "DATE(a.LoggedAt) <= :date_to";
    $params[':date_to'] = $date_to;
}

$sql = "SELECT a.LogID AS id, a.LoggedAt AS created_at, a.Action AS action,
               a.RecordAffected AS record_affected, a.IPAddress AS ip_address,
               a.Username AS username, a.Role AS role
        FROM   auditlog a
        WHERE  " . implode(' AND ', $where) . "
        ORDER  BY a.LoggedAt DESC
        LIMIT  200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Audit Log';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Audit Log</h1>
            <span class="page-subtitle">Full system activity and access log</span>
        </div>
        <div class="page-body">

            <div class="card">
                <div class="card-header">
                    <h3>System Activity Log</h3>
                    <div class="card-actions">
                        <form method="GET" action="audit_log.php" style="display:contents;">
                            <input type="text" name="search"
                                class="search-input"
                                placeholder="Search by user or action..."
                                value="<?= htmlspecialchars($search) ?>" />
                            <input type="date" name="date_from"
                                class="date-input" title="From date"
                                value="<?= htmlspecialchars($date_from) ?>" />
                            <input type="date" name="date_to"
                                class="date-input" title="To date"
                                value="<?= htmlspecialchars($date_to) ?>" />
                            <button type="submit" class="btn btn-secondary btn-small">Filter</button>
                        </form>
                        <a href="audit_log.php?export=csv&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"
                            class="btn btn-secondary btn-small">Export CSV</a>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Record Affected</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No log entries found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                                    <td><?= htmlspecialchars($log['username']        ?? '—') ?></td>
                                    <td><?= htmlspecialchars(ucfirst($log['role'])   ?? '—') ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= htmlspecialchars($log['record_affected'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($log['ip_address']      ?? '—') ?></td>
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