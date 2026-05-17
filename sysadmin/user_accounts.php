<?php
// Barangay Connect – User Account Management
// sysadmin/user_accounts.php
// FIXED: added $pdo = get_db()
// FIXED: base filter excludes residents
// FIXED: removed 'resident' from role dropdown
// FIXED: added missing error messages (missing_fields, password_mismatch, password_short)
// FIXED: added View button per account
// FIXED: JS path corrected
// FIXED: Added pagination (25 per page).
// FIXED: :search named param used 2x → renamed to :search1/:search2 to avoid HY093
// FIXED: Create Account form now includes csrf_token hidden input

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');

$pdo = get_db();

// ── Filters ───────────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$role   = trim($_GET['role']   ?? '');
$status = trim($_GET['status'] ?? '');

$per_page = 25;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

// Base: admin roles only — residents are managed by Secretary
$where  = ["u.Role IN ('captain', 'secretary', 'staff', 'sysadmin')"];
$params = [];

if ($search !== '') {
    // FIXED: PDO does not allow the same named placeholder more than once.
    // Use :search1 and :search2 with the same value.
    $where[]            = "(u.Username LIKE :search1 OR u.FullName LIKE :search2)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
}
if ($role !== '') {
    $where[]          = "u.Role = :role";
    $params[':role']  = $role;
}
if ($status !== '') {
    $where[]            = "u.AccountStatus = :status";
    $params[':status']  = $status;
}

$where_sql = implode(' AND ', $where);

// Count total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM useraccount u WHERE $where_sql");
$count_stmt->execute($params);
$total_count = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_count / $per_page));
$page        = min($page, $total_pages);

$params[':limit']  = $per_page;
$params[':offset'] = $offset;

$sql = "SELECT u.UserAccountID AS id, u.Username AS username, u.FullName AS full_name,
               u.Role AS role, u.AccountStatus AS status,
               u.CreatedAt AS created_at, u.Email AS email
        FROM   useraccount u
        WHERE  $where_sql
        ORDER  BY u.CreatedAt DESC
        LIMIT  :limit OFFSET :offset";

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

        <div class="page-body">

            <?php if (isset($_GET['msg'])): ?>
                <?php $msgs = [
                    'created'           => ['success', '✅ Account created successfully.'],
                    'disabled'          => ['warning', '⚠️ Account has been disabled.'],
                    'enabled'           => ['success', '✅ Account has been enabled.'],
                    'deleted'           => ['success', '✅ Account has been deleted.'],
                    'missing_fields'    => ['error',   '❌ Please fill in all required fields.'],
                    'password_mismatch' => ['error',   '❌ Passwords do not match.'],
                    'password_short'    => ['error',   '❌ Password must be at least 8 characters.'],
                    'invalid_role'      => ['error',   '❌ Invalid role selected.'],
                    'cannot_self'       => ['error',   '❌ You cannot disable or delete your own account.'],
                    'last_sysadmin'     => ['error',   '❌ Cannot delete the last System Administrator account.'],
                ]; ?>
                <?php if (isset($msgs[$_GET['msg']])): [$type, $text] = $msgs[$_GET['msg']]; ?>
                    <div class="alert alert-<?= $type ?>"><?= $text ?></div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Account List -->
            <div class="card">
                <div class="card-header">
                    <h3>All User Accounts
                        <span class="badge badge-gray"><?= number_format($total_count) ?> found</span>
                    </h3>
                    <div class="card-actions">
                        <form method="GET" action="user_accounts.php" style="display:contents;">
                            <input type="text" name="search"
                                class="search-input"
                                placeholder="Search by username or name..."
                                value="<?= htmlspecialchars($search) ?>" />
                            <select name="role" class="filter-select">
                                <option value="">All Roles</option>
                                <?php foreach (['captain', 'secretary', 'staff', 'sysadmin'] as $r): ?>
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
                            <input type="hidden" name="page" value="1">
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
                                        <!-- View profile -->
                                        <a href="view_account.php?id=<?= $acc['id'] ?>"
                                            class="btn btn-secondary btn-small">View</a>

                                        <!-- Disable / Enable -->
                                        <?php if ($acc['status'] === 'Active'): ?>
                                            <a href="../handlers/user_account_handler.php?action=disable&id=<?= $acc['id'] ?>"
                                                class="btn btn-warning btn-small"
                                                onclick="return confirm('Disable this account?')">Disable</a>
                                        <?php else: ?>
                                            <a href="../handlers/user_account_handler.php?action=enable&id=<?= $acc['id'] ?>"
                                                class="btn btn-success btn-small"
                                                onclick="return confirm('Enable this account?')">Enable</a>
                                        <?php endif; ?>

                                        <!-- Delete -->
                                        <a href="../handlers/user_account_handler.php?action=delete&id=<?= $acc['id'] ?>"
                                            class="btn btn-danger btn-small"
                                            onclick="return confirm('Permanently delete this account? This cannot be undone.')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $base = '?search=' . urlencode($search) . '&role=' . urlencode($role) . '&status=' . urlencode($status);
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="<?= $base ?>&page=<?= $page - 1 ?>" class="btn btn-secondary btn-small">← Prev</a>
                    <?php endif; ?>
                    <span class="pagination-info">Page <?= $page ?> of <?= $total_pages ?></span>
                    <?php if ($page < $total_pages): ?>
                        <a href="<?= $base ?>&page=<?= $page + 1 ?>" class="btn btn-secondary btn-small">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

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
                    <!-- FIXED: CSRF token was missing from this form -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_generate()) ?>">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-input" />
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
                        <label>Password * <small>(min. 8 characters)</small></label>
                        <div class="input-wrap">
                        <input type="password" name="password" class="form-input pw-input" required minlength="8" />
                        <button type="button" class="input-toggle" onclick="togglePassword(this)" tabindex="-1">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <div class="input-wrap">
                        <input type="password" name="confirm_password" class="form-input pw-input" required />
                        <button type="button" class="input-toggle" onclick="togglePassword(this)" tabindex="-1">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    </div>
                    <div class="form-group form-full">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Create Account</button>
                            <button type="button" class="btn btn-secondary"
                                onclick="document.getElementById('create-form').style.display='none';
                                         document.querySelector('.btn-primary.btn-small').style.display=''">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<style>
.pagination {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    border-top: 1px solid #e2e8f0;
}
.pagination-info {
    font-size: 0.88rem;
    color: #6b7280;
}
</style>

<script src="/barangay_connect/assets/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>
