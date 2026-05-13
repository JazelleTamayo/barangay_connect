<?php
// Barangay Connect – Complaint Management
// secretary/complaint_management.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

//FIX: Load Active Complaints from DB.
// Joins: Complaint → ServiceRequest → Resident
// Excludes terminal statuses (Rejected, Cancelled, Released).
$pdo = get_db();
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$validStatuses = ['Pending', 'ForApproval', 'Approved', 'Rejected', 'Prepared', 'Released', 'Cancelled'];
if ($status !== '' && !in_array($status, $validStatuses, true)) {
    $status = '';
}

$params = [];
$sql = "SELECT
         sr.ReferenceNo,
         CONCAT(r.FirstName, ' ', r.LastName)  AS ComplainantName,
         c.RespondentName,
         c.IncidentDate,
         sr.Status,
         c.MediationDate,
         sr.RequestID
     FROM Complaint c
     JOIN ServiceRequest sr ON c.RequestID  = sr.RequestID
     JOIN Resident r        ON sr.ResidentID = r.ResidentID
     WHERE sr.RequestType = 'Complaint'
       AND sr.Status NOT IN ('Rejected', 'Cancelled', 'Released')
";

if ($status !== '') {
    $sql .= " AND sr.Status = ?";
    $params[] = $status;
}

if ($search !== '') {
    $sql .= " AND (
        sr.ReferenceNo LIKE ? OR
        CONCAT(r.FirstName, ' ', r.LastName) LIKE ? OR
        c.RespondentName LIKE ?
    )";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY sr.CreatedAt DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);


$page_title = 'Complaint Management';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Complaint Management</h1>
            <span class="page-subtitle">Manage complaints and schedule mediation</span>
        </div>
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
                    <h3>Active Complaints</h3>
                    <form method="GET" class="card-actions">
                        <input type="text"
                            name="search"
                            class="search-input"
                            placeholder="Search by name or reference no..."
                            value="<?= htmlspecialchars($search) ?>" />
                        <select name="status" class="filter-select">
                            <option value="" <?= $status === '' ? 'selected' : '' ?>>All Active Status</option>
                            <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="ForApproval" <?= $status === 'ForApproval' ? 'selected' : '' ?>>For Approval</option>
                            <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Prepared" <?= $status === 'Prepared' ? 'selected' : '' ?>>Prepared</option>
                        </select>
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
.btn-link { color: #2563eb; text-decoration: none; }
</style>
<script src="/barangay_connect/assets/css/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>
