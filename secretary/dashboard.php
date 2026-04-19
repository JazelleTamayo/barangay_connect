<?php
// Barangay Connect – Secretary Dashboard
// secretary/dashboard.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$page_title = 'Secretary Dashboard';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Secretary Dashboard</h1>
            <span class="page-subtitle">Welcome, <?= current_user_name() ?></span>
        </div>
        <div class="page-body">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Pending Verification</span>
                    </div>
                </div>
                <div class="stat-card stat-blue">
                    <div class="stat-icon">📥</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">For Approval</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">📬</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Ready for Release</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Open Complaints</span>
                    </div>
                </div>
            </div>

            <!-- Requests For Approval -->
            <div class="card">
                <div class="card-header">
                    <h3>Requests For Approval</h3>
                    <a href="request_processing.php" class="btn btn-primary btn-small">View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="empty-row">No requests waiting for approval.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pending Verifications -->
            <div class="card">
                <div class="card-header">
                    <h3>Pending Resident Verifications</h3>
                    <a href="resident_verification.php" class="btn btn-secondary btn-small">View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Submitted</th>
                            <th>ID Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="empty-row">No pending verifications.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Documents Ready for Release -->
            <div class="card">
                <div class="card-header">
                    <h3>Documents Ready for Release Today</h3>
                    <a href="document_release.php" class="btn btn-secondary btn-small">View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Approved Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="empty-row">No documents ready for release.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>