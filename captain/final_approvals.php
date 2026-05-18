<?php
// captain/final_approvals.php – final version with correct heading and logs

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

$pdo = get_db();

// Pending captain approval (was "Escalated Requests")
$pendingRequests = $pdo->query(
    "SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType,
            sr.CreatedAt, sr.Remarks,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     WHERE sr.Status = 'ForApproval'
     ORDER BY sr.CreatedAt ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Captain approval logs (last 20)
$logs = $pdo->query(
    "SELECT log.*, sr.RequestType 
     FROM captain_approval_logs log
     LEFT JOIN ServiceRequest sr ON log.request_id = sr.RequestID
     ORDER BY log.created_at DESC
     LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Final Approvals';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-body">

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'approved'): ?>
                    <div class="alert alert-success">✅ Request approved successfully.</div>
                <?php elseif ($_GET['msg'] === 'rejected'): ?>
                    <div class="alert alert-error">❌ Request rejected.</div>
                <?php elseif ($_GET['msg'] === 'cancelled'): ?>
                    <div class="alert alert-error">🚫 Request cancelled.</div>
                <?php elseif ($_GET['msg'] === 'log_missing'): ?>
                    <div class="alert alert-warning">⚠️ Log table missing – run the SQL script.</div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- PENDING CAPTAIN APPROVAL (renamed) -->
            <div class="card">
                <div class="card-header">
                    <h3>📋 Pending Captain Approval</h3>
                    <p class="card-desc">Requests that need your decision. Click <strong>Review</strong>.</p>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Staff Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendingRequests)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No pending requests. 👍
            </div>
            </tr>
            <?php else: foreach ($pendingRequests as $req): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($req['ReferenceNo']) ?></strong>
        </div>
        <td><?= htmlspecialchars($req['ResidentName']) ?>
</div>
<td><?= htmlspecialchars($req['RequestType']) ?></div>
<td><?= date('M d, Y h:i A', strtotime($req['CreatedAt'])) ?></div>
<td class="text-muted"><?php $raw = trim($req['Remarks'] ?? '');
                                $cleaned = trim(preg_replace('/\n?\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] Staff:\s*/i', "\n", $raw));
                                echo htmlspecialchars($cleaned ?: '—'); ?></td>
<td>
    <a href="request_detail.php?id=<?= $req['RequestID'] ?>" class="btn btn-small btn-primary">Review</a>
    </div>
    </tr>
<?php endforeach;
                        endif; ?>
</tbody>
</table>
</div>

<!-- CAPTAIN APPROVAL LOGS -->
<div class="card mt-4">
    <div class="card-header">
        <h3>📜 Captain Approval Logs</h3>
        <p class="card-desc">Last 20 actions taken by captains</p>
    </div>
    <?php if (empty($logs)): ?>
        <div class="alert alert-info" style="margin: 16px;">📭 No logs yet. Approve a request to see it here.</div>
    <?php else: ?>
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th>Reference No.</th>
                    <th>Type</th>
                    <th>Action</th>
                    <th>Captain</th>
                    <th>Remarks</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($log['reference_no'] ?? 'N/A') ?></strong>
</div>
<td><?= htmlspecialchars($log['RequestType'] ?? 'Deleted') ?></div>
<td>
    <?php
                    $badgeClass = match ($log['action']) {
                        'Approved' => 'badge-success',
                        'Rejected' => 'badge-error',
                        'Cancelled' => 'badge-warning',
                        default => ''
                    };
    ?>
    <span class="badge <?= $badgeClass ?>"><?= $log['action'] ?></span>
    </div>
<td><?= htmlspecialchars($log['captain_name'] ?? 'Unknown') ?></div>
<td><?= htmlspecialchars($log['remarks'] ?? '—') ?></div>
<td><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></div>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

</div>
</main>
</div>

<style>
    .text-muted {
        color: #9ca3af;
        font-size: 0.85rem;
    }

    .card-desc {
        color: #6b7280;
        font-size: 0.82rem;
        margin: 4px 0 0;
    }

    .mt-4 {
        margin-top: 1rem;
    }

    .compact-table td,
    .compact-table th {
        padding: 8px 12px;
    }

    .badge-success {
        background: #d1fae5;
        color: #065f46;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-warning {
        background: #fed7aa;
        color: #92400e;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
<?php include '../includes/footer.php'; ?>