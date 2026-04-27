<?php
// Barangay Connect – Request Processing
// secretary/request_processing.php (show For Approval by default)

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo = get_db();

$filterType   = trim($_GET['type']   ?? '');
$filterStatus = trim($_GET['status'] ?? '');

// Default to 'ForApproval' if no explicit status filter is set
if ($filterStatus === '' && !isset($_GET['status'])) {
    $filterStatus = 'ForApproval';
}

$sql = "SELECT
            sr.RequestID,
            sr.ReferenceNo,
            CONCAT(r.FirstName, ' ', r.LastName) AS ResidentName,
            sr.RequestType,
            sr.CreatedAt,
            sr.Status
        FROM ServiceRequest sr
        JOIN Resident r ON sr.ResidentID = r.ResidentID
        WHERE 1=1";
$params = [];

if ($filterType !== '') {
    $sql .= " AND sr.RequestType = ?";
    $params[] = $filterType;
}
if ($filterStatus !== '' && $filterStatus !== 'all') {
    $sql .= " AND sr.Status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY sr.CreatedAt DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Process Requests';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Process Requests</h1>
            <span class="page-subtitle">Review and approve or reject service requests</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
                <div class="alert alert-success">✅ Request approved successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'rejected'): ?>
                <div class="alert alert-error">❌ Request has been rejected.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>All Service Requests</h3>
                    <div class="card-actions">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by reference no. or name..." />
                        <select id="typeFilter" class="filter-select">
                            <option value="">All Types</option>
                            <option value="Clearance">Clearance</option>
                            <option value="Indigency">Indigency</option>
                            <option value="FacilityReservation">Facility Reservation</option>
                            <option value="Complaint">Complaint</option>
                        </select>
                        <select id="statusFilter" class="filter-select">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="ForApproval" <?= $filterStatus === 'ForApproval' ? 'selected' : '' ?>>For Approval</option>
                            <option value="Approved" <?= $filterStatus === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Rejected" <?= $filterStatus === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="Released" <?= $filterStatus === 'Released' ? 'selected' : '' ?>>Released</option>
                            <option value="Cancelled" <?= $filterStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
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
                            <tr><td colspan="6" class="empty-row">No requests found.</td></tr>
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

<script>
    const searchInput  = document.getElementById('searchInput');
    const typeFilter   = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const tableRows    = document.querySelectorAll('#requestsTable tbody tr');

    function filterTable() {
        const searchTerm     = searchInput.value.toLowerCase();
        const selectedType   = typeFilter.value;
        const selectedStatus = statusFilter.value;

        tableRows.forEach(row => {
            const ref    = row.getAttribute('data-ref')    || '';
            const name   = row.getAttribute('data-name')   || '';
            const type   = row.getAttribute('data-type')   || '';
            const status = row.getAttribute('data-status') || '';

            const matchesSearch = ref.includes(searchTerm) || name.includes(searchTerm);
            const matchesType   = selectedType   === '' || type   === selectedType;
            const matchesStatus = selectedStatus === 'all' || selectedStatus === '' || status === selectedStatus;

            row.style.display = (matchesSearch && matchesType && matchesStatus) ? '' : 'none';
        });
    }

    if (searchInput)  searchInput.addEventListener('keyup',  filterTable);
    if (typeFilter)   typeFilter.addEventListener('change',  filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
</script>

<?php include '../includes/footer.php'; ?>