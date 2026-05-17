<?php
// Barangay Connect – Complaint Management
// secretary/complaint_management.php
// FIXED: Added pagination (25 per page).

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo    = get_db();
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$validStatuses = ['Pending', 'ForApproval', 'Approved', 'Rejected', 'Prepared', 'Released', 'Cancelled'];
if ($status !== '' && !in_array($status, $validStatuses, true)) {
    $status = '';
}

$per_page = 25;
$page     = max(1, (int)($_GET['page'] ?? 1));

$params = [];
$where  = [
    "sr.RequestType = 'Complaint'",
    "sr.Status NOT IN ('Rejected', 'Cancelled', 'Released')",
];

if ($status !== '') {
    $where[]  = 'sr.Status = ?';
    $params[] = $status;
}

if ($search !== '') {
    $like     = '%' . $search . '%';
    $where[]  = "(sr.ReferenceNo LIKE ? OR CONCAT(r.FirstName, ' ', r.LastName) LIKE ? OR c.RespondentName LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = implode(' AND ', $where);

$base_sql = "FROM Complaint c
             JOIN ServiceRequest sr ON c.RequestID  = sr.RequestID
             JOIN Resident r        ON sr.ResidentID = r.ResidentID
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
    "SELECT sr.ReferenceNo,
            CONCAT(r.FirstName, ' ', r.LastName) AS ComplainantName,
            c.RespondentName,
            c.IncidentDate,
            sr.Status,
            c.MediationDate,
            sr.RequestID
     $base_sql
     ORDER BY sr.CreatedAt DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$per_page, $offset]));
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Complaint Management';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">✅ Complaint updated successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'missing_fields'): ?>
                <div class="alert alert-danger">Please provide the reference number and mediation date.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'invalid'): ?>
                <div class="alert alert-danger">Invalid complaint update request.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'not_found'): ?>
                <div class="alert alert-danger">Complaint reference number was not found.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Active Complaints
                        <span style="font-size:0.82rem;font-weight:400;color:var(--text-light);margin-left:8px;">
                            (<?= number_format($total_count) ?> found)
                        </span>
                    </h3>
                    <form method="GET" class="card-actions">
                        <input type="text"
                            name="search"
                            class="search-input"
                            placeholder="Search by name or reference no..."
                            value="<?= htmlspecialchars($search) ?>" />
                        <select name="status" class="filter-select">
                            <option value="" <?= $status === '' ? 'selected' : '' ?>>All Active Status</option>
                            <option value="Pending" <?= $status === 'Pending'     ? 'selected' : '' ?>>Pending</option>
                            <option value="ForApproval" <?= $status === 'ForApproval' ? 'selected' : '' ?>>For Approval</option>
                            <option value="Approved" <?= $status === 'Approved'    ? 'selected' : '' ?>>Approved</option>
                            <option value="Prepared" <?= $status === 'Prepared'    ? 'selected' : '' ?>>Prepared</option>
                        </select>
                        <input type="hidden" name="page" value="1">
                        <button type="submit" class="btn btn-primary btn-small">Filter</button>
                        <a href="complaint_management.php" class="btn-link">Clear</a>
                    </form>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Complainant</th>
                            <th>Respondent</th>
                            <th>Incident Date</th>
                            <th>Status</th>
                            <th>Mediation Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($complaints)): ?>
                            <tr>
                                <td colspan="7" class="empty-row">No active complaints found.</td>
                            </tr>
                            <?php else: foreach ($complaints as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['ReferenceNo'])     ?></td>
                                    <td><?= htmlspecialchars($c['ComplainantName']) ?></td>
                                    <td><?= htmlspecialchars($c['RespondentName'])  ?></td>
                                    <td><?= htmlspecialchars($c['IncidentDate'])    ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($c['Status']) ?>">
                                            <?= htmlspecialchars($c['Status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $c['MediationDate']
                                            ? htmlspecialchars($c['MediationDate'])
                                            : '<em>Not set</em>' ?></td>
                                    <td>
                                        <a href="complaint_detail.php?id=<?= $c['RequestID'] ?>" class="btn btn-sm btn-outline">View</a>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
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

            <!-- Schedule Mediation Form -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Schedule Mediation</h3>
                </div>
                <form method="POST"
                    action="../handlers/complaint_update_handler.php"
                    class="form-vertical validate-form">
                    <div class="form-group">
                        <label>Reference Number *</label>
                        <input type="text"
                            name="reference_no"
                            class="form-input"
                            placeholder="BRGY-YYYYMMDD-XXXXX"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Mediation Date *</label>
                        <input type="date"
                            name="mediation_date"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Actions Taken / Notes</label>
                        <textarea name="actions_taken"
                            rows="4"
                            class="form-textarea"
                            placeholder="Describe actions taken or notes for this complaint..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Update Status</label>
                        <select name="status" class="form-select">
                            <option value="">-- No Change --</option>
                            <option value="ForApproval">For Approval</option>
                            <option value="Approved">Resolved</option>
                            <option value="Rejected">Dismissed</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<style>
    .btn-link {
        color: #2563eb;
        text-decoration: none;
    }

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