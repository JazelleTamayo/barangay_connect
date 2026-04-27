<?php
// Barangay Connect – Release Document & Record Payment
// staff/release_document.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../config/settings.php';
require_role('staff');

$pdo     = get_db();
$user_id = $_SESSION['user_id'];

function getExpectedAmount($pdo, $request_id, $request_type) {
    $clearance_fee = get_setting('clearance_fee', 50.00);
    if ($request_type === 'Clearance') return (float)$clearance_fee;
    if ($request_type === 'FacilityReservation') {
        $stmt = $pdo->prepare("
            SELECT f.ReservationFee
            FROM FacilityReservation fr
            JOIN Facility f ON fr.FacilityID = f.FacilityID
            WHERE fr.RequestID = ?
        ");
        $stmt->execute([$request_id]);
        $fee = $stmt->fetchColumn();
        return $fee ? (float)$fee : 0;
    }
    return 0;
}

// --- Handle release + payment ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['release_document'])) {
    $request_id     = intval($_POST['request_id']);
    $amount         = floatval($_POST['amount'] ?? 0);
    $receipt_no     = trim($_POST['receipt_no'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');

    if ($amount <= 0) {
        $error = "Amount must be greater than 0.";
    } elseif (empty($receipt_no)) {
        $error = "Receipt number is required.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE ServiceRequest
                SET Status = 'Released', ReleasedBy = ?, ReleasedAt = NOW()
                WHERE RequestID = ? AND Status = 'Prepared'
            ");
            $stmt->execute([$user_id, $request_id]);
            if ($stmt->rowCount() === 0) throw new Exception("Request not found or not in 'Prepared' status.");

            $stmt = $pdo->prepare("
                INSERT INTO Payment (RequestID, Amount, ReceiptNo, PaymentMethod, RecordedBy, RecordedAt)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$request_id, $amount, $receipt_no, $payment_method, $user_id]);

            $pdo->commit();
            header("Location: release_document.php?msg=released");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// --- Load specific request for release form ---
$release_request = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("
        SELECT sr.*,
               CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
               r.Address, r.ContactNumber
        FROM ServiceRequest sr
        JOIN Resident r ON sr.ResidentID = r.ResidentID
        WHERE sr.RequestID = ? AND sr.Status = 'Prepared'
    ");
    $stmt->execute([$id]);
    $release_request = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($release_request) {
        $release_request['expected_amount'] = getExpectedAmount($pdo, $id, $release_request['RequestType']);
    }
}

// --- List all prepared requests ---
$preparedRequests = $pdo->query("
    SELECT sr.RequestID, sr.ReferenceNo, sr.RequestType, sr.PreparedAt,
           CONCAT(r.FirstName,' ',r.LastName) AS ResidentName
    FROM ServiceRequest sr
    JOIN Resident r ON sr.ResidentID = r.ResidentID
    WHERE sr.Status = 'Prepared'
    ORDER BY sr.PreparedAt ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Release Document & Payment';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Release Document</h1>
            <span class="page-subtitle">Collect payment and release document to resident</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'released'): ?>
                <div class="alert alert-success">✅ Document released and payment recorded successfully.</div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($release_request): ?>

                <!-- Back button -->
                <div class="mb-3">
                    <a href="release_document.php" class="btn btn-secondary">← Back to Prepared List</a>
                </div>

                <!-- Release form card -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3>Release Document &amp; Record Payment</h3>
                            <span class="ref-tag">Ref: <?= htmlspecialchars($release_request['ReferenceNo']) ?></span>
                        </div>
                    </div>

                    <div class="card-body">

                        <!-- Request summary -->
                        <div class="summary-box">
                            <div class="summary-row">
                                <span class="summary-label">Resident</span>
                                <span class="summary-value"><?= htmlspecialchars($release_request['ResidentName']) ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Request Type</span>
                                <span class="summary-value"><?= htmlspecialchars($release_request['RequestType']) ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Expected Amount</span>
                                <span class="summary-value amount-highlight">
                                    ₱<?= number_format($release_request['expected_amount'] ?? 0, 2) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Payment form -->
                        <form method="POST" class="payment-form">
                            <input type="hidden" name="release_document" value="1">
                            <input type="hidden" name="request_id" value="<?= $release_request['RequestID'] ?>">

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Amount Paid <span class="req">*</span></label>
                                    <div class="input-prefix-wrap">
                                        <span class="input-prefix">₱</span>
                                        <input type="number" step="0.01" name="amount"
                                               class="form-input with-prefix" required
                                               value="<?= htmlspecialchars($release_request['expected_amount'] ?? 0) ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Receipt Number <span class="req">*</span></label>
                                    <input type="text" name="receipt_no" class="form-input" required
                                           placeholder="e.g. REC-20260427-001">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-input">
                                        <option value="Cash">Cash</option>
                                        <option value="GCash">GCash</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-confirm">
                                    ✅ Confirm Release &amp; Record Payment
                                </button>
                                <a href="release_document.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>

                    </div><!-- /card-body -->
                </div><!-- /card -->

            <?php else: ?>

                <!-- Prepared list -->
                <div class="card">
                    <div class="card-header">
                        <h3>Documents Ready for Release</h3>
                        <span class="card-subtitle">Click <strong>Release</strong> to collect payment and complete the request.</span>
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
                            <?php if (empty($preparedRequests)): ?>
                                <tr>
                                    <td colspan="5" class="empty-row">No documents ready for release.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($preparedRequests as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['ReferenceNo']) ?></td>
                                        <td><?= htmlspecialchars($doc['ResidentName']) ?></td>
                                        <td><?= htmlspecialchars($doc['RequestType']) ?></td>
                                        <td><?= $doc['PreparedAt'] ? date('M d, Y', strtotime($doc['PreparedAt'])) : '—' ?></td>
                                        <td>
                                            <a href="release_document.php?id=<?= $doc['RequestID'] ?>"
                                               class="btn btn-small btn-primary">Release</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </main>
</div>

<style>
/* Back button spacing */
.mb-3 { margin-bottom: 1rem; }

/* Ref tag under card title */
.ref-tag {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 2px;
    display: block;
}

/* Card body padding */
.card-body { padding: 20px 24px 24px; }

/* Summary box (replaces the info-table) */
.summary-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 4px 0;
    margin-bottom: 24px;
}
.summary-row {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-bottom: 1px solid #e2e8f0;
    gap: 16px;
}
.summary-row:last-child { border-bottom: none; }
.summary-label {
    width: 160px;
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
    flex-shrink: 0;
}
.summary-value {
    font-size: 0.9rem;
    color: #111827;
}
.amount-highlight {
    font-size: 1.05rem;
    font-weight: 700;
    color: #2e7d32;
}

/* Form layout */
.payment-form { margin-top: 4px; }
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    margin-bottom: 8px;
}
@media (max-width: 700px) {
    .form-row { grid-template-columns: 1fr; }
}
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 0.875rem; font-weight: 600; color: #374151; }
.req { color: #dc2626; margin-left: 2px; }

/* Input with peso prefix */
.input-prefix-wrap { position: relative; display: flex; align-items: center; }
.input-prefix {
    position: absolute;
    left: 11px;
    font-size: 0.95rem;
    color: #6b7280;
    pointer-events: none;
}
.form-input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; width: 100%; box-sizing: border-box; }
.form-input.with-prefix { padding-left: 26px; }
.form-input:focus { outline: none; border-color: #2e7d32; box-shadow: 0 0 0 3px #c8e6c9; }

/* Confirm button */
.form-actions { display: flex; gap: 10px; margin-top: 20px; align-items: center; }
.btn-confirm { font-size: 0.95rem; padding: 10px 20px; }

/* Card subtitle */
.card-subtitle { font-size: 0.82rem; color: #6b7280; }
</style>

<?php include '../includes/footer.php'; ?>