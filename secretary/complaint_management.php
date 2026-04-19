<?php
// Barangay Connect – Complaint Management
// secretary/complaint_management.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$page_title = 'Complaint Management';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Complaint Management</h1>
            <span class="page-subtitle">Manage complaints and schedule mediation</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">✅ Complaint updated successfully.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Active Complaints</h3>
                    <div class="card-actions">
                        <input type="text"
                            class="search-input"
                            placeholder="Search by name or reference no..." />
                        <select class="filter-select">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="ForApproval">For Approval</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Complainant</th>
                            <th>Respondent</th>
                            <th>Incident Date</th>
                            <th>Status</th>
                            <th>Mediation Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="empty-row">No complaints found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Schedule Mediation Form -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Schedule Mediation</h3>
                </div>
                <form method="POST"
                    action="../handlers/complaint_update_handler.php"
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
                        <label>Mediation Date *</label>
                        <input type="date"
                            name="mediation_date"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Actions Taken / Notes</label>
                        <textarea name="actions_taken"
                            rows="4"
                            class="form-textarea"
                            placeholder="Describe actions taken or notes for this complaint..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Update Status</label>
                        <select name="status" class="form-select">
                            <option value="">-- No Change --</option>
                            <option value="ForApproval">For Approval</option>
                            <option value="Approved">Resolved</option>
                            <option value="Rejected">Dismissed</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>
<script src="/BARANGAY_CONNECT/assets/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>