<?php
// Barangay Connect – Resident Dashboard
// resident/dashboard.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$page_title = 'My Dashboard';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>My Dashboard</h1>
            <span class="page-subtitle">Welcome, <?= current_user_name() ?></span>
        </div>
        <div class="page-body">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Total Requests</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Pending</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Released</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <span class="stat-value">—</span>
                        <span class="stat-label">Complaints Filed</span>
                    </div>
                </div>
            </div>

            <!-- Recent Requests -->
            <div class="card">
                <div class="card-header">
                    <h3>My Recent Requests</h3>
                    <a href="new_request.php" class="btn btn-primary btn-small">
                        + New Request
                    </a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="empty-row">
                                You have no requests yet.
                                <a href="new_request.php">Create one now.</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- My Complaints -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>My Complaints</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Respondent</th>
                            <th>Incident Date</th>
                            <th>Status</th>
                            <th>Mediation Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="empty-row">No complaints filed.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- My Reservations -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>My Facility Reservations</h3>
                    <a href="facility_schedule.php" class="btn btn-secondary btn-small">
                        View Schedule
                    </a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="empty-row">No reservations yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>