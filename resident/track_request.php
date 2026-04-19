<?php
// Barangay Connect – Track Request
// resident/track_request.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$page_title = 'Track Request';
$ref = trim($_GET['ref'] ?? '');
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Track Request</h1>
            <span class="page-subtitle">Track your request using a reference number</span>
        </div>
        <div class="page-body">

            <!-- Search by Reference -->
            <div class="card">
                <div class="card-header">
                    <h3>Search by Reference Number</h3>
                </div>
                <form method="GET" class="form-inline">
                    <input type="text"
                        name="ref"
                        class="form-input"
                        placeholder="e.g. BRGY-20250101-00001"
                        value="<?= htmlspecialchars($ref) ?>"
                        style="max-width: 340px;" />
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <!-- Search Result -->
            <?php if (!empty($ref)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Result for: <?= htmlspecialchars($ref) ?></h3>
                    </div>
                    <div class="track-result">
                        <p class="empty-row">
                            No request found with that reference number.
                            Please check and try again.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- All My Requests -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>All My Requests</h3>
                    <div class="card-actions">
                        <select class="filter-select">
                            <option value="">All Types</option>
                            <option value="Clearance">Clearance</option>
                            <option value="Indigency">Indigency</option>
                            <option value="FacilityReservation">Facility Reservation</option>
                            <option value="Complaint">Complaint</option>
                        </select>
                        <select class="filter-select">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="ForApproval">For Approval</option>
                            <option value="Approved">Approved</option>
                            <option value="Released">Released</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="empty-row">No requests found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>