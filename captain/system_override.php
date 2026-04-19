<?php
// Barangay Connect – Captain System Override
// captain/system_override.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

$page_title = 'System Override';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>System Override</h1>
            <span class="page-subtitle">Override any request status with documented reason</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
                <div class="alert alert-success">✅ Override applied successfully.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Override Request Status</h3>
                    <p class="card-desc text-warning">
                        ⚠️ Use with caution. All overrides are logged and cannot be undone.
                    </p>
                </div>
                <form method="POST"
                    action="../handlers/override_handler.php"
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
                        <button type="submit" class="btn btn-danger">Apply Override</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <!-- Override History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Override History</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Reference No.</th>
                            <th>Overridden To</th>
                            <th>Reason</th>
                            <th>Applied By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="empty-row">No overrides recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<script src="/BARANGAY_CONNECT/assets/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>