<?php
// Barangay Connect – Captain Final Approvals
// captain/final_approvals.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

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
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
                <div class="alert alert-success">✅ Request has been approved successfully.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'rejected'): ?>
                <div class="alert alert-error">❌ Request has been rejected.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'not_found'): ?>
                <div class="alert alert-error">❌ Request not found.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'invalid'): ?>
                <div class="alert alert-error">❌ Invalid action or request is no longer pending.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Escalated Requests</h3>
                    <p class="card-desc">These requests have been forwarded for your final approval.</p>
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
                                        <button type="button"
                                            class="btn btn-small btn-primary"
                                            onclick="openModal(<?= $req['RequestID'] ?>, '<?= htmlspecialchars($req['ReferenceNo']) ?>', 'approve')">
                                            Approve
                                        </button>
                                        <button type="button"
                                            class="btn btn-small btn-danger"
                                            onclick="openModal(<?= $req['RequestID'] ?>, '<?= htmlspecialchars($req['ReferenceNo']) ?>', 'reject')">
                                            Reject
                                        </button>
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

<!-- Approval/Rejection Modal -->
<div id="approvalModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header" id="modalHeader">
            <h3 id="modalTitle">Confirm Action</h3>
            <button type="button" class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body">
            <p id="modalDesc" style="margin-bottom:12px; color:#4b5563;"></p>
            <form method="POST" action="../handlers/approval_action_handler.php">
                <input type="hidden" name="request_id" id="modalRequestId" />
                <input type="hidden" name="action"     id="modalAction" />
                <div class="form-group">
                    <label for="modalRemarks" id="modalRemarksLabel">Remarks <span style="color:#e53e3e;">*</span></label>
                    <textarea name="remarks"
                        id="modalRemarks"
                        rows="4"
                        class="form-textarea"
                        placeholder="State your reason..."
                        required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Confirm</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-box {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    overflow: hidden;
}
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
}
.modal-header h3 { margin: 0; font-size: 1.05rem; }
.modal-close {
    background: none; border: none;
    font-size: 1.1rem; cursor: pointer; color: #9ca3af;
}
.modal-close:hover { color: #374151; }
.modal-body { padding: 20px 24px 24px; }
.modal-header.approve-header { background: #f0fdf4; }
.modal-header.reject-header  { background: #fff1f2; }
</style>

<script>
function openModal(id, refNo, action) {
    document.getElementById('modalRequestId').value = id;
    document.getElementById('modalAction').value    = action;
    document.getElementById('modalRemarks').value   = '';

    const header = document.getElementById('modalHeader');
    const title  = document.getElementById('modalTitle');
    const desc   = document.getElementById('modalDesc');
    const btn    = document.getElementById('modalSubmitBtn');

    if (action === 'approve') {
        header.className = 'modal-header approve-header';
        title.textContent = '✅ Approve Request';
        desc.textContent  = 'You are about to approve ' + refNo + '. Please provide your remarks.';
        btn.className     = 'btn btn-primary';
        btn.textContent   = 'Approve';
    } else {
        header.className = 'modal-header reject-header';
        title.textContent = '❌ Reject Request';
        desc.textContent  = 'You are about to reject ' + refNo + '. Please state the reason for rejection.';
        btn.className     = 'btn btn-danger';
        btn.textContent   = 'Reject';
    }

    document.getElementById('approvalModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('approvalModal').style.display = 'none';
}

// Close on backdrop click
document.getElementById('approvalModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '../includes/footer.php'; ?>
