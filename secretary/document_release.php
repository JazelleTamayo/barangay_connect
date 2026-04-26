<?php
// Barangay Connect – Document Release
// secretary/document_release.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$page_title = 'Document Release';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Document Release</h1>
            <span class="page-subtitle">Release approved documents and record payments</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'released'): ?>
                <div class="alert alert-success">✅ Document released and payment recorded.</div>
            <?php endif; ?>

            <!-- Ready for Release -->
            <div class="card">
                <div class="card-header">
                    <h3>Approved — Ready for Release</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Approved Date</th>
                            <th>Fee</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="empty-row">No documents ready for release.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Record Release Form -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Record Release &amp; Payment</h3>
                    <p class="card-desc">Fill in the details after the resident claims the document in person.</p>
                </div>
                <form method="POST"
                    action="../handlers/request_release_handler.php"
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
                        <label>Amount Paid (₱)</label>
                        <input type="number"
                            name="amount"
                            class="form-input"
                            step="0.01"
                            min="0"
                            placeholder="0.00" />
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="None">No Payment (Indigency)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <input type="text"
                            name="remarks"
                            class="form-input"
                            placeholder="Optional remarks..." />
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Mark as Released &amp; Record Payment
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <!-- Release History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Release History</h3>
                    <div class="card-actions">
                        <input type="date" class="date-input" />
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Receipt No.</th>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Released At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="empty-row">No release history yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<script src="/barangay_connect/assets/css/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>