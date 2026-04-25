<?php
// Barangay Connect – Database Backup
// sysadmin/backup.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');

// ── Load backup files from folder ─────────────────────────────────────────────
$backup_dir = __DIR__ . '/../backups/';
$backups    = [];

if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*.sql');
    if ($files) {
        // Sort newest first
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size'     => round(filesize($file) / 1024, 2) . ' KB',
                'created'  => date('Y-m-d H:i:s', filemtime($file)),
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
        <div class="page-header">
            <h1>Database Backup</h1>
            <span class="page-subtitle">Manage and create database backups</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'success'): ?>
                    <div class="alert alert-success">✅ Backup completed successfully.</div>
                <?php elseif ($_GET['msg'] === 'failed'): ?>
                    <div class="alert alert-error">❌ Backup failed. Please check server permissions.</div>
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
                        <tr><td colspan="4" class="empty-row">No backups found.</td></tr>
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

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>