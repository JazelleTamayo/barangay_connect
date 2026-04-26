<?php
// Barangay Connect – Request Status Update
// staff/request_status_update.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$pdo = get_db();
$user_id = $_SESSION['user_id'];

// --- Handle the actual update when form is submitted ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Append the staff remark to the existing Remarks field
    $remark_line = "\n[" . date('Y-m-d H:i:s') . "] Staff: " . $remarks;
    
    $stmt = $pdo->prepare("
        UPDATE ServiceRequest 
        SET Status = 'ForApproval',
            ProcessedBy = ?,
            ProcessedAt = NOW(),
            Remarks = CONCAT(IFNULL(Remarks, ''), ?)
        WHERE RequestID = ? AND Status = 'Pending'
    ");
    $stmt->execute([$user_id, $remark_line, $request_id]);
    
    header("Location: request_status_update.php?msg=updated");
    exit;
}

// --- If an ID is passed, show the detailed update form ---
$update_request = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("
        SELECT sr.*, 
               CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
               r.ContactNumber, r.Address
        FROM ServiceRequest sr
        JOIN Resident r ON sr.ResidentID = r.ResidentID
        WHERE sr.RequestID = ? AND sr.Status = 'Pending'
    ");
    $stmt->execute([$id]);
    $update_request = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- List all pending requests for the main table ---
$pending_requests = $pdo->query("
    SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
           sr.Status, sr.CreatedAt,
           CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
    FROM ServiceRequest sr
    JOIN Resident r ON sr.ResidentID = r.ResidentID
    WHERE sr.Status = 'Pending'
    ORDER BY sr.CreatedAt ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Update Request Status';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Update Request Status</h1>
            <span class="page-subtitle">Move requests from Pending to For Approval</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">✅ Request status updated to "For Approval".</div>
            <?php endif; ?>

            <!-- ------------------------------------------------------------- -->
            <!--  If we are editing a specific request, show the confirm form  -->
            <!-- ------------------------------------------------------------- -->
            <?php if ($update_request): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Confirm: Send to Secretary</h3>
                    </div>
                    <form method="POST" class="form-vertical">
                        <input type="hidden" name="request_id" value="<?= $update_request['RequestID'] ?>">
                        
                        <div class="form-group">
                            <label>Reference No.</label>
                            <input type="text" class="form-input" value="<?= htmlspecialchars($update_request['ReferenceNo']) ?>" readonly disabled>
                        </div>
                        <div class="form-group">
                            <label>Resident</label>
                            <input type="text" class="form-input" value="<?= htmlspecialchars($update_request['ResidentName']) ?>" readonly disabled>
                        </div>
                        <div class="form-group">
                            <label>Request Type</label>
                            <input type="text" class="form-input" value="<?= htmlspecialchars($update_request['RequestType']) ?>" readonly disabled>
                        </div>
                        <div class="form-group">
                            <label>Purpose</label>
                            <textarea class="form-textarea" rows="3" readonly disabled><?= htmlspecialchars($update_request['Purpose'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Remarks (optional)</label>
                            <textarea name="remarks" class="form-textarea" rows="3" placeholder="Add any remarks before sending to Secretary..."></textarea>
                            <small class="form-hint">These remarks will be appended to the request log.</small>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Confirm & Send to Secretary</button>
                            <a href="request_status_update.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ------------------------------------------------------------- -->
            <!--  Main table: list all pending requests                         -->
            <!-- ------------------------------------------------------------- -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Pending Requests</h3>
                    <div class="card-actions">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by reference no. or name..." />
                        <select id="typeFilter" class="filter-select">
                            <option value="">All Types</option>
                            <option value="Clearance">Clearance</option>
                            <option value="Indigency">Indigency</option>
                            <option value="FacilityReservation">Facility Reservation</option>
                            <option value="Complaint">Complaint</option>
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_requests)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No pending requests. <a href="../resident/new_request.php">Create a new request</a>.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $req): ?>
                                <tr data-type="<?= $req['RequestType'] ?>" data-name="<?= strtolower($req['ResidentName']) ?>" data-ref="<?= strtolower($req['ReferenceNo']) ?>">
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['CreatedAt'])) ?></td>
                                    <td><span class="badge badge-pending"><?= $req['Status'] ?></span></td>
                                    <td>
                                        <a href="request_status_update.php?id=<?= $req['RequestID'] ?>" class="btn btn-small btn-primary">Update</a>
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
    // Simple client‑side filtering (search + type)
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const tableRows = document.querySelectorAll('#requestsTable tbody tr');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = typeFilter.value;
        tableRows.forEach(row => {
            const ref = row.getAttribute('data-ref') || '';
            const name = row.getAttribute('data-name') || '';
            const type = row.getAttribute('data-type') || '';
            const matchesSearch = ref.includes(searchTerm) || name.includes(searchTerm);
            const matchesType = selectedType === '' || type === selectedType;
            row.style.display = (matchesSearch && matchesType) ? '' : 'none';
        });
    }
    if (searchInput) searchInput.addEventListener('keyup', filterTable);
    if (typeFilter) typeFilter.addEventListener('change', filterTable);
</script>

<?php include '../includes/footer.php'; ?>