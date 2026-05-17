<?php
// Barangay Connect – Resident Management
// secretary/resident_management.php
// FIXED: Only shows actual residents (role='resident' or no linked account).
//        Admin accounts (captain, secretary, staff, sysadmin) are excluded.
// FIXED: Removed HTML comment from inside <input> tag that caused
//        oninput="..." to leak as visible raw text on the page.
// FIXED: Added pagination (25 per page).

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo    = get_db();
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$per_page = 25;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

// Base WHERE conditions
$sql    = "
    SELECT r.*, ua.AccountStatus
    FROM Resident r
    LEFT JOIN UserAccount ua ON ua.ResidentID = r.ResidentID
    WHERE (ua.Role = 'resident' OR ua.UserAccountID IS NULL)
    AND (ua.AccountStatus IS NULL OR ua.AccountStatus != 'Rejected')
    AND (ua.Role = 'resident' OR ua.Role IS NULL)
";
$params = [];

if ($status !== '') {
    $sql     .= " AND r.Status = ?";
    $params[] = $status;
}

if ($search !== '') {
    $like     = '%' . $search . '%';
    $sql     .= " AND (
        r.FirstName  LIKE ? OR
        r.LastName   LIKE ? OR
        r.MiddleName LIKE ? OR
        r.Purok      LIKE ?
    )";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY r.LastName, r.FirstName";

// Count total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM ($sql) AS sub");
$count_stmt->execute($params);
$total_count = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_count / $per_page));
$page        = min($page, $total_pages);

// Paginated fetch
$paged_sql  = $sql . " LIMIT ? OFFSET ?";
$paged_params = array_merge($params, [$per_page, $offset]);
$stmt = $pdo->prepare($paged_sql);
$stmt->execute($paged_params);
$residents = $stmt->fetchAll();

$page_title = 'Resident Management';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
                <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">✅ Resident record updated successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'not_allowed'): ?>
                <div class="alert alert-danger">This resident record cannot be edited from the Secretary module.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>All Residents
                        <span style="font-size:0.82rem;font-weight:400;color:var(--text-light);margin-left:8px;">
                            (<?= number_format($total_count) ?> found)
                        </span>
                    </h3>
                    <div class="card-actions">
                        <form method="GET" action="" class="inline-form" id="filter-form">
                            <input
                                type="text"
                                name="search"
                                class="search-input"
                                placeholder="Search by name, purok..."
                                value="<?= htmlspecialchars($search) ?>"
                                oninput="document.getElementById('filter-form').submit()" />
                            <select
                                name="status"
                                class="filter-select"
                                onchange="document.getElementById('filter-form').submit()">
                                <option value="" <?= $status === ''         ? 'selected' : '' ?>>All Status</option>
                                <option value="Active" <?= $status === 'Active'   ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <!-- preserve page on filter submit -->
                            <input type="hidden" name="page" value="1">
                        </form>
                        <a href="../staff/resident_encoding.php" class="btn btn-primary btn-small">+ Add Resident</a>
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Resident ID</th>
                            <th>Full Name</th>
                            <th>Birthdate</th>
                            <th>Purok</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($residents)): ?>
                            <tr>
                                <td colspan="7" class="empty-row">No residents found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($residents as $r): ?>
                                <tr>
                                    <td><?= (int) $r['ResidentID'] ?></td>
                                    <td>
                                        <?= htmlspecialchars(trim(
                                            $r['FirstName'] . ' '
                                                . ($r['MiddleName'] ? $r['MiddleName'] . ' ' : '')
                                                . $r['LastName']
                                        )) ?>
                                    </td>
                                    <td><?= htmlspecialchars($r['Birthdate'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($r['Purok'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($r['ContactNumber'] ?? '—') ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($r['Status'] ?? 'inactive') ?>">
                                            <?= htmlspecialchars($r['Status'] ?? 'Unknown') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="resident_edit.php?id=<?= (int) $r['ResidentID'] ?>"
                                            class="btn btn-small btn-secondary">Edit</a>
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
                    $base = '?search=' . urlencode($search) . '&status=' . urlencode($status);
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

<?php include '../includes/footer.php'; ?>
