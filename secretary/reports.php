<?php
// Barangay Connect - Secretary Reports
// secretary/reports.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../classes/Report.php';
require_once '../classes/Resident.php';
require_role('secretary');

$report = new Report();
$residentReport = new Resident();

$today = date('Y-m-d');
$year = (int) date('Y');
$month = (int) date('m');

$dailyLog = $report->dailyLog($today);
$weeklyPending = $report->weeklyPending();
$monthlySummary = $report->monthlySummary($year, $month);
$complaintSummary = $report->complaintSummary();
$facilityUtilization = $report->facilityUtilization();
$staffPerformance = $report->staffPerformance();
$demographics = $residentReport->getDemographics();

$page_title = 'Reports';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
                <div class="page-body">

            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-info">
                        <span class="stat-value"><?= count($dailyLog) ?></span>
                        <span class="stat-label">Transactions Today</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-info">
                        <span class="stat-value"><?= count($weeklyPending) ?></span>
                        <span class="stat-label">Past SLA</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-info">
                        <span class="stat-value"><?= (int) ($demographics['total'] ?? 0) ?></span>
                        <span class="stat-label">Active Residents</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Monthly Service Summary</h3>
                    <span class="card-desc"><?= date('F Y') ?></span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Released</th>
                            <th>Rejected</th>
                            <th>Cancelled</th>
                            <th>Avg Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthlySummary)): ?>
                            <tr><td colspan="6" class="empty-row">No requests this month.</td></tr>
                        <?php else: ?>
                            <?php foreach ($monthlySummary as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['RequestType']) ?></td>
                                    <td><?= (int) $row['Total'] ?></td>
                                    <td><?= (int) $row['Released'] ?></td>
                                    <td><?= (int) $row['Rejected'] ?></td>
                                    <td><?= (int) $row['Cancelled'] ?></td>
                                    <td><?= $row['AvgHours'] !== null ? number_format((float) $row['AvgHours'], 1) : '0.0' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3>Pending Requests Past SLA</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Hours Elapsed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($weeklyPending)): ?>
                            <tr><td colspan="5" class="empty-row">No requests are past SLA.</td></tr>
                        <?php else: ?>
                            <?php foreach ($weeklyPending as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['ReferenceNo']) ?></td>
                                    <td><?= htmlspecialchars($row['ResidentName']) ?></td>
                                    <td><?= htmlspecialchars($row['RequestType']) ?></td>
                                    <td><?= htmlspecialchars($row['Status']) ?></td>
                                    <td><?= (int) $row['HoursElapsed'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-grid mt-4">
                <div class="card">
                    <div class="card-header"><h3>Complaint Summary</h3></div>
                    <table class="data-table compact-table">
                        <thead><tr><th>Status</th><th>Count</th><th>Avg Days</th></tr></thead>
                        <tbody>
                            <?php if (empty($complaintSummary)): ?>
                                <tr><td colspan="3" class="empty-row">No complaints found.</td></tr>
                            <?php else: foreach ($complaintSummary as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Status']) ?></td>
                                    <td><?= (int) $row['Count'] ?></td>
                                    <td><?= $row['AvgDays'] !== null ? number_format((float) $row['AvgDays'], 1) : '0.0' ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Facility Utilization</h3></div>
                    <table class="data-table compact-table">
                        <thead><tr><th>Facility</th><th>Total</th><th>Approved</th></tr></thead>
                        <tbody>
                            <?php if (empty($facilityUtilization)): ?>
                                <tr><td colspan="3" class="empty-row">No facility data found.</td></tr>
                            <?php else: foreach ($facilityUtilization as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['FacilityName']) ?></td>
                                    <td><?= (int) $row['TotalReservations'] ?></td>
                                    <td><?= (int) $row['ApprovedReservations'] ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="report-grid mt-4">
                <div class="card">
                    <div class="card-header"><h3>Resident Demographics by Sex</h3></div>
                    <table class="data-table compact-table">
                        <thead><tr><th>Sex</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($demographics['by_sex'] ?? [] as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Sex'] ?? 'Not set') ?></td>
                                    <td><?= (int) $row['count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Staff Performance</h3></div>
                    <table class="data-table compact-table">
                        <thead><tr><th>Name</th><th>Role</th><th>Processed</th><th>Avg Hours</th></tr></thead>
                        <tbody>
                            <?php if (empty($staffPerformance)): ?>
                                <tr><td colspan="4" class="empty-row">No staff activity found.</td></tr>
                            <?php else: foreach ($staffPerformance as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['FullName'] ?? 'Unnamed') ?></td>
                                    <td><?= htmlspecialchars($row['Role']) ?></td>
                                    <td><?= (int) $row['TotalProcessed'] ?></td>
                                    <td><?= $row['AvgHours'] !== null ? number_format((float) $row['AvgHours'], 1) : '0.0' ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>
<style>
.card-desc { color: #6b7280; font-size: 0.85rem; }
.report-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.compact-table { font-size: 0.88rem; }
.mt-4 { margin-top: 1rem; }
@media (max-width: 900px) {
    .report-grid { grid-template-columns: 1fr; }
}
</style>
<?php include '../includes/footer.php'; ?>
