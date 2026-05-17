<?php
// Barangay Connect – Request Processing
// secretary/request_processing.php (show For Approval by default)
// FIXED: Added pagination (25 per page).

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo = get_db();

$filterType   = trim($_GET['type']   ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$validTypes    = ['Clearance', 'Indigency', 'FacilityReservation', 'Complaint'];
$validStatuses = ['all', 'Pending', 'ForApproval', 'Approved', 'Prepared', 'Released', 'Rejected', 'Cancelled'];

if ($filterType !== '' && !in_array($filterType, $validTypes, true)) {
    $filterType = '';
}
if ($filterStatus !== '' && !in_array($filterStatus, $validStatuses, true)) {
    $filterStatus = '';
}

// Default to 'ForApproval' if no explicit status filter is set
if ($filterStatus === '' && !isset($_GET['status'])) {
    $filterStatus = 'ForApproval';
}

$per_page = 25;
$page     = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];

if ($filterType !== '') {
    $where[]  = 'sr.RequestType = ?';
    $params[] = $filterType;
}
if ($filterStatus !== '' && $filterStatus !== 'all') {
    $where[]  = 'sr.Status = ?';
    $params[] = $filterStatus;
}

$where_sql = implode(' AND ', $where);

$base_sql = "FROM ServiceRequest sr
             JOIN Resident r ON sr.ResidentID = r.ResidentID
             WHERE $where_sql";

// Count total
$count_stmt = $pdo->prepare("SELECT COUNT(*) $base_sql");
$count_stmt->execute($params);
$total_count = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_count / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// Paginated fetch
$stmt = $pdo->prepare(
    "SELECT sr.RequestID, sr.ReferenceNo,
            CONCAT(r.FirstName, ' ', r.LastName) AS ResidentName,
            sr.RequestType, sr.CreatedAt, sr.Status
     $base_sql
     ORDER BY sr.CreatedAt DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$per_page, $offset]));
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Process Requests';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
                <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
                <div class="alert alert-success">✅ Request approved successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'rejected'): ?>
                <div class="alert alert-error">❌ Request has been rejected.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
                <div class="alert alert-warning">🚫 Request has been cancelled.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>All Service Requests
                        <span style="font-size:0.82rem;font-weight:400;color:var(--text-light);margin-left:8px;">
                            (<?= number_format($total_count) ?> found)
                        </span>
                    </h3>
                    <form method="GET" class="card-actions" id="requestFilterForm">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search loaded results..." />
                        <select name="type" class="filter-select" onchange="this.form.page.value=1;this.form.submit()">
                            <option value="" <?= $filterType === '' ? 'selected' : '' ?>>All Types</option>
                            <option value="Clearance"           <?= $filterType === 'Clearance'           ? 'selected' : '' ?>>Clearance</option>
                            <option value="Indigency"           <?= $filterType === 'Indigency'           ? 'selected' : '' ?>>Indigency</option>
                            <option value="FacilityReservation" <?= $filterType === 'FacilityReservation' ? 'selected' : '' ?>>Facility Reservation</option>
                            <option value="Complaint"           <?= $filterType === 'Complaint'           ? 'selected' : '' ?>>Complaint</option>
                        </select>
                        <select name="status" class="filter-select" onchange="this.form.page.value=1;this.form.submit()">
                            <option value="all"          <?= $filterStatus === 'all'          ? 'selected' : '' ?>>All Status</option>
                            <option value="ForApproval"  <?= $filterStatus === 'ForApproval'  ? 'selected' : '' ?>>For Approval</option>
                            <option value="Approved"     <?= $filterStatus === 'Approved'     ? 'selected' : '' ?>>Approved</option>
                            <option value="Prepared"     <?= $filterStatus === 'Prepared'     ? 'selected' : '' ?>>Prepared</option>
                            <option value="Released"     <?= $filterStatus === 'Released'     ? 'selected' : '' ?>>Released</option>
                            <option value="Rejected"     <?= $filterStatus === 'Rejected'     ? 'selected' : '' ?>>Rejected</option>
                            <option value="Cancelled"    <?= $filterStatus === 'Cancelled'    ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>
                <table class="data-table" id="requestsTable">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                                <tr data-type="<?= htmlspecialchars($req['RequestType']) ?>"
                                    data-status="<?= htmlspecialchars($req['Status']) ?>"
                                    data-name="<?= htmlspecialchars(strtolower($req['ResidentName'])) ?>"
                                    data-ref="<?= htmlspecialchars(strtolower($req['ReferenceNo'])) ?>">
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y', strtotime($req['CreatedAt'])) ?></td>
                                    <td><span class="badge badge-<?= strtolower($req['Status']) ?>"><?= htmlspecialchars($req['Status']) ?></span></td>
                                    <td>
                                        <a href="request_detail.php?id=<?= $req['RequestID'] ?>"
                                            class="btn btn-small"
                                            style="background:#2e7d32; color:white; text-decoration:none; padding:5px 12px; border-radius:4px;">
                                            Review
                                        </a>
                                        <?php if ($req['Status'] === 'Released' && in_array($req['RequestType'], ['Clearance','Indigency'])): ?>
                                        <a href="../staff/print_document.php?id=<?= $req['RequestID'] ?>"
                                            target="_blank"
                                            class="btn btn-small"
                                            style="background:#1d4ed8; color:white; text-decoration:none; padding:5px 12px; border-radius:4px; margin-left:4px;">
                                            🖨 Print
                                        </a>
                                        <?php endif; ?>
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
                    $base = '?type=' . urlencode($filterType) . '&status=' . urlencode($filterStatus);
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

<script>
    const searchInput = document.getElementById('searchInput');
    const tableRows   = document.querySelectorAll('#requestsTable tbody tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        tableRows.forEach(row => {
            const ref  = row.getAttribute('data-ref')  || '';
            const name = row.getAttribute('data-name') || '';
            row.style.display = (ref.includes(searchTerm) || name.includes(searchTerm)) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('keyup', filterTable);
</script>

<?php include '../includes/footer.php'; ?>
