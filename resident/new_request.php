<?php
// Barangay Connect – New Request
// resident/new_request.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$page_title = 'New Request';
$type = $_GET['type'] ?? '';

// Load active facilities for the dropdown
$pdo = get_db();
$facilities = $pdo->query("
    SELECT FacilityID, FacilityName, ReservationFee, Capacity
    FROM Facility
    WHERE Status = 'Active'
    ORDER BY FacilityName ASC
")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>New Service Request</h1>
            <span class="page-subtitle">Select a service type to get started</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg'])): ?>
                <?php $msg = $_GET['msg']; ?>
                <?php if ($msg === 'created'): ?>
                    <div class="alert alert-success">✅ Request submitted successfully. Check your dashboard for the reference number.</div>
                <?php elseif ($msg === 'missing_fields'): ?>
                    <div class="alert alert-error">⚠️ Please fill in all required fields.</div>
                <?php elseif ($msg === 'not_good_standing'): ?>
                    <div class="alert alert-error">⚠️ You have an existing active request. Please wait for it to be resolved before submitting a new one.</div>
                <?php elseif ($msg === 'double_booking'): ?>
                    <div class="alert alert-error">⚠️ That facility is already booked on the selected date. Please choose another date.</div>
                <?php elseif ($msg === 'lead_time_error'): ?>
                    <div class="alert alert-error">⚠️ Reservation date must be at least <?= RESERVATION_LEAD_DAYS ?> working days from today.</div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Request Type Cards -->
            <div class="request-type-grid">
                <a href="?type=Clearance"
                   class="request-type-card <?= $type === 'Clearance' ? 'active' : '' ?>">
                    <div class="rt-icon">📄</div>
                    <h3>Barangay Clearance</h3>
                    <p>For employment, education, legal purposes</p>
                    <small>Fee applies · 1 hour processing</small>
                </a>
                <a href="?type=Indigency"
                   class="request-type-card <?= $type === 'Indigency' ? 'active' : '' ?>">
                    <div class="rt-icon">🤝</div>
                    <h3>Certificate of Indigency</h3>
                    <p>For financial assistance and benefits</p>
                    <small>Free · 24 hour processing</small>
                </a>
                <a href="?type=FacilityReservation"
                   class="request-type-card <?= $type === 'FacilityReservation' ? 'active' : '' ?>">
                    <div class="rt-icon">🏟️</div>
                    <h3>Facility Reservation</h3>
                    <p>Reserve barangay venues and facilities</p>
                    <small>Fee varies · 3 day lead time</small>
                </a>
                <a href="?type=Complaint"
                   class="request-type-card <?= $type === 'Complaint' ? 'active' : '' ?>">
                    <div class="rt-icon">⚠️</div>
                    <h3>File a Complaint</h3>
                    <p>Report incidents within the barangay</p>
                    <small>Free · 7 day scheduling</small>
                </a>
            </div>

            <!-- Request Form -->
            <?php if (!empty($type)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h3>
                        <?php
                        $type_labels = [
                            'Clearance'          => '📄 Barangay Clearance',
                            'Indigency'          => '🤝 Certificate of Indigency',
                            'FacilityReservation'=> '🏟️ Facility Reservation',
                            'Complaint'          => '⚠️ File a Complaint',
                        ];
                        echo htmlspecialchars($type_labels[$type] ?? $type);
                        ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="../handlers/request_create_handler.php"
                          class="form-vertical validate-form">
                        <input type="hidden" name="request_type" value="<?= htmlspecialchars($type) ?>">
                        <input type="hidden" name="submitted_by" value="resident">

                        <!-- Purpose — all types -->
                        <div class="form-group">
                            <label>Purpose / Description <span class="req">*</span></label>
                            <textarea name="purpose" rows="4" class="form-textarea" required
                                      placeholder="Briefly state your purpose..."></textarea>
                        </div>

                        <!-- ===== INDIGENCY FIELDS ===== -->
                        <?php if ($type === 'Indigency'): ?>
                            <div class="form-divider">Financial Assessment (optional but recommended)</div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Monthly Income (₱)</label>
                                    <input type="number" step="0.01" min="0" name="monthly_income"
                                           class="form-input" placeholder="e.g. 5000">
                                    <small class="form-hint">Leave blank if none.</small>
                                </div>
                                <div class="form-group">
                                    <label>Household Size (including yourself)</label>
                                    <input type="number" name="household_size"
                                           class="form-input" min="1" value="1">
                                </div>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Employment Status</label>
                                    <select name="employment_status" class="form-input">
                                        <option value="">— Select —</option>
                                        <option value="Employed">Employed</option>
                                        <option value="Self-employed">Self-employed</option>
                                        <option value="Unemployed">Unemployed</option>
                                        <option value="Retired">Retired</option>
                                        <option value="PWD">PWD</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Source of Income</label>
                                    <input type="text" name="income_source" class="form-input"
                                           placeholder="e.g. daily labor, small store">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Government Assistance Received (if any)</label>
                                <input type="text" name="assistance_received" class="form-input"
                                       placeholder="e.g. 4Ps, senior citizen pension, none">
                            </div>
                        <?php endif; ?>

                        <!-- ===== FACILITY RESERVATION FIELDS ===== -->
                        <?php if ($type === 'FacilityReservation'): ?>
                            <div class="form-divider">Facility & Schedule</div>

                            <div class="form-group">
                                <label>Select Facility <span class="req">*</span></label>
                                <select name="facility_id" class="form-input" required
                                        onchange="updateFacilityInfo(this)">
                                    <option value="">— Choose a facility —</option>
                                    <?php foreach ($facilities as $f): ?>
                                        <option value="<?= $f['FacilityID'] ?>"
                                                data-fee="<?= $f['ReservationFee'] ?>"
                                                data-cap="<?= $f['Capacity'] ?>">
                                            <?= htmlspecialchars($f['FacilityName']) ?>
                                            — ₱<?= number_format($f['ReservationFee'], 2) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="facilityInfo" class="facility-info" style="display:none;">
                                    <span id="facilityFee"></span> &middot; <span id="facilityCapacity"></span>
                                </div>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Preferred Date <span class="req">*</span></label>
                                    <input type="date" name="reservation_date" class="form-input" required
                                           min="<?= date('Y-m-d', strtotime('+' . RESERVATION_LEAD_DAYS . ' days')) ?>">
                                    <small class="form-hint">Must be at least <?= RESERVATION_LEAD_DAYS ?> days from today.</small>
                                </div>
                                <div class="form-group">
                                    <label>Time Slot</label>
                                    <select name="time_slot" class="form-input">
                                        <option value="Morning (8AM-12PM)">Morning (8AM–12PM)</option>
                                        <option value="Afternoon (1PM-5PM)">Afternoon (1PM–5PM)</option>
                                        <option value="Full Day">Full Day</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- ===== COMPLAINT FIELDS ===== -->
                        <?php if ($type === 'Complaint'): ?>
                            <div class="form-divider">Complaint Details</div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Respondent Name <span class="req">*</span></label>
                                    <input type="text" name="respondent_name" class="form-input"
                                           required placeholder="Full name of respondent">
                                </div>
                                <div class="form-group">
                                    <label>Respondent Contact</label>
                                    <input type="text" name="respondent_contact" class="form-input"
                                           placeholder="09XXXXXXXXX">
                                </div>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Incident Date <span class="req">*</span></label>
                                    <input type="date" name="incident_date" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label>Incident Location <span class="req">*</span></label>
                                    <input type="text" name="incident_location" class="form-input"
                                           required placeholder="Where did it happen?">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                            <a href="new_request.php" class="btn btn-secondary">Back</a>
                        </div>

                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
function updateFacilityInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('facilityInfo');
    if (opt.value) {
        document.getElementById('facilityFee').textContent =
            'Fee: ₱' + parseFloat(opt.dataset.fee).toFixed(2);
        document.getElementById('facilityCapacity').textContent =
            'Capacity: ' + opt.dataset.cap + ' persons';
        info.style.display = 'block';
    } else {
        info.style.display = 'none';
    }
}
</script>

<style>
.req { color: #e53e3e; }
.form-divider {
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 6px; margin: 20px 0 14px;
}
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 640px) {
    .form-row-2 { grid-template-columns: 1fr; }
}
.facility-info {
    margin-top: 6px;
    font-size: 0.82rem;
    color: #2e7d32;
    font-weight: 600;
}
.card-body { padding: 20px 24px 24px; }
</style>

<?php include '../includes/footer.php'; ?>