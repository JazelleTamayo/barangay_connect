<?php
// Barangay Connect – Captain Final Approvals
// captain/final_approvals.php
// FIXED: Added PHP query to load ForApproval requests (previously hardcoded empty table)

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

// FIXED: Load all escalated requests awaiting captain's final approval
$pdo = get_db();
$escalatedRequests = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
            sr.CreatedAt, sr.Remarks,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'ForApproval'
     ORDER BY sr.CreatedAt ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Final Approvals';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Final Approvals</h1>
            <span class="page-subtitle">Escalated requests requiring your approval</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
                <div class="alert alert-success">✅ Request has been approved successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'rejected'): ?>
                <div class="alert alert-error">❌ Request has been rejected.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Escalated Requests</h3>
                    <p class="card-desc">These requests have been flagged for your final approval.</p>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Secretary Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- FIXED: Table now populated from DB query -->
                        <?php if (empty($escalatedRequests)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No escalated requests pending.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($escalatedRequests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($req['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($req['RequestType']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['CreatedAt'])) ?></td>
                                    <td><?= htmlspecialchars($req['Remarks'] ?? '—') ?></td>
                                    <td>
                                        <a href="approval_action.php?id=<?= $req['RequestID'] ?>&action=approve"
                                           class="btn btn-small btn-primary">Approve</a>
                                        <a href="approval_action.php?id=<?= $req['RequestID'] ?>&action=reject"
                                           class="btn btn-small btn-danger">Reject</a>
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