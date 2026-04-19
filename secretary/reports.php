<?php
// Barangay Connect – Secretary Reports
// secretary/reports.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$page_title = 'Reports';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Reports</h1>
            <span class="page-subtitle">Generate and export operational reports</span>
        </div>
        <div class="page-body">

            <div class="reports-grid">
                <a href="#" class="report-card">
                    <div class="report-icon">📅</div>
                    <h3>Daily Transaction Log</h3>
                    <p>All processed requests for today</p>
                </a>
                <a href="#" class="report-card">
                    <div class="report-icon">📆</div>
                    <h3>Weekly Pending Report</h3>
                    <p>Requests exceeding SLA thresholds</p>
                </a>
                <a href="#" class="report-card">
                    <div class="report-icon">📊</div>
                    <h3>Monthly Summary</h3>
                    <p>Volume and types of services rendered</p>
                </a>
                <a href="#" class="report-card">
                    <div class="report-icon">⚠️</div>
                    <h3>Complaint Summary</h3>
                    <p>Filed, resolved, and average resolution time</p>
                </a>
                <a href="#" class="report-card">
                    <div class="report-icon">🏟️</div>
                    <h3>Facility Utilization</h3>
                    <p>Usage frequency and occupancy rates</p>
                </a>
                <a href="#" class="report-card">
                    <div class="report-icon">👥</div>
                    <h3>Resident Demographics</h3>
                    <p>Age, sex, and purok distribution</p>
                </a>
                <a href="#" class="report-card">
                    <div class="report-icon">🏆</div>
                    <h3>Staff Performance</h3>
                    <p>Productivity and processing times per staff</p>
                </a>
                <a href="#" class="report-card">
                    <div class="report-icon">📋</div>
                    <h3>User Activity Log</h3>
                    <p>System access and modification audit trail</p>
                </a>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>