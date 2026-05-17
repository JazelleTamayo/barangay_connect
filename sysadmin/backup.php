<?php
// Barangay Connect – Database Backup
// sysadmin/backup.php
// FIXED: removed stray backslash after <?php if (isset($_GET['msg'])): 


require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');

// ── Load backup files from folder ─────────────────────────────────────────────
$backup_dir = __DIR__ . '/../backups/';
$backups = [];

if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*.sql');
    if ($files) {
        // Sort newest first
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => round(filesize($file) / 1024, 2) . ' KB',
                'created' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }
    }
}

$page_title = 'Database Backup';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-body">

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'success'): ?>
                    <div class="alert alert-success">✅ Backup completed successfully.</div>
                <?php elseif ($_GET['msg'] === 'failed'): ?>
                    <div class="alert alert-error">❌ Backup failed. Please check server permissions.</div>
                <?php elseif ($_GET['msg'] === 'purged'): ?>
                    <div class="alert alert-success">✅ Audit log purge complete. <?= (int)($_GET['count'] ?? 0) ?> record(s) older than 1 year deleted.</div>
                <?php elseif ($_GET['msg'] === 'purge_failed'): ?>
                    <div class="alert alert-error">❌ Audit log purge failed. Please try again.</div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Manual Backup -->
            <div class="card">
                <div class="card-header">
                    <h3>Manual Backup</h3>
                    <p class="card-desc">
                        Create a backup of the Barangay Connect database.
                        Backups are saved to the <code>/backups/</code> folder on the server.
                    </p>
                </div>
                <div style="padding: 24px;">
                    <form method="POST" action="../handlers/backup_handler.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_generate()) ?>">
                        <input type="hidden" name="action" value="backup" />
                        <button type="submit" class="btn btn-primary">💾 Run Backup Now</button>
                    </form>
                </div>
            </div>

            <!-- Backup History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Backup History</h3>
                    <span class="card-desc"><?= count($backups) ?> backup(s) found</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="4" class="empty-row">No backups found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td>📄 <?= htmlspecialchars($b['filename']) ?></td>
                                    <td><?= $b['size'] ?></td>
                                    <td><?= $b['created'] ?></td>
                                    <td>
                                        <a href="../handlers/backup_handler.php?action=download&file=<?= urlencode($b['filename']) ?>"
                                            class="btn btn-secondary btn-small">⬇ Download</a>
                                        <a href="../handlers/backup_handler.php?action=delete&file=<?= urlencode($b['filename']) ?>"
                                            class="btn btn-danger btn-small"
                                            onclick="return confirm('Delete this backup file?')">🗑 Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Audit Log Retention (BR-11) -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Audit Log Retention</h3>
                    <p class="card-desc">
                        BR-11 requires logs be kept for a minimum of 1 year.
                        This action permanently deletes all audit log entries older than 1 year.
                    </p>
                </div>
                <div style="padding: 24px;">
                    <?php
                    $pdo_count = get_db();
                    $old_count = $pdo_count->query(
                        "SELECT COUNT(*) FROM auditlog WHERE LoggedAt < DATE_SUB(NOW(), INTERVAL 1 YEAR)"
                    )->fetchColumn();
                    ?>
                    <p style="margin-bottom:16px;">
                        Records older than 1 year: <strong><?= number_format((int)$old_count) ?></strong>
                    </p>
                    <form method="POST" action="../handlers/backup_handler.php"
                        onsubmit="return confirm('Permanently delete <?= (int)$old_count ?> audit log record(s) older than 1 year? This cannot be undone.')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_generate()) ?>">
                        <input type="hidden" name="action" value="purge_logs">
                        <button type="submit" class="btn btn-danger"
                            <?= $old_count == 0 ? 'disabled' : '' ?>>
                            🗑 Purge Old Logs (<?= (int)$old_count ?> records)
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>