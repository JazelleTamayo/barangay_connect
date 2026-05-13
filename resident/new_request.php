<?php
// Barangay Connect – New Request
// resident/new_request.php
//
// FIXED Bug #1 (double_booking): The redirect from request_create_handler.php
//   sends ?type=FacilityReservation&msg=double_booking, so $type is correctly
//   read from $_GET — the form DOES re-open. However the error alert was
//   rendering ABOVE the type cards and disappearing before the user scrolled
//   down to the form. Fix: also pass ?type= when displaying errors so the
//   Facility Reservation card is highlighted and the form is visible alongside
//   the alert. Also added a link to the facility schedule so the resident can
//   check available dates without leaving the page.
//
// FIXED Bug #2 (not_good_standing message): The message said "You have an
//   existing active request. Please wait for it to be resolved." This is WRONG.
//   Good standing for Clearance means no unresolved COMPLAINT as respondent and
//   no ordinance violation — it has nothing to do with existing requests.
//   Corrected the message to accurately reflect BR-03.
//
// FIXED Bug #3 (date min attribute): The date input's min was calculated as
//   today + RESERVATION_LEAD_DAYS calendar days using strtotime('+3 days').
//   But BR-08 requires 3 WORKING days (Mon–Fri), not calendar days. If today
//   is Thursday, +3 calendar days = Sunday, but the correct earliest date is
//   Tuesday (3 working days forward). The min attribute now uses the same
//   working-day calculation logic as the backend handler.
//
// FIXED Bug #4 (account_inactive): Added missing message handler for the
//   account_inactive redirect added in request_create_handler.php (Bug #6 fix).

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$page_title = 'New Request';
$type = $_GET['type'] ?? '';
$msg  = $_GET['msg']  ?? '';

