<?php
// Barangay Connect – Resident Verification
// secretary/resident_verification.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../classes/UserAccount.php';
require_role('secretary');

$page_title = 'Resident Verification';
$history_status = trim($_GET['history_status'] ?? '');
if (!in_array($history_status, ['', 'Active', 'Rejected'], true)) {
    $history_status = '';
}

// Fetch pending registrations from DB
$pdo     = get_db();
$pending = $pdo->query("
    SELECT ua.UserAccountID, ua.Username, ua.CreatedAt,
           r.FirstName, r.LastName, r.Birthdate,
           r.Address, r.ContactNumber, r.GovIDImagePath,
           r.GovIDType
    FROM UserAccount ua
    JOIN Resident r ON ua.ResidentID = r.ResidentID
    WHERE ua.AccountStatus = 'PendingVerification'
    ORDER BY ua.CreatedAt ASC
")->fetchAll();

// Fetch recently verified
$verifiedSql = "
    SELECT ua.UserAccountID, ua.AccountStatus,
           ua.VerifiedAt, ua.RejectionReason,
           r.FirstName, r.LastName,
           COALESCE(
               CONCAT(sp.FirstName, ' ', sp.LastName),
               CONCAT(stf.FirstName, ' ', stf.LastName),
               CONCAT(cp.FirstName, ' ', cp.LastName),
               '—'
           ) AS VerifiedByName
    FROM UserAccount ua
    JOIN Resident r ON ua.ResidentID = r.ResidentID
    LEFT JOIN UserAccount verifier ON ua.VerifiedBy = verifier.UserAccountID
    LEFT JOIN SecretaryProfile sp  ON verifier.UserAccountID = sp.UserID
    LEFT JOIN StaffProfile     stf ON verifier.UserAccountID = stf.UserID
    LEFT JOIN CaptainProfile   cp  ON verifier.UserAccountID = cp.UserID
    WHERE ua.AccountStatus IN ('Active','Rejected')
      AND ua.Role = 'resident'
      AND ua.VerifiedAt IS NOT NULL
";
$verifiedParams = [];
if ($history_status !== '') {
    $verifiedSql .= " AND ua.AccountStatus = ?";
    $verifiedParams[] = $history_status;
}
$verifiedSql .= " ORDER BY ua.VerifiedAt DESC LIMIT 20";
$verifiedStmt = $pdo->prepare($verifiedSql);
$verifiedStmt->execute($verifiedParams);
$verified = $verifiedStmt->fetchAll();

include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
                <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
                <div class="alert alert-success">
                    ✅ Resident account has been approved.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'rejected'): ?>
                <div class="alert alert-error">
                    ❌ Resident account has been rejected.
                </div>
            <?php endif; ?>

            <!-- Pending -->
            <div class="card">
                <div class="card-header">
                    <h3>Pending Self-Registrations
                        <span class="status-badge status-pending">
                            <?= count($pending) ?>
                        </span>
                    </h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Birthdate</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Submitted</th>
                            <th>ID Image</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending)): ?>
                            <tr>
                                <td colspan="7" class="empty-row">
                                    No pending verifications.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending as $p): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(
                                                $p['FirstName'] . ' ' . $p['LastName']
                                            ) ?>
                                        </strong><br>
                                        <small style="color:var(--text-light)">
                                            @<?= htmlspecialchars($p['Username']) ?>
                                        </small>
                                    </span>
                                    <td><?= htmlspecialchars($p['Birthdate']) ?></span>
                                    <td><?= htmlspecialchars($p['Address']) ?></span>
                                    <td><?= htmlspecialchars($p['ContactNumber'] ?? '—') ?></span>
                                    <td><?= date('M d, Y', strtotime($p['CreatedAt'])) ?></span>
                                    <td>
                                        <?php if ($p['GovIDImagePath']): ?>
                                            <a href="/BARANGAY_CONNECT/<?= htmlspecialchars(
                                                                            $p['GovIDImagePath']
                                                                        ) ?>"
                                                target="_blank"
                                                class="btn btn-secondary btn-small">
                                                View ID
                                            </a>
                                        <?php else: ?>
                                            <span style="color:var(--text-light)">
                                                No image
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <td>
                                        <div style="display:flex; gap:6px;">
                                            <!-- Approve -->
                                            <form method="POST"
                                                action="../handlers/resident_verify_handler.php">
                                                <input type="hidden"
                                                    name="account_id"
                                                    value="<?= $p['UserAccountID'] ?>" />
                                                <input type="hidden"
                                                    name="action"
                                                    value="approve" />
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_generate()) ?>">
                                                <button type="submit"
                                                    class="btn btn-primary btn-small">
                                                    ✅ Approve
                                                </button>
                                            </form>
                                            <!-- Reject -->
                                            <form method="POST"
                                                action="../handlers/resident_verify_handler.php"
                                                onsubmit="return confirmReject(this)">
                                                <input type="hidden"
                                                    name="account_id"
                                                    value="<?= $p['UserAccountID'] ?>" />
                                                <input type="hidden"
                                                    name="action"
                                                    value="reject" />
                                                <input type="hidden"
                                                    name="reason"
                                                    class="reject-reason" />
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_generate()) ?>">
                                                <button type="submit"
                                                    class="btn btn-danger btn-small">
                                                    ❌ Reject
                                                </button>
                                            </form>
                                        </div>
                                    </span>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Verification History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Recently Verified</h3>
                    <form method="GET" class="card-actions" id="historyFilterForm">
                        <select name="history_status" class="filter-select" onchange="document.getElementById('historyFilterForm').submit()">
                            <option value="" <?= $history_status === '' ? 'selected' : '' ?>>All</option>
                            <option value="Active" <?= $history_status === 'Active' ? 'selected' : '' ?>>Approved</option>
                            <option value="Rejected" <?= $history_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </form>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Verified By</th>
                            <th>Verified At</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($verified)): ?>
                            <tr>
                                <td colspan="5" class="empty-row">
                                    No verification history yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($verified as $v): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars(
                                            $v['FirstName'] . ' ' . $v['LastName']
                                        ) ?>
                                    </span>
                                    <td>
                                        <span class="status-badge
                                    <?= $v['AccountStatus'] === 'Active'
                                        ? 'status-approved'
                                        : 'status-rejected' ?>">
                                            <?= $v['AccountStatus'] === 'Active'
                                                ? 'Approved' : 'Rejected' ?>
                                        </span>
                                    </span>
                                    <td>
                                        <?= htmlspecialchars(
                                            $v['VerifiedByName'] ?? '—'
                                        ) ?>
                                    </span>
                                    <td>
                                        <?= $v['VerifiedAt']
                                            ? date(
                                                'M d, Y h:i A',
                                                strtotime($v['VerifiedAt'])
                                            )
                                            : '—' ?>
                                    </span>
                                    <td>
                                        <?= htmlspecialchars(
                                            $v['RejectionReason'] ?? '—'
                                        ) ?>
                                    </span>
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
    function confirmReject(form) {
        const reason = prompt(
            'Please enter the reason for rejection:'
        );
        if (!reason || reason.trim() === '') {
            alert('A reason is required to reject an account.');
            return false;
        }
        form.querySelector('.reject-reason').value = reason;
        return true;
    }
</script>

<?php include '../includes/footer.php'; ?>