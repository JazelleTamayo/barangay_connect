<?php
// Barangay Connect – Resident Management
// secretary/resident_management.php
// FIXED: Only shows actual residents (role='resident' or no linked account).
//        Admin accounts (captain, secretary, staff, sysadmin) are excluded.
// FIXED: Removed HTML comment from inside <input> tag that caused
//        oninput="..." to leak as visible raw text on the page.

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo    = get_db();
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

// FIXED: Only pull residents whose linked UserAccount has role='resident',
// OR residents with no UserAccount yet (encoded by staff, pending self-registration).
// This excludes captain, secretary, staff, and sysadmin accounts entirely.
//Filters out Rejected accounts
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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll();

$page_title = 'Resident Management';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Resident Management</h1>
            <span class="page-subtitle">View, search, and manage all barangay residents</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">✅ Resident record updated successfully.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>All Residents
                        <span style="font-size:0.82rem;font-weight:400;color:var(--text-light);margin-left:8px;">
                            (<?= count($residents) ?> found)
                        </span>
                    </h3>
                    <div class="card-actions">
                        <!-- FIXED: HTML comment removed from inside <input> tag -->
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
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>