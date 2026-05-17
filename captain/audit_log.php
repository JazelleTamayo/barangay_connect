<?php
// Barangay Connect – Captain Audit Log
// captain/audit_log.php
// FIXED: Search/date inputs were purely decorative (no filter logic) — now actually filter results
// FIXED: IP Address column was in the query but never rendered in the table
// FIXED: Added CSV export and record count

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

$pdo = get_db();

// ── Filter params ─────────────────────────────────────────────────────────────
$search    = trim($_GET['search']    ?? '');
$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to']   ?? '');

// ── CSV Export ────────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Build same filtered query
    $sql    = "SELECT LoggedAt, Username, Role, Action, RecordAffected, IPAddress FROM AuditLog WHERE 1=1";
    $params = [];
    if ($search !== '') {
        $sql    .= " AND (Username LIKE ? OR Action LIKE ? OR Role LIKE ?)";
        $like    = '%' . $search . '%';
        $params  = array_merge($params, [$like, $like, $like]);
    }
    if ($dateFrom !== '') { $sql .= " AND DATE(LoggedAt) >= ?"; $params[] = $dateFrom; }
    if ($dateTo   !== '') { $sql .= " AND DATE(LoggedAt) <= ?"; $params[] = $dateTo; }
    $sql .= " ORDER BY LoggedAt DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_log_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Timestamp', 'User', 'Role', 'Action', 'Record Affected', 'IP Address']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['LoggedAt'], $r['Username'], $r['Role'],
            $r['Action'], $r['RecordAffected'] ?? '', $r['IPAddress'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

// ── Build filtered query ──────────────────────────────────────────────────────
$sql    = "SELECT LogID, LoggedAt, Username, Role, Action, RecordAffected, IPAddress
           FROM AuditLog WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql    .= " AND (Username LIKE ? OR Action LIKE ? OR Role LIKE ?)";
    $like    = '%' . $search . '%';
    $params  = array_merge($params, [$like, $like, $like]);
}
if ($dateFrom !== '') { $sql .= " AND DATE(LoggedAt) >= ?"; $params[] = $dateFrom; }
if ($dateTo   !== '') { $sql .= " AND DATE(LoggedAt) <= ?"; $params[] = $dateTo; }

$sql .= " ORDER BY LoggedAt DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Audit Log';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-body">

            <div class="card">
                <div class="card-header">
                    <h3>System Activity Log</h3>
                    <div class="card-actions">
                        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"
                           class="btn btn-secondary btn-small" download>⬇ Export CSV</a>
                    </div>
                </div>

                <!-- FIXED: Filter form — was non-functional inputs with no submit -->
                <form method="GET" class="filter-bar">
                    <input type="text"
                           name="search"
                           class="form-input"
                           placeholder="Search user, role, or action..."
                           value="<?= htmlspecialchars($search) ?>" />
                    <input type="date" name="date_from" class="form-input"
                           value="<?= htmlspecialchars($dateFrom) ?>"
                           title="From date" />
                    <input type="date" name="date_to" class="form-input"
                           value="<?= htmlspecialchars($dateTo) ?>"
                           title="To date" />
                    <button type="submit" class="btn btn-primary btn-small">Filter</button>
                    <?php if ($search || $dateFrom || $dateTo): ?>
                        <a href="audit_log.php" class="btn btn-secondary btn-small">Clear</a>
                    <?php endif; ?>
                </form>

                <p class="result-count">
                    <?= count($auditLogs) ?> entr<?= count($auditLogs) === 1 ? 'y' : 'ies' ?> shown
                    <?= ($search || $dateFrom || $dateTo) ? '(filtered)' : '(latest 200)' ?>
                </p>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Record Affected</th>
                            <!-- FIXED: IP Address was queried but never rendered -->
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($auditLogs)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No log entries match the current filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td><?= date('M d, Y h:i A', strtotime($log['LoggedAt'])) ?></td>
                                    <td><?= htmlspecialchars($log['Username']) ?></td>
                                    <td><?= htmlspecialchars($log['Role']) ?></td>
                                    <td><?= htmlspecialchars($log['Action']) ?></td>
                                    <td><?= htmlspecialchars($log['RecordAffected'] ?? '—') ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($log['IPAddress'] ?? '—') ?></td>
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
.filter-bar { display: flex; gap: 8px; flex-wrap: wrap; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; }
.filter-bar .form-input { flex: 1; min-width: 160px; max-width: 260px; }
.result-count { font-size: 0.82rem; color: #6b7280; padding: 6px 16px 0; }
.card-actions { display: flex; gap: 8px; align-items: center; }
.text-muted { color: #9ca3af; font-size: 0.82rem; }
</style>
<?php include '../includes/footer.php'; ?>