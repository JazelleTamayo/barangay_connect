<?php
// Barangay Connect – Document Preparation
// staff/document_preparation.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$pdo = get_db();
$user_id = $_SESSION['user_id'];

// --- Handle "Mark as Prepared" action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prepare_document'])) {
    $request_id = intval($_POST['request_id']);
    
    $stmt = $pdo->prepare("
        UPDATE ServiceRequest 
        SET Status = 'Prepared',
            PreparedBy = ?,
            PreparedAt = NOW()
        WHERE RequestID = ? AND Status = 'Approved'
    ");
    $stmt->execute([$user_id, $request_id]);
    
    header("Location: document_preparation.php?msg=prepared");
    exit;
}

// --- If an ID is passed, show the preparation confirmation form ---
$prepare_request = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("
        SELECT sr.*, 
               CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
               r.Address, r.ContactNumber
        FROM ServiceRequest sr
        JOIN Resident r ON sr.ResidentID = r.ResidentID
        WHERE sr.RequestID = ? AND sr.Status = 'Approved'
    ");
    $stmt->execute([$id]);
    $prepare_request = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- List all approved requests ---
$approvedRequests = $pdo->query("
    SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
           sr.ProcessedAt,
           CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
    FROM ServiceRequest sr
    JOIN Resident r ON sr.ResidentID = r.ResidentID
    WHERE sr.Status = 'Approved'
    ORDER BY sr.ProcessedAt ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Document Preparation';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-header">
            <h1>Document Preparation</h1>
            <span class="page-subtitle">Prepare approved documents for release</span>
        </div>

        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'prepared'): ?>
                <div class="alert alert-success">✅ Document marked as ready for pickup.</div>
            <?php endif; ?>

            <?php if ($prepare_request): ?>

                <!-- Back button -->
                <div class="back-bar">
                    <a href="document_preparation.php" class="btn btn-secondary">← Back to List</a>
                </div>

                <!-- Preparation Form -->
                <div class="card">
                    <div class="card-header">
                        <h3>Prepare Document</h3>
                        <p class="card-desc">Review the details below and confirm to mark this document as prepared.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="prepareForm">
                            <input type="hidden" name="prepare_document" value="1">
                            <input type="hidden" name="request_id" value="<?= $prepare_request['RequestID'] ?>">

                            <table class="info-table">
                                <tr>
                                    <th>Reference No.</th>
                                    <td><?= htmlspecialchars($prepare_request['ReferenceNo']) ?></td>
                                </tr>
                                <tr>
                                    <th>Resident</th>
                                    <td><?= htmlspecialchars($prepare_request['ResidentName']) ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td><?= nl2br(htmlspecialchars($prepare_request['Address'] ?? '—')) ?></td>
                                </tr>
                                <tr>
                                    <th>Contact</th>
                                    <td><?= htmlspecialchars($prepare_request['ContactNumber'] ?? '—') ?></td>
                                </tr>
                                <tr>
                                    <th>Request Type</th>
                                    <td><?= htmlspecialchars($prepare_request['RequestType']) ?></td>
                                </tr>
                                <tr>
                                    <th>Purpose</th>
                                    <td><?= nl2br(htmlspecialchars($prepare_request['Purpose'] ?? '—')) ?></td>
                                </tr>
                            </table>

                            <div class="form-actions">
                                <button type="button" class="btn btn-primary" onclick="confirmPrepare()">
                                    ✅ Confirm & Mark as Prepared
                                </button>
                                <a href="document_preparation.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: ?>

                <!-- Approved Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <h3>Approved Requests</h3>
                        <p class="card-desc">Click <strong>Prepare</strong> to mark a document as ready for pickup.</p>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Reference No.</th>
                                    <th>Resident</th>
                                    <th>Type</th>
                                    <th>Approved Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($approvedRequests)): ?>
                                    <tr>
                                        <td colspan="5" class="empty-row">
                                            <div class="empty-state">
                                                <span class="empty-icon">📄</span>
                                                <p>No approved requests waiting for preparation.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($approvedRequests as $doc): ?>
                                        <tr>
                                            <td class="ref-no"><?= htmlspecialchars($doc['ReferenceNo']) ?></td>
                                            <td><?= htmlspecialchars($doc['ResidentName']) ?></td>
                                            <td>
                                                <span class="type-badge"><?= htmlspecialchars($doc['RequestType']) ?></span>
                                            </td>
                                            <td><?= $doc['ProcessedAt'] ? date('M d, Y', strtotime($doc['ProcessedAt'])) : '—' ?></td>
                                            <td>
                                                <a href="document_preparation.php?id=<?= $doc['RequestID'] ?>" class="btn btn-small btn-primary">
                                                    📋 Prepare
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </main>
</div>

<script>
function confirmPrepare() {
    if (confirm('Mark this document as PREPARED and ready for pickup?')) {
        document.getElementById('prepareForm').submit();
    }
}
</script>

<style>
/* Layout */
.back-bar { margin-bottom: 1rem; }
.card-body { padding: 20px; }

/* Info Table (detail view) */
.info-table { width: 100%; border-collapse: collapse; }
.info-table th,
.info-table td {
    padding: 11px 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    vertical-align: top;
    font-size: 0.9rem;
}
.info-table th {
    width: 160px;
    background: #f8fafc;
    color: #374151;
    font-weight: 600;
    white-space: nowrap;
}
.info-table tr:last-child th,
.info-table tr:last-child td { border-bottom: none; }

/* Data Table (list view) */
.table-wrapper { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table thead tr { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
.data-table th { padding: 12px 14px; text-align: left; font-weight: 600; color: #374151; white-space: nowrap; }
.data-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #1f2937; vertical-align: middle; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr:last-child td { border-bottom: none; }

/* Type Badge */
.type-badge {
    display: inline-block;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 500;
    white-space: nowrap;
}

/* Ref No */
.ref-no { font-family: monospace; font-size: 0.85rem; color: #374151; }

/* Empty State */
.empty-row { text-align: center; padding: 40px 0 !important; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 8px; color: #9ca3af; }
.empty-icon { font-size: 2rem; }
.empty-state p { margin: 0; font-size: 0.9rem; }

/* Form Actions */
.form-actions { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; align-items: center; }

/* Card desc */
.card-desc { margin: 4px 0 0; color: #6b7280; font-size: 0.85rem; }

/* Buttons */
.btn { padding: 9px 18px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9rem; font-weight: 500; text-decoration: none; display: inline-block; transition: background 0.15s; }
.btn-small { padding: 6px 14px; font-size: 0.82rem; }
.btn-primary { background: #2563eb; color: white; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #6b7280; color: white; }
.btn-secondary:hover { background: #4b5563; }
</style>

<?php include '../includes/footer.php'; ?>