<?php
// Barangay Connect - Secretary Reports (Analytics Edition)
// secretary/reports.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../classes/Report.php';
require_once '../classes/Resident.php';
require_role('secretary');

$report         = new Report();
$residentReport = new Resident();
$pdo            = get_db(); // ADDED

$today = date('Y-m-d');
$year  = (int) date('Y');
$month = (int) date('m');

$dailyLog            = $report->dailyLog($today);
$weeklyPending       = $report->weeklyPending();
$monthlySummary      = $report->monthlySummary($year, $month);
$complaintSummary    = $report->complaintSummary();
$facilityUtilization = $report->facilityUtilization();
$staffPerformance    = $report->staffPerformance();
$demographics        = $residentReport->getDemographics();

// ADDED: Payment collection summary
$paymentStmt = $pdo->prepare(
    "SELECT sr.RequestType,
            COUNT(p.PaymentID)   AS TotalReleased,
            SUM(p.Amount)        AS TotalCollected,
            SUM(CASE WHEN p.PaymentMethod = 'Cash'  THEN p.Amount ELSE 0 END) AS CashTotal,
            SUM(CASE WHEN p.PaymentMethod = 'GCash' THEN p.Amount ELSE 0 END) AS GCashTotal
     FROM Payment p
     JOIN ServiceRequest sr ON p.RequestID = sr.RequestID
     WHERE YEAR(p.RecordedAt)  = ?
       AND MONTH(p.RecordedAt) = ?
     GROUP BY sr.RequestType
     ORDER BY TotalCollected DESC"
);
$paymentStmt->execute([$year, $month]);
$paymentSummary = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
$totalCollectedMonth = array_sum(array_column($paymentSummary, 'TotalCollected'));

// ADDED: Completed transactions (Released)
$completedStmt = $pdo->prepare(
    "SELECT sr.ReferenceNo, sr.RequestType,
            CONCAT(r.FirstName,' ',r.LastName) AS ResidentName,
            sr.ReleasedAt,
            IFNULL(p.Amount, 0)                AS Amount,
            IFNULL(p.PaymentMethod, 'N/A')     AS PaymentMethod,
            IFNULL(p.ReceiptNo, '—')           AS ReceiptNo,
            ua.FullName                        AS ReleasedBy
     FROM ServiceRequest sr
     JOIN Resident r ON sr.ResidentID = r.ResidentID
     LEFT JOIN Payment p ON sr.RequestID = p.RequestID
     LEFT JOIN UserAccount ua ON sr.ReleasedBy = ua.UserAccountID
     WHERE sr.Status = 'Released'
     ORDER BY sr.ReleasedAt DESC
     LIMIT 50"
);
$completedStmt->execute();
$completedTransactions = $completedStmt->fetchAll(PDO::FETCH_ASSOC);

