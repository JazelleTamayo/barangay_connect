<?php
// Barangay Connect – Captain System Override
// captain/system_override.php
// FIXED: Wrong JS path (/assets/css/js/ → /assets/js/)
// FIXED: Override History table was hardcoded empty — now queries AuditLog for CAPTAIN OVERRIDE entries
// FIXED: Added missing_fields and not_found flash messages

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

$pdo = get_db();

// FIXED: Load override history from AuditLog (all actions logged by captain overrides)
$overrideHistory = $pdo->query(
    "SELECT LoggedAt, RecordAffected, Action, Username
     FROM AuditLog
     WHERE Action LIKE 'CAPTAIN OVERRIDE%'
     ORDER BY LoggedAt DESC
     LIMIT 50"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'System Override';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-body">

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'success'): ?>
                    <div class="alert alert-success">✅ Override applied successfully.</div>
                <?php elseif ($_GET['msg'] === 'missing_fields'): ?>
                    <div class="alert alert-error">⚠️ Please fill in all required fields.</div>
                <?php elseif ($_GET['msg'] === 'not_found'): ?>
                    <div class="alert alert-error">⚠️ No request found with that reference number.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Override Request Status</h3>
                    <p class="card-desc text-warning">
                        ⚠️ Use with caution. All overrides are logged and cannot be undone.
                    </p>
                </div>
                <!-- FIXED: form_validation.js path was /barangay_connect/assets/css/js/ (wrong) -->
                <form method="POST"
                      action="../handlers/override_handler.php"
                      class="form-vertical">
                    <div class="form-group">
                        <label>Reference Number *</label>
                        <input type="text"
                               name="reference_no"
                               class="form-input"
                               placeholder="BRGY-YYYYMMDD-XXXXX"
                               required />
                    </div>
                    <div class="form-group">
                        <label>Override to Status *</label>
                        <select name="new_status" class="form-select" required>
                            <option value="">-- Select Status --</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Released">Released</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reason / Remarks *</label>
                        <textarea name="reason"
                                  rows="4"
                                  class="form-textarea"
                                  required
                                  placeholder="State the reason for this override..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger"
                                onclick="return confirm('Apply this override? This action cannot be undone.');">
                            Apply Override
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <!-- FIXED: Override History — now populated from AuditLog (was hardcoded empty) -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Override History</h3>
                    <span class="card-desc">Last 50 overrides applied by the Barangay Captain</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Reference No.</th>
                            <th>Action / New Status</th>
                            <th>Applied By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($overrideHistory)): ?>
                            <tr>
                                <td colspan="4" class="empty-row">No overrides recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($overrideHistory as $oh): ?>
                                <tr>
                                    <td><?= date('M d, Y h:i A', strtotime($oh['LoggedAt'])) ?></td>
                                    <td><?= htmlspecialchars($oh['RecordAffected'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($oh['Action']) ?></td>
                                    <td><?= htmlspecialchars($oh['Username']) ?></td>
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
.mt-4 { margin-top: 1rem; }
</style>
<!-- FIXED: was /barangay_connect/assets/css/js/form_validation.js (wrong path) -->
<script src="../assets/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>