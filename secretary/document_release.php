<?php
// Barangay Connect - Document Release
// secretary/document_release.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../config/settings.php';
require_role('secretary');

$pdo     = get_db();
$user_id = (int) $_SESSION['user_id'];
$error   = '';

function secretary_expected_amount(PDO $pdo, int $request_id, string $request_type): float
{
    if ($request_type === REQ_CLEARANCE) {
        return (float) get_setting('clearance_fee', 50.00);
    }

    if ($request_type === REQ_RESERVATION) {
        $stmt = $pdo->prepare("
            SELECT f.ReservationFee
            FROM FacilityReservation fr
            JOIN Facility f ON fr.FacilityID = f.FacilityID
            WHERE fr.RequestID = ?
        ");
        $stmt->execute([$request_id]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    return 0.00;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['release_document'])) {
    $request_id     = (int) ($_POST['request_id'] ?? 0);
    $amount         = (float) ($_POST['amount'] ?? 0);
    $receipt_no     = trim($_POST['receipt_no'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');
    $remarks        = trim($_POST['remarks'] ?? '');

    if (!$request_id) {
        $error = 'Invalid request selected.';
    } elseif (!in_array($payment_method, ['Cash', 'GCash', 'None'], true)) {
        $error = 'Invalid payment method.';
    } elseif ($payment_method !== 'None' && $amount <= 0) {
        $error = 'Amount paid must be greater than 0.';
    } elseif ($payment_method !== 'None' && $receipt_no === '') {
        $error = 'Receipt number is required.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT RequestID
                FROM ServiceRequest
                WHERE RequestID = ? AND Status = 'Prepared'
                FOR UPDATE
            ");
            $stmt->execute([$request_id]);
            if (!$stmt->fetchColumn()) {
                throw new Exception('Request not found or not ready for release.');
            }

            $stmt = $pdo->prepare("
                UPDATE ServiceRequest
                SET Status = 'Released',
                    ReleasedBy = ?,
                    ReleasedAt = NOW(),
                    Remarks = CASE
                        WHEN ? = '' THEN Remarks
                        ELSE CONCAT(IFNULL(Remarks, ''), ?)
                    END
                WHERE RequestID = ? AND Status = 'Prepared'
            ");
            $release_note = "\n[" . date('Y-m-d H:i:s') . "] Secretary Released: " . $remarks;
            $stmt->execute([$user_id, $remarks, $release_note, $request_id]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Request could not be released.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO Payment (RequestID, ReceiptNo, Amount, PaymentMethod, RecordedBy, RecordedAt)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $request_id,
                $payment_method === 'None' ? 'N/A-' . $request_id : $receipt_no,
                $payment_method === 'None' ? 0.00 : $amount,
                $payment_method,
                $user_id,
            ]);

            $pdo->commit();
            header('Location: document_release.php?msg=released');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

$release_request = null;
if (isset($_GET['id'])) {
    $request_id = (int) $_GET['id'];
    $stmt = $pdo->prepare("
        SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType, sr.Purpose, sr.PreparedAt,
               CONCAT(r.FirstName, ' ', r.LastName) AS ResidentName,
               r.Address, r.ContactNumber
        FROM ServiceRequest sr
        JOIN Resident r ON sr.ResidentID = r.ResidentID
        WHERE sr.RequestID = ? AND sr.Status = 'Prepared'
    ");
    $stmt->execute([$request_id]);
    $release_request = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($release_request) {
        $release_request['ExpectedAmount'] = secretary_expected_amount(
            $pdo,
            (int) $release_request['RequestID'],
            $release_request['RequestType']
        );
    }
}

$readyDocs = $pdo->query("
    SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType, sr.PreparedAt,
           CONCAT(r.FirstName, ' ', r.LastName) AS ResidentName
    FROM ServiceRequest sr
    JOIN Resident r ON sr.ResidentID = r.ResidentID
    WHERE sr.Status = 'Prepared'
    ORDER BY sr.PreparedAt ASC
")->fetchAll(PDO::FETCH_ASSOC);

$releaseHistory = $pdo->query("
    SELECT p.ReceiptNo, p.Amount, p.PaymentMethod, p.RecordedAt,
           sr.ReferenceNo,
           CONCAT(r.FirstName, ' ', r.LastName) AS ResidentName
    FROM Payment p
    JOIN ServiceRequest sr ON p.RequestID = sr.RequestID
    JOIN Resident r ON sr.ResidentID = r.ResidentID
    ORDER BY p.RecordedAt DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Document Release';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
                <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'released'): ?>
                <div class="alert alert-success">Document released and payment recorded.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($release_request): ?>
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3>Release Document</h3>
                            <p class="card-desc">Reference: <?= htmlspecialchars($release_request['ReferenceNo']) ?></p>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="info-table">
                            <tr><th>Resident</th><td><?= htmlspecialchars($release_request['ResidentName']) ?></td></tr>
                            <tr><th>Type</th><td><?= htmlspecialchars($release_request['RequestType']) ?></td></tr>
                            <tr><th>Purpose</th><td><?= nl2br(htmlspecialchars($release_request['Purpose'] ?? '')) ?></td></tr>
                            <tr><th>Prepared Date</th><td><?= $release_request['PreparedAt'] ? date('M d, Y', strtotime($release_request['PreparedAt'])) : 'Not set' ?></td></tr>
                            <tr><th>Expected Amount</th><td>PHP <?= number_format((float) $release_request['ExpectedAmount'], 2) ?></td></tr>
                        </table>

                        <form method="POST" class="form-vertical">
                            <input type="hidden" name="release_document" value="1">
                            <input type="hidden" name="request_id" value="<?= (int) $release_request['RequestID'] ?>">

                            <div class="form-grid mt-4">
                                <div class="form-group">
                                    <label>Amount Paid *</label>
                                    <input type="number" name="amount" class="form-input" step="0.01" min="0"
                                           value="<?= htmlspecialchars((string) $release_request['ExpectedAmount']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Receipt Number *</label>
                                    <input type="text" name="receipt_no" class="form-input" placeholder="Official receipt number">
                                </div>
                                <div class="form-group">
                                    <label>Payment Method</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="Cash">Cash</option>
                                        <option value="GCash">GCash</option>
                                        <option value="None">No Payment</option>
                                    </select>
                                </div>
                                <div class="form-group form-full">
                                    <label>Remarks</label>
                                    <input type="text" name="remarks" class="form-input" placeholder="Optional release remarks">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Confirm Release</button>
                                <a href="document_release.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Prepared Documents Ready for Release</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference No.</th>
                                <th>Resident</th>
                                <th>Type</th>
                                <th>Prepared Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($readyDocs)): ?>
                                <tr>
                                    <td colspan="5" class="empty-row">No documents ready for release.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($readyDocs as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['ReferenceNo']) ?></td>
                                        <td><?= htmlspecialchars($doc['ResidentName']) ?></td>
                                        <td><?= htmlspecialchars($doc['RequestType']) ?></td>
                                        <td><?= $doc['PreparedAt'] ? date('M d, Y', strtotime($doc['PreparedAt'])) : 'Not set' ?></td>
                                        <td>
                                            <a href="document_release.php?id=<?= (int) $doc['RequestID'] ?>"
                                               class="btn btn-small btn-primary">Release</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="card mt-4">
                <div class="card-header">
                    <h3>Recent Release Payments</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Receipt No.</th>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Recorded At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($releaseHistory)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No release payment history yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($releaseHistory as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['ReceiptNo']) ?></td>
                                    <td><?= htmlspecialchars($payment['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($payment['ResidentName']) ?></td>
                                    <td>PHP <?= number_format((float) $payment['Amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($payment['PaymentMethod']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($payment['RecordedAt'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<style>
.card-body { padding: 20px 24px; }
.card-desc { margin: 4px 0 0; color: #6b7280; font-size: 0.85rem; }
.info-table { width: 100%; border-collapse: collapse; }
.info-table th,
.info-table td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
.info-table th { width: 180px; background: #f8fafc; color: #374151; font-weight: 600; }
.info-table tr:last-child th,
.info-table tr:last-child td { border-bottom: none; }
.form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
.form-full { grid-column: 1 / -1; }
.mt-4 { margin-top: 1rem; }
@media (max-width: 800px) {
    .form-grid { grid-template-columns: 1fr; }
}
</style>
<?php include '../includes/footer.php'; ?>
