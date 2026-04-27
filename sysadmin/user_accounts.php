<?php
// Barangay Connect – User Account Management
// sysadmin/user_accounts.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');

// ── Filters ───────────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$role   = trim($_GET['role']   ?? '');
$status = trim($_GET['status'] ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = "(u.Username LIKE :search OR u.FullName LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($role !== '') {
    $where[]  = "u.Role = :role";
    $params[':role'] = $role;
}
if ($status !== '') {
    $where[]  = "u.AccountStatus = :status";
    $params[':status'] = $status;
}

$sql = "SELECT u.UserAccountID AS id, u.Username AS username, u.FullName AS full_name,
               u.Role AS role, u.AccountStatus AS status,
               u.CreatedAt AS created_at, u.Email AS email
        FROM   useraccount u
        WHERE  " . implode(' AND ', $where) . "
        ORDER  BY u.CreatedAt DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'User Account Management';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>User Account Management</h1>
            <span class="page-subtitle">Create, disable, and assign roles to accounts</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg'])): ?>
                <?php $msgs = [
                    'created'  => ['success', '✅ Account created successfully.'],
                    'disabled' => ['warning', '⚠️ Account has been disabled.'],
                    'enabled'  => ['success', '✅ Account has been enabled.'],
                    'deleted'  => ['success', '✅ Account has been deleted.'],
                ]; ?>
                <?php if (isset($msgs[$_GET['msg']])): [$type, $text] = $msgs[$_GET['msg']]; ?>
                    <div class="alert alert-<?= $type ?>"><?= $text ?></div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Account List -->
            <div class="card">
                <div class="card-header">
                    <h3>All User Accounts</h3>
                    <div class="card-actions">
                        <!-- Filter form (GET so filters stay in URL) -->
                        <form method="GET" action="user_accounts.php"
                            style="display:contents;">
                            <input type="text" name="search"
                                class="search-input"
                                placeholder="Search by username or name..."
                                value="<?= htmlspecialchars($search) ?>" />
                            <select name="role" class="filter-select">
                                <option value="">All Roles</option>
                                <?php foreach (['captain', 'secretary', 'staff', 'sysadmin', 'resident'] as $r): ?>
                                    <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>>
                                        <?= ucfirst($r) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status" class="filter-select">
                                <option value="">All Status</option>
                                <?php foreach (['Active', 'Inactive', 'PendingVerification', 'Rejected'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
                                        <?= $s ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-secondary btn-small">Filter</button>
                        </form>
                        <button class="btn btn-primary btn-small"
                            onclick="document.getElementById('create-form').style.display='block';
                                     this.style.display='none'">
                            + New Account
                        </button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accounts)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No accounts found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $acc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($acc['username']) ?></td>
                                    <td><?= htmlspecialchars($acc['full_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars(ucfirst($acc['role'])) ?></td>
                                    <td>
                                        <?php
                                        $badge = [
                                            'Active'              => 'badge-green',
                                            'Inactive'            => 'badge-red',
                                            'PendingVerification' => 'badge-yellow',
                                            'Rejected'            => 'badge-gray',
                                        ][$acc['status']] ?? 'badge-gray';
                                        ?>
                                        <span class="badge <?= $badge ?>">
                                            <?= htmlspecialchars($acc['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($acc['created_at']) ?></td>
                                    <td class="table-actions">
                                        <?php if ($acc['status'] === 'Active'): ?>
                                            <a href="../handlers/user_account_handler.php?action=disable&id=<?= $acc['id'] ?>"
                                                class="btn btn-warning btn-small"
                                                onclick="return confirm('Disable this account?')">Disable</a>
                                        <?php else: ?>
                                            <a href="../handlers/user_account_handler.php?action=enable&id=<?= $acc['id'] ?>"
                                                class="btn btn-success btn-small"
                                                onclick="return confirm('Enable this account?')">Enable</a>
                                        <?php endif; ?>
                                        <a href="../handlers/user_account_handler.php?action=delete&id=<?= $acc['id'] ?>"
                                            class="btn btn-danger btn-small"
                                            onclick="return confirm('Permanently delete this account?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Create Account Form -->
            <div class="card mt-4" id="create-form" style="display:none;">
                <div class="card-header">
                    <h3>Create New Account</h3>
                </div>
                <form method="POST"
                    action="../handlers/user_account_handler.php"
                    class="form-grid validate-form">
                    <input type="hidden" name="action" value="create" />
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Select Role --</option>
                            <option value="captain">Barangay Captain</option>
                            <option value="secretary">Barangay Secretary</option>
                            <option value="staff">Barangay Staff</option>
                            <option value="sysadmin">System Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-input" />
                    </div>
                    <div class="form-group form-full">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Create Account</button>
                            <button type="button" class="btn btn-secondary"
                                onclick="document.getElementById('create-form').style.display='none'">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>
<script src="/barangay_connect/assets/css/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>