// Load active facilities for the dropdown
$pdo = get_db();
$facilities = $pdo->query("
    SELECT FacilityID, FacilityName, ReservationFee, Capacity
    FROM Facility
    WHERE Status = 'Active'
    ORDER BY FacilityName ASC
")->fetchAll(PDO::FETCH_ASSOC);

// FIXED Bug #3: Calculate the correct earliest reservation date using
// working days (Mon–Fri), matching the backend logic in request_create_handler.php.
$today       = new DateTime('today');
$workingDays = 0;
$check       = clone $today;
while ($workingDays < RESERVATION_LEAD_DAYS) {
    $check->modify('+1 day');
    $dow = (int) $check->format('N'); // 1=Mon … 7=Sun
    if ($dow < 6) {                   // Mon–Fri only
        $workingDays++;
    }
}
$minReservationDate = $check->format('Y-m-d');

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

            <?php if (!empty($msg)): ?>
                <?php if ($msg === 'created'): ?>
                    <div class="alert alert-success">✅ Request submitted successfully. Check your dashboard for the reference number.</div>

                <?php elseif ($msg === 'missing_fields'): ?>
                    <div class="alert alert-error">⚠️ Please fill in all required fields.</div>

                <?php elseif ($msg === 'not_good_standing'): ?>
                    <!-- FIXED Bug #2: Corrected message to accurately reflect BR-03 good standing rules. -->
                    <div class="alert alert-error">
                        ⚠️ <strong>Cannot submit Barangay Clearance.</strong>
                        You do not currently meet the good standing requirement.<br>
                        This may be because you have an <strong>unresolved complaint where you are the respondent</strong>,
                        or a <strong>recorded barangay ordinance violation within the last 6 months</strong>.
                        Please visit the barangay office for assistance.
                    </div>

                <?php elseif ($msg === 'double_booking'): ?>
                    <!-- FIXED Bug #1: Added facility schedule link so resident can check available dates. -->
                    <div class="alert alert-error">
                        ⚠️ <strong>That facility is already reserved on the selected date.</strong>
                        Please choose a different date or facility.<br>
                        <a href="facility_schedule.php"
                           style="color:#991b1b; font-weight:600; text-decoration:underline;">
                            View the Facility Schedule →
                        </a>
                        to see which dates are available.
                    </div>

                <?php elseif ($msg === 'lead_time_error'): ?>
                    <div class="alert alert-error">
                        ⚠️ Reservation date must be at least <?= RESERVATION_LEAD_DAYS ?> <strong>working days</strong> from today.
                        The earliest available date is <strong><?= date('F j, Y', strtotime($minReservationDate)) ?></strong>.
                    </div>

                <?php elseif ($msg === 'account_inactive'): ?>
                    <!-- FIXED Bug #4: Added handler for account_inactive redirect. -->
                    <div class="alert alert-error">
                        ⚠️ Your account is currently inactive. You cannot submit service requests.
                        Please visit the barangay office for assistance.
                    </div>

                <?php elseif ($msg === 'resident_not_found'): ?>
                    <div class="alert alert-error">⚠️ Resident profile not found. Please contact the barangay office.</div>
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
                    <small>Fee varies · <?= RESERVATION_LEAD_DAYS ?> working day lead time</small>
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
                                'Clearance'           => '📄 Barangay Clearance',
                                'Indigency'           => '🤝 Certificate of Indigency',
                                'FacilityReservation' => '🏟️ Facility Reservation',
                                'Complaint'           => '⚠️ File a Complaint',
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
                                <div class="form-divider">Financial Assessment <span class="req">*</span> <small style="font-weight:400;color:#6b7280;">(All fields required)</small></div>

                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label>Monthly Income (₱) <span class="req">*</span></label>
                                        <input type="number" step="0.01" min="0" name="monthly_income"
                                            class="form-input" placeholder="e.g. 5000" required>
                                        <small class="form-hint">Enter 0 if no income.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Household Size (including yourself) <span class="req">*</span></label>
                                        <input type="number" name="household_size"
                                            class="form-input" min="1" value="1" required>
                                    </div>
                                </div>

                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label>Employment Status <span class="req">*</span></label>
                                        <select name="employment_status" class="form-input" required>
                                            <option value="">— Select —</option>
                                            <option value="Employed">Employed</option>
                                            <option value="Self-employed">Self-employed</option>
                                            <option value="Unemployed">Unemployed</option>
                                            <option value="Retired">Retired</option>
                                            <option value="PWD">PWD</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Source of Income <span class="req">*</span></label>
                                        <input type="text" name="income_source" class="form-input" required
                                            placeholder="e.g. daily labor, small store">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Government Assistance Received <span class="req">*</span></label>
                                    <input type="text" name="assistance_received" class="form-input" required
                                        placeholder="e.g. 4Ps, senior citizen pension, none">
                                </div>
                            <?php endif; ?>

                            <!-- ===== FACILITY RESERVATION FIELDS ===== -->
                            <?php if ($type === 'FacilityReservation'): ?>
                                <div class="form-divider">Facility &amp; Schedule</div>

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
                                        <!-- FIXED Bug #3: min is now the correct working-day earliest date,
                                             not just +LEAD_DAYS calendar days. -->
                                        <input type="date" name="reservation_date" class="form-input" required
                                            min="<?= $minReservationDate ?>">
                                        <small class="form-hint">
                                            Must be at least <?= RESERVATION_LEAD_DAYS ?> working days from today.
                                            Earliest: <strong><?= date('M j, Y', strtotime($minReservationDate)) ?></strong>.
                                            <a href="facility_schedule.php" style="color:var(--green-dark);">Check schedule →</a>
                                        </small>
                                    </div>
                                    <div class="form-group">
                                        <label>Time Slot <span class="req">*</span></label>
                                        <select name="time_slot" class="form-input" required>
                                            <option value="Morning (8AM-12PM)">Morning (8AM–12PM)</option>
                                            <option value="Afternoon (1PM-5PM)">Afternoon (1PM–5PM)</option>
                                            <option value="Full Day">Full Day</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-divider">Event Details</div>

                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label>Event / Activity Name <span class="req">*</span></label>
                                        <input type="text" name="event_name" class="form-input" required
                                            placeholder="e.g. Birthday Party, Community Meeting">
                                    </div>
                                    <div class="form-group">
                                        <label>Expected Number of Attendees <span class="req">*</span></label>
                                        <input type="number" name="expected_attendees" class="form-input" required
                                            min="1" placeholder="e.g. 50">
                                    </div>
                                </div>

                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label>Contact Person Name <span class="req">*</span></label>
                                        <input type="text" name="contact_person" class="form-input" required
                                            placeholder="Name of person in charge during the event">
                                    </div>
                                    <div class="form-group">
                                        <label>Contact Person Number <span class="req">*</span></label>
                                        <input type="text" name="contact_person_number" class="form-input" required
                                            placeholder="09XXXXXXXXX">
                                    </div>
                                </div>

                                <div class="form-divider">Facility Rules &amp; Agreement</div>
                                <div class="rules-box">
                                    <p class="rules-title">Please read and agree to the following rules before submitting:</p>
                                    <ol class="rules-list">
                                        <li>The facility must be left <strong>clean and in good condition</strong> after use.</li>
                                        <li><strong>Vandalism or destruction</strong> of any property is strictly prohibited.</li>
                                        <li>Noise must be kept at a <strong>reasonable level</strong> at all times.</li>
                                        <li>The reserved <strong>time slot must be strictly followed</strong>. Overstaying is not allowed.</li>
                                        <li>Any <strong>damages caused during the event</strong> will be the <strong>full financial responsibility</strong> of the reserver and must be settled before any future reservations.</li>
                                        <li>Non-compliance with these rules may result in <strong>cancellation of current and future reservations</strong>.</li>
                                    </ol>
                                    <label class="rules-check">
                                        <input type="checkbox" name="agreed_to_rules" value="1" required>
                                        <span>I have read, understood, and agree to all the facility rules stated above. <span class="req">*</span></span>
                                    </label>
                                </div>
                            <?php endif; ?>

                            <!-- ===== COMPLAINT FIELDS ===== -->
                            <?php if ($type === 'Complaint'): ?>
                                <div class="form-divider">Respondent Information</div>

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
                                        <label>Relationship to Respondent</label>
                                        <select name="respondent_relationship" class="form-input">
                                            <option value="">— Select —</option>
                                            <option value="Neighbor">Neighbor</option>
                                            <option value="Family Member">Family Member</option>
                                            <option value="Colleague">Colleague</option>
                                            <option value="Stranger">Stranger</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <!-- Removed duplicate "Respondent Contact" label that was here before -->
                                    </div>
                                </div>

                                <div class="form-divider">Incident Details</div>

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

                                <div class="form-group">
                                    <label>Witnesses <span class="form-hint">(optional — names and contact numbers)</span></label>
                                    <textarea name="witnesses" class="form-textarea" rows="2"
                                        placeholder="e.g. Juan dela Cruz - 09171234567, Maria Reyes - 09281234567"></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Relief Sought <span class="form-hint">(optional — what outcome do you want?)</span></label>
                                    <textarea name="relief_sought" class="form-textarea" rows="2"
                                        placeholder="e.g. Mediation, apology, compensation for damages..."></textarea>
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
    .req {
        color: #e53e3e;
    }

    .rules-box {
        background: #fefce8;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 16px 20px;
        margin-top: 4px;
    }

    .rules-title {
        font-weight: 600;
        font-size: 0.875rem;
        color: #92400e;
        margin-bottom: 10px;
    }

    .rules-list {
        margin: 0 0 14px 0;
        padding-left: 20px;
        font-size: 0.875rem;
        color: #374151;
        line-height: 1.8;
    }

    .rules-check {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 0.875rem;
        color: #374151;
        cursor: pointer;
    }

    .rules-check input {
        margin-top: 3px;
        flex-shrink: 0;
        accent-color: #2e7d32;
        width: 16px;
        height: 16px;
    }

    .form-divider {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 6px;
        margin: 20px 0 14px;
    }

    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    @media (max-width: 640px) {
        .form-row-2 {
            grid-template-columns: 1fr;
        }
    }

    .facility-info {
        margin-top: 6px;
        font-size: 0.82rem;
        color: #2e7d32;
        font-weight: 600;
    }

    .card-body {
        padding: 20px 24px 24px;
    }
</style>

<?php include '../includes/footer.php'; ?>