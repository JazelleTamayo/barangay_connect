<?php
// Barangay Connect – View Admin Account Profile
// sysadmin/view_account.php
// FIXED: profile table queries use UserID (not UserAccountID)
// FIXED: skip list updated to match actual PK/FK column names

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');

$pdo = get_db();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: user_accounts.php');
    exit;
}

// Fetch the account — admin roles only (no residents)
$stmt = $pdo->prepare("
    SELECT u.UserAccountID AS id,
           u.Username      AS username,
           u.FullName      AS full_name,
           u.Role          AS role,
           u.AccountStatus AS status,
           u.Email         AS email,
           u.CreatedAt     AS created_at
    FROM   useraccount u
    WHERE  u.UserAccountID = :id
    AND    u.Role IN ('captain', 'secretary', 'staff', 'sysadmin')
");
$stmt->execute([':id' => $id]);
$acc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$acc) {
    header('Location: user_accounts.php');
    exit;
}

// Fetch the role-specific profile table based on role
$profile = [];
switch ($acc['role']) {
    case 'captain':
        $ps = $pdo->prepare("SELECT * FROM captainprofile WHERE UserID = :id");
        $ps->execute([':id' => $id]);
        $profile = $ps->fetch(PDO::FETCH_ASSOC) ?: [];
        break;
    case 'secretary':
        $ps = $pdo->prepare("SELECT * FROM secretaryprofile WHERE UserID = :id");
        $ps->execute([':id' => $id]);
        $profile = $ps->fetch(PDO::FETCH_ASSOC) ?: [];
        break;
    case 'staff':
        $ps = $pdo->prepare("SELECT * FROM staffprofile WHERE UserID = :id");
        $ps->execute([':id' => $id]);
        $profile = $ps->fetch(PDO::FETCH_ASSOC) ?: [];
        break;
    case 'sysadmin':
        $ps = $pdo->prepare("SELECT * FROM systemadminprofile WHERE UserID = :id");
        $ps->execute([':id' => $id]);
        $profile = $ps->fetch(PDO::FETCH_ASSOC) ?: [];
        break;
}

// Fetch recent audit log entries for this user
$al = $pdo->prepare("
    SELECT LoggedAt, Action, RecordAffected
    FROM   auditlog
    WHERE  Username = :username
    ORDER  BY LoggedAt DESC
    LIMIT  10
");
$al->execute([':username' => $acc['username']]);
$logs = $al->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'View Account — ' . htmlspecialchars($acc['full_name']);
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Account Profile</h1>
            <span class="page-subtitle">Viewing: <?= htmlspecialchars($acc['full_name']) ?></span>
        </div>
        <div class="page-body">

            <div style="margin-bottom: 16px;">
                <a href="user_accounts.php" class="btn btn-secondary btn-small">← Back to Accounts</a>
            </div>

            <!-- Account Details -->
            <div class="card">
                <div class="card-header">
                    <h3>Account Information</h3>
                    <div class="card-actions">
                        <?php if ($acc['status'] === 'Active'): ?>
                            <a href="../handlers/user_account_handler.php?action=disable&id=<?= $acc['id'] ?>"
                                class="btn btn-warning btn-small"
                                onclick="return confirm('Disable this account?')">Disable Account</a>
                        <?php else: ?>
                            <a href="../handlers/user_account_handler.php?action=enable&id=<?= $acc['id'] ?>"
                                class="btn btn-success btn-small"
                                onclick="return confirm('Enable this account?')">Enable Account</a>
                        <?php endif; ?>
                        <?php if ($acc['id'] !== (int) $_SESSION['user_id']): ?>
                            <a href="../handlers/user_account_handler.php?action=delete&id=<?= $acc['id'] ?>"
                                class="btn btn-danger btn-small"
                                onclick="return confirm('Permanently delete this account?')">Delete Account</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-grid" style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="detail-item">
                        <span class="detail-label">Username</span>
                        <span class="detail-value"><?= htmlspecialchars($acc['username']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Full Name</span>
                        <span class="detail-value"><?= htmlspecialchars($acc['full_name'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Role</span>
                        <span class="detail-value"><?= htmlspecialchars(ucfirst($acc['role'])) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?= htmlspecialchars($acc['email'] ?? '—') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <?php
                            $badge = [
                                'Active'              => 'badge-green',
                                'Inactive'            => 'badge-red',
                                'PendingVerification' => 'badge-yellow',
                                'Rejected'            => 'badge-gray',
                            ][$acc['status']] ?? 'badge-gray';
                            ?>
                            <span class="badge <?= $badge ?>"><?= htmlspecialchars($acc['status']) ?></span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date Created</span>
                        <span class="detail-value"><?= htmlspecialchars($acc['created_at']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Role Profile Details -->
            <?php if (!empty($profile)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3><?= ucfirst($acc['role']) ?> Profile Details</h3>
                    </div>
                    <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <?php foreach ($profile as $key => $value): ?>
                            <?php if (in_array($key, ['UserID', 'CaptainID', 'SecretaryID', 'StaffID', 'AdminID'])) continue; ?>
                            <div class="detail-item">
                                <span class="detail-label"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></span>
                                <span class="detail-value"><?= htmlspecialchars($value ?? '—') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3><?= ucfirst($acc['role']) ?> Profile Details</h3>
                    </div>
                    <div style="padding: 24px;">
                        <p class="empty-row">No additional profile details found.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Activity -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Recent Activity</h3>
                    <a href="audit_log.php?search=<?= urlencode($acc['username']) ?>" class="btn btn-secondary btn-small">View Full Log</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Action</th>
                            <th>Record Affected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="3" class="empty-row">No activity found for this account.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['LoggedAt']) ?></td>
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