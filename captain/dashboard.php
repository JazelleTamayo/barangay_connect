<?php
// Barangay Connect – Captain Dashboard
// captain/dashboard.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('captain');

$page_title = 'Captain Dashboard';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Captain Dashboard</h1>
            <span class="page-subtitle">Welcome, <?= current_user_name() ?></span>
        </div>
        <div class="page-body">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <span class="stat-value" id="stat-residents">—</span>
                        <span class="stat-label">Total Residents</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <span class="stat-value" id="stat-pending">—</span>
                        <span class="stat-label">Pending Requests</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <span class="stat-value" id="stat-complaints">—</span>
                        <span class="stat-label">Open Complaints</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">🏟️</div>
                    <div class="stat-info">
                        <span class="stat-value" id="stat-facilities">—</span>
                        <span class="stat-label">Active Facilities</span>
                    </div>
                </div>
            </div>

            <!-- Escalated Requests -->
            <div class="card">
                <div class="card-header">
                    <h3>Requests Awaiting Final Approval</h3>
                    <a href="final_approvals.php" class="btn btn-primary btn-small">View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="empty-row">No escalated requests at this time.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Staff Performance -->
            <div class="card">
                <div class="card-header">
                    <h3>Staff Performance (This Week)</h3>
                    <a href="reports.php" class="btn btn-secondary btn-small">Full Report</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Requests Processed</th>
                            <th>Avg. Time</th>
                            <th>Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="empty-row">No data yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent System Activity</h3>
                    <a href="audit_log.php" class="btn btn-secondary btn-small">View Full Log</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="empty-row">No recent activity.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>