// ── CSV Export ────────────────────────────────────────────────────────────────
if (isset($_GET['export'])) {
    $export  = $_GET['export'];
    $allowed = ['monthly', 'payments', 'complaints', 'facility', 'staff', 'pending', 'demographics', 'completed'];
    if (!in_array($export, $allowed, true)) {
        http_response_code(400); exit('Invalid export type.');
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="barangay_' . $export . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');

    switch ($export) {
        case 'monthly':
            fputcsv($out, ['Type','Total','Released','Rejected','Cancelled','Avg Hours']);
            foreach ($monthlySummary as $r) {
                fputcsv($out, [
                    $r['RequestType'], $r['Total'], $r['Released'],
                    $r['Rejected'], $r['Cancelled'],
                    $r['AvgHours'] !== null ? number_format((float)$r['AvgHours'], 1) : '0.0'
                ]);
            }
            break;
        case 'payments':
            fputcsv($out, ['Type', 'Released', 'Total Collected (PHP)', 'Cash', 'GCash']);
            foreach ($paymentSummary as $r) {
                fputcsv($out, [
                    $r['RequestType'], $r['TotalReleased'],
                    number_format((float)$r['TotalCollected'], 2),
                    number_format((float)$r['CashTotal'], 2),
                    number_format((float)$r['GCashTotal'], 2)
                ]);
            }
            break;
        case 'completed':
            fputcsv($out, ['Reference No.','Resident','Type','Released At','Amount','Payment','Receipt No.','Released By']);
            foreach ($completedTransactions as $r) {
                fputcsv($out, [
                    $r['ReferenceNo'], $r['ResidentName'], $r['RequestType'],
                    $r['ReleasedAt'] ? date('M d, Y h:i A', strtotime($r['ReleasedAt'])) : '—',
                    number_format((float)$r['Amount'], 2),
                    $r['PaymentMethod'], $r['ReceiptNo'], $r['ReleasedBy'] ?? '—'
                ]);
            }
            break;
        case 'complaints':
            fputcsv($out, ['Status','Count','Avg Days']);
            foreach ($complaintSummary as $r) {
                fputcsv($out, [$r['Status'], $r['Count'],
                    $r['AvgDays'] !== null ? number_format((float)$r['AvgDays'], 1) : '0.0']);
            }
            break;
        case 'facility':
            fputcsv($out, ['Facility','Total Reservations','Approved Reservations']);
            foreach ($facilityUtilization as $r) {
                fputcsv($out, [$r['FacilityName'], $r['TotalReservations'], $r['ApprovedReservations']]);
            }
            break;
        case 'staff':
            fputcsv($out, ['Name','Role','Processed','Avg Hours']);
            foreach ($staffPerformance as $r) {
                fputcsv($out, [
                    $r['FullName'] ?? 'Unnamed', $r['Role'], $r['TotalProcessed'],
                    $r['AvgHours'] !== null ? number_format((float)$r['AvgHours'], 1) : '0.0'
                ]);
            }
            break;
        case 'pending':
            fputcsv($out, ['Reference No.','Resident','Type','Status','Hours Elapsed']);
            foreach ($weeklyPending as $r) {
                fputcsv($out, [
                    $r['ReferenceNo'], $r['ResidentName'],
                    $r['RequestType'], $r['Status'], (int)$r['HoursElapsed']
                ]);
            }
            break;
        case 'demographics':
            fputcsv($out, ['Sex','Count']);
            foreach ($demographics['by_sex'] ?? [] as $r) {
                fputcsv($out, [$r['Sex'] ?? 'Not set', (int)$r['count']]);
            }
            break;
    }
    fclose($out);
    exit;
}

// ── Chart data JSON ───────────────────────────────────────────────────────────
$chartMonthly    = json_encode(array_values($monthlySummary));
$chartComplaints = json_encode(array_values($complaintSummary));
$chartFacility   = json_encode(array_values($facilityUtilization));
$chartStaff      = json_encode(array_values($staffPerformance));
$chartDemog      = json_encode(array_values($demographics['by_sex'] ?? []));
$chartPayments   = json_encode(array_values($paymentSummary));

$page_title = 'Analytics & Reports';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-body">

            <!-- KPI Row -->
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= count($dailyLog) ?></span>
                        <span class="stat-label">Transactions Today</span>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= count($weeklyPending) ?></span>
                        <span class="stat-label">Past SLA</span>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <span class="stat-value"><?= (int)($demographics['total'] ?? 0) ?></span>
                        <span class="stat-label">Active Residents</span>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <span class="stat-value">₱<?= number_format((float)$totalCollectedMonth, 2) ?></span>
                        <span class="stat-label">Collected This Month</span>
                    </div>
                </div>
            </div>

            <!-- Monthly Summary -->
            <div class="card mt-4">
                <div class="card-header">
                    <div>
                        <h3>Monthly Service Summary</h3>
                        <span class="card-desc"><?= date('F Y') ?></span>
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-ghost btn-small" onclick="toggleView('monthly')">
                            <span id="monthly-toggle-label">📊 Chart</span>
                        </button>
                        <a href="?export=monthly" class="btn btn-secondary btn-small" download>⬇ Download CSV</a>
                    </div>
                </div>
                <div id="monthly-chart-view">
                    <div class="chart-wrap"><canvas id="monthlyChart"></canvas></div>
                </div>
                <div id="monthly-table-view" style="display:none;">
                    <table class="data-table">
                        <thead>
                            <tr><th>Type</th><th>Total</th><th>Released</th><th>Rejected</th><th>Cancelled</th><th>Avg Hours</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monthlySummary)): ?>
                                <tr><td colspan="6" class="empty-row">No requests this month.</td></tr>
                            <?php else: foreach ($monthlySummary as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['RequestType']) ?></td>
                                    <td><?= (int)$r['Total'] ?></td>
                                    <td><?= (int)$r['Released'] ?></td>
                                    <td><?= (int)$r['Rejected'] ?></td>
                                    <td><?= (int)$r['Cancelled'] ?></td>
                                    <td><?= $r['AvgHours'] !== null ? number_format((float)$r['AvgHours'], 1) : '0.0' ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Collection Summary -->
            <div class="card mt-4">
                <div class="card-header">
                    <div>
                        <h3>Payment Collection Summary</h3>
                        <span class="card-desc"><?= date('F Y') ?> — Released documents only</span>
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-ghost btn-small" onclick="toggleView('payments')">
                            <span id="payments-toggle-label">📊 Chart</span>
                        </button>
                        <a href="?export=payments" class="btn btn-secondary btn-small" download>⬇ Download CSV</a>
                    </div>
                </div>
                <div id="payments-chart-view">
                    <div class="chart-wrap"><canvas id="paymentsChart"></canvas></div>
                </div>
                <div id="payments-table-view" style="display:none;">
                    <table class="data-table">
                        <thead>
                            <tr><th>Type</th><th>Released</th><th>Total Collected</th><th>Cash</th><th>GCash</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paymentSummary)): ?>
                                <tr><td colspan="5" class="empty-row">No payments recorded this month.</td></tr>
                            <?php else: foreach ($paymentSummary as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['RequestType']) ?></td>
                                    <td><?= (int)$r['TotalReleased'] ?></td>
                                    <td><strong>₱<?= number_format((float)$r['TotalCollected'], 2) ?></strong></td>
                                    <td>₱<?= number_format((float)$r['CashTotal'], 2) ?></td>
                                    <td>₱<?= number_format((float)$r['GCashTotal'], 2) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                        <?php if (!empty($paymentSummary)): ?>
                        <tfoot>
                            <tr class="table-total">
                                <td colspan="2"><strong>Total</strong></td>
                                <td><strong>₱<?= number_format((float)$totalCollectedMonth, 2) ?></strong></td>
                                <td><strong>₱<?= number_format((float)array_sum(array_column($paymentSummary, 'CashTotal')), 2) ?></strong></td>
                                <td><strong>₱<?= number_format((float)array_sum(array_column($paymentSummary, 'GCashTotal')), 2) ?></strong></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Complaints + Facility side-by-side -->
            <div class="report-grid mt-4">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3>Complaint Summary</h3>
                            <span class="card-desc">By status</span>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-ghost btn-small" onclick="toggleView('complaints')">
                                <span id="complaints-toggle-label">📊 Chart</span>
                            </button>
                            <a href="?export=complaints" class="btn btn-secondary btn-small" download>⬇ CSV</a>
                        </div>
                    </div>
                    <div id="complaints-chart-view">
                        <div class="chart-wrap chart-wrap--sm"><canvas id="complaintsChart"></canvas></div>
                    </div>
                    <div id="complaints-table-view" style="display:none;">
                        <table class="data-table compact-table">
                            <thead><tr><th>Status</th><th>Count</th><th>Avg Days</th></tr></thead>
                            <tbody>
                                <?php if (empty($complaintSummary)): ?>
                                    <tr><td colspan="3" class="empty-row">No complaints found.</td></tr>
                                <?php else: foreach ($complaintSummary as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['Status']) ?></td>
                                        <td><?= (int)$r['Count'] ?></td>
                                        <td><?= $r['AvgDays'] !== null ? number_format((float)$r['AvgDays'], 1) : '0.0' ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3>Facility Utilization</h3>
                            <span class="card-desc">Reservations</span>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-ghost btn-small" onclick="toggleView('facility')">
                                <span id="facility-toggle-label">📊 Chart</span>
                            </button>
                            <a href="?export=facility" class="btn btn-secondary btn-small" download>⬇ CSV</a>
                        </div>
                    </div>
                    <div id="facility-chart-view">
                        <div class="chart-wrap chart-wrap--sm"><canvas id="facilityChart"></canvas></div>
                    </div>
                    <div id="facility-table-view" style="display:none;">
                        <table class="data-table compact-table">
                            <thead><tr><th>Facility</th><th>Total</th><th>Approved</th></tr></thead>
                            <tbody>
                                <?php if (empty($facilityUtilization)): ?>
                                    <tr><td colspan="3" class="empty-row">No facility data found.</td></tr>
                                <?php else: foreach ($facilityUtilization as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['FacilityName']) ?></td>
                                        <td><?= (int)$r['TotalReservations'] ?></td>
                                        <td><?= (int)$r['ApprovedReservations'] ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Demographics + Staff side-by-side -->
            <div class="report-grid mt-4">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3>Resident Demographics</h3>
                            <span class="card-desc">By sex</span>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-ghost btn-small" onclick="toggleView('demographics')">
                                <span id="demographics-toggle-label">📊 Chart</span>
                            </button>
                            <a href="?export=demographics" class="btn btn-secondary btn-small" download>⬇ CSV</a>
                        </div>
                    </div>
                    <div id="demographics-chart-view">
                        <div class="chart-wrap chart-wrap--sm"><canvas id="demographicsChart"></canvas></div>
                    </div>
                    <div id="demographics-table-view" style="display:none;">
                        <table class="data-table compact-table">
                            <thead><tr><th>Sex</th><th>Count</th></tr></thead>
                            <tbody>
                                <?php foreach ($demographics['by_sex'] ?? [] as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['Sex'] ?? 'Not set') ?></td>
                                        <td><?= (int)$r['count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3>Staff Performance</h3>
                            <span class="card-desc">Requests processed</span>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-ghost btn-small" onclick="toggleView('staff')">
                                <span id="staff-toggle-label">📊 Chart</span>
                            </button>
                            <a href="?export=staff" class="btn btn-secondary btn-small" download>⬇ CSV</a>
                        </div>
                    </div>
                    <div id="staff-chart-view">
                        <div class="chart-wrap chart-wrap--sm"><canvas id="staffChart"></canvas></div>
                    </div>
                    <div id="staff-table-view" style="display:none;">
                        <table class="data-table compact-table">
                            <thead><tr><th>Name</th><th>Role</th><th>Processed</th><th>Avg Hours</th></tr></thead>
                            <tbody>
                                <?php if (empty($staffPerformance)): ?>
                                    <tr><td colspan="4" class="empty-row">No staff activity found.</td></tr>
                                <?php else: foreach ($staffPerformance as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['FullName'] ?? 'Unnamed') ?></td>
                                        <td><?= htmlspecialchars($r['Role']) ?></td>
                                        <td><?= (int)$r['TotalProcessed'] ?></td>
                                        <td><?= $r['AvgHours'] !== null ? number_format((float)$r['AvgHours'], 1) : '0.0' ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Past SLA -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>⚠️ Pending Requests Past SLA</h3>
                    <a href="?export=pending" class="btn btn-secondary btn-small" download>⬇ Download CSV</a>
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
                            <tr><td colspan="5" class="empty-row">✅ No requests are past SLA.</td></tr>
                        <?php else: foreach ($weeklyPending as $r): ?>
                            <tr class="row-warn">
                                <td><?= htmlspecialchars($r['ReferenceNo']) ?></td>
                                <td><?= htmlspecialchars($r['ResidentName']) ?></td>
                                <td><?= htmlspecialchars($r['RequestType']) ?></td>
                                <td><span class="badge badge-<?= strtolower($r['Status']) ?>"><?= htmlspecialchars($r['Status']) ?></span></td>
                                <td><strong><?= (int)$r['HoursElapsed'] ?>h</strong></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Completed Transactions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>✅ Completed Transactions</h3>
                    <div class="card-actions">
                        <span class="card-desc">Last 50 released requests</span>
                        <a href="?export=completed" class="btn btn-secondary btn-small" download>⬇ Download CSV</a>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Released At</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Receipt No.</th>
                            <th>Released By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($completedTransactions)): ?>
                            <tr><td colspan="8" class="empty-row">No completed transactions yet.</td></tr>
                        <?php else: foreach ($completedTransactions as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['ReferenceNo']) ?></td>
                                <td><?= htmlspecialchars($t['ResidentName']) ?></td>
                                <td><?= htmlspecialchars($t['RequestType']) ?></td>
                                <td><?= $t['ReleasedAt'] ? date('M d, Y h:i A', strtotime($t['ReleasedAt'])) : '—' ?></td>
                                <td>₱<?= number_format((float)$t['Amount'], 2) ?></td>
                                <td><?= htmlspecialchars($t['PaymentMethod']) ?></td>
                                <td><?= htmlspecialchars($t['ReceiptNo']) ?></td>
                                <td><?= htmlspecialchars($t['ReleasedBy'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const monthly    = <?= $chartMonthly ?>;
const complaints = <?= $chartComplaints ?>;
const facility   = <?= $chartFacility ?>;
const staff      = <?= $chartStaff ?>;
const demog      = <?= $chartDemog ?>;
const payments   = <?= $chartPayments ?>;

const GREEN = '#2d6a4f', GOLD = '#c9a84c', TEAL = '#40916c',
      RED   = '#e05252', BLUE = '#3b82f6';
const MUTED = [GREEN, GOLD, TEAL, '#74c69d', RED, BLUE, '#a78bfa'];

Chart.defaults.font.family = 'inherit';
Chart.defaults.color = '#4b5563';

const emptyStatePlugin = {
    id: 'emptyState',
    afterDraw(chart) {
        const total = chart.data.datasets.reduce(
            (s, ds) => s + ds.data.reduce((a, v) => a + (v || 0), 0), 0);
        if (total > 0) return;
        const { ctx, chartArea: { left, top, width, height } } = chart;
        ctx.save();
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillStyle = '#9ca3af'; ctx.font = '500 13px inherit';
        ctx.fillText('No data available yet', left + width / 2, top + height / 2 - 10);
        ctx.fillStyle = '#d1d5db'; ctx.font = '12px inherit';
        ctx.fillText('Data will appear once records are added', left + width / 2, top + height / 2 + 12);
        ctx.restore();
    }
};
Chart.register(emptyStatePlugin);

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: monthly.length ? monthly.map(r => r.RequestType) : ['Clearance','Indigency','Reservation','Complaint'],
        datasets: [
            { label: 'Total',     data: monthly.map(r => r.Total),     backgroundColor: BLUE,  borderRadius: 4 },
            { label: 'Released',  data: monthly.map(r => r.Released),  backgroundColor: GREEN, borderRadius: 4 },
            { label: 'Rejected',  data: monthly.map(r => r.Rejected),  backgroundColor: RED,   borderRadius: 4 },
            { label: 'Cancelled', data: monthly.map(r => r.Cancelled), backgroundColor: GOLD,  borderRadius: 4 },
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('paymentsChart'), {
    type: 'bar',
    data: {
        labels: payments.length ? payments.map(r => r.RequestType) : ['No data'],
        datasets: [
            { label: 'Cash (₱)',  data: payments.map(r => parseFloat(r.CashTotal)  || 0), backgroundColor: GREEN, borderRadius: 4 },
            { label: 'GCash (₱)', data: payments.map(r => parseFloat(r.GCashTotal) || 0), backgroundColor: BLUE,  borderRadius: 4 },
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

(function() {
    const has = complaints.length > 0;
    new Chart(document.getElementById('complaintsChart'), {
        type: 'doughnut',
        data: {
            labels: has ? complaints.map(r => r.Status) : ['No data'],
            datasets: [{ data: has ? complaints.map(r => r.Count) : [0], backgroundColor: has ? MUTED : ['#e5e7eb'], hoverOffset: has ? 6 : 0, borderWidth: has ? 2 : 0 }]
        },
        options: { responsive: true, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { filter: i => i.text !== 'No data' } } } }
    });
})();

new Chart(document.getElementById('facilityChart'), {
    type: 'bar',
    data: {
        labels: facility.length ? facility.map(r => r.FacilityName) : ['No facilities'],
        datasets: [
            { label: 'Total',    data: facility.map(r => r.TotalReservations),    backgroundColor: BLUE,  borderRadius: 4 },
            { label: 'Approved', data: facility.map(r => r.ApprovedReservations), backgroundColor: GREEN, borderRadius: 4 },
        ]
    },
    options: { indexAxis: 'y', responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

(function() {
    const has = demog.length > 0;
    new Chart(document.getElementById('demographicsChart'), {
        type: 'pie',
        data: {
            labels: has ? demog.map(r => r.Sex || 'Not set') : ['No data'],
            datasets: [{ data: has ? demog.map(r => r.count) : [0], backgroundColor: has ? [GREEN, GOLD, TEAL, BLUE, RED] : ['#e5e7eb'], hoverOffset: has ? 6 : 0, borderWidth: has ? 2 : 0 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { filter: i => i.text !== 'No data' } } } }
    });
})();

new Chart(document.getElementById('staffChart'), {
    type: 'bar',
    data: {
        labels: staff.length ? staff.map(r => r.FullName || 'Unnamed') : ['No staff'],
        datasets: [
            { label: 'Processed', data: staff.map(r => r.TotalProcessed), backgroundColor: GREEN, borderRadius: 4 },
            { label: 'Avg Hours', data: staff.map(r => r.AvgHours ? parseFloat(r.AvgHours) : 0), backgroundColor: GOLD, borderRadius: 4 },
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

function toggleView(key) {
    const c = document.getElementById(key + '-chart-view');
    const t = document.getElementById(key + '-table-view');
    const l = document.getElementById(key + '-toggle-label');
    const show = c.style.display !== 'none';
    c.style.display = show ? 'none' : '';
    t.style.display = show ? '' : 'none';
    l.textContent   = show ? '📋 Table' : '📊 Chart';
}
</script>

<style>
.card-desc    { color: #6b7280; font-size: 0.82rem; margin-top: 2px; display: block; }
.card-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.report-grid  { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.compact-table { font-size: 0.88rem; }
.mt-4         { margin-top: 1rem; }
.chart-wrap   { padding: 8px 0 16px; max-height: 320px; display: flex; align-items: center; justify-content: center; }
.chart-wrap canvas { max-height: 300px; }
.chart-wrap--sm { max-height: 260px; }
.chart-wrap--sm canvas { max-height: 240px; }
.row-warn td  { background: #fff8e6; }
.btn-ghost    { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-ghost:hover { background: #f3f4f6; }
.table-total td { background: #f0fdf4; }
@media (max-width: 900px) {
    .report-grid  { grid-template-columns: 1fr; }
    .card-header  { flex-wrap: wrap; gap: 8px; }
    .card-actions { width: 100%; justify-content: flex-start; }
}
</style>
<?php include '../includes/footer.php'; ?>