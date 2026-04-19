<?php
// Barangay Connect – Staff Dashboard
// staff/dashboard.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$page_title = 'Staff Dashboard';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Staff Dashboard</h1>
            <span class="page-subtitle">Welcome, <?= current_user_name() ?></span>
        </div>
        <div class="page-body">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">📥</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Pending Requests</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Docs to Prepare</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Processed Today</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Overdue Requests</span>
                    </div>
                </div>
            </div>

            <!-- Assigned Requests -->
            <div class="card">
                <div class="card-header">
                    <h3>My Assigned Requests</h3>
                    <a href="request_status_update.php"
                        class="btn btn-primary btn-small">View All</a>
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
                            <td colspan="6" class="empty-row">No assigned requests.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Documents to Prepare -->
            <div class="card">
                <div class="card-header">
                    <h3>Documents to Prepare</h3>
                    <a href="document_preparation.php"
                        class="btn btn-secondary btn-small">View All</a>
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
                            <td colspan="5" class="empty-row">No documents to prepare.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>