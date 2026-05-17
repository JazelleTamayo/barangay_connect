<?php
// Barangay Connect – Request Acceptance
// staff/request_acceptance.php
// FIXED: resident_id was never populated (text search had no JS) → replaced with DB select dropdown
// FIXED: missing CSRF token
// FIXED: wrong JS path (assets/css/js/ → assets/js/)

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$pdo = get_db();

// Load all active residents for the dropdown
$residents = $pdo->query(
    "SELECT ResidentID, FirstName, MiddleName, LastName
     FROM resident
     WHERE Status = 'Active'
     ORDER BY LastName, FirstName"
)->fetchAll(PDO::FETCH_ASSOC);

// Load active facilities for facility reservation
$facilities = $pdo->query(
    "SELECT FacilityID, FacilityName, ReservationFee, Capacity
     FROM Facility
     WHERE Status = 'Active'
     ORDER BY FacilityName ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Calculate earliest reservation date (working days)
$today       = new DateTime('today');
$workingDays = 0;
$check       = clone $today;
while ($workingDays < RESERVATION_LEAD_DAYS) {
    $check->modify('+1 day');
    if ((int)$check->format('N') < 6) $workingDays++;
}
$minReservationDate = $check->format('Y-m-d');

$page_title = 'Accept Service Request';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
                <div class="alert alert-success">✅ Request created successfully.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'double_booking'): ?>
                <div class="alert alert-error">⚠️ <strong>That facility is already reserved on the selected date.</strong> Please choose a different date or facility.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'lead_time_error'): ?>
                <div class="alert alert-error">⚠️ Reservation date must be at least <?= RESERVATION_LEAD_DAYS ?> working days from today.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'over_capacity'): ?>
                <div class="alert alert-error">⚠️ <strong>Number of attendees exceeds the facility's maximum capacity.</strong> Please reduce the attendee count or choose a larger facility.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>New Service Request</h3>
                    <p class="card-desc">
                        Select the request type first then fill in the details.
                    </p>
                </div>
                <form method="POST"
                    action="../handlers/request_create_handler.php"
                    class="form-vertical validate-form"
                    id="request-form">

                    <input type="hidden" name="submitted_by" value="staff">
                    <!-- FIXED: CSRF token was missing -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_generate()) ?>">

                    <div class="form-group">
                        <label>Request Type *</label>
                        <select name="request_type"
                            class="form-select"
                            required
                            id="req-type-select">
                            <option value="">-- Select Request Type --</option>
                            <option value="Clearance">Barangay Clearance</option>
                            <option value="Indigency">Certificate of Indigency</option>
                            <option value="FacilityReservation">Facility Reservation</option>
                            <option value="Complaint">Complaint</option>
                        </select>
                    </div>

                    <!-- FIXED: replaced broken text search + empty hidden field with a proper select -->
                    <div class="form-group">
                        <label>Resident Name *</label>
                        <select name="resident_id" class="form-select" required>
                            <option value="">-- Select Resident --</option>
                            <?php foreach ($residents as $r): ?>
                                <option value="<?= $r['ResidentID'] ?>">
                                    <?= htmlspecialchars(
                                        trim($r['LastName'] . ', ' . $r['FirstName'] .
                                            ($r['MiddleName'] ? ' ' . $r['MiddleName'] : ''))
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Purpose / Description *</label>
                        <textarea name="purpose"
                            rows="3"
                            class="form-textarea"
                            required
                            placeholder="State the purpose of the request..."></textarea>
                    </div>

                    <!-- Facility Reservation Fields -->
                    <div id="facility-fields" style="display:none;">

                        <div class="form-divider">Facility &amp; Schedule</div>

                        <div class="form-group">
                            <label>Select Facility <span class="req">*</span></label>
                            <select name="facility_id" class="form-select" id="facility-select"
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
                                <label>Reservation Date <span class="req">*</span></label>
                                <input type="date" name="reservation_date" class="form-input"
                                    min="<?= $minReservationDate ?>">
                                <small class="form-hint">
                                    At least <?= RESERVATION_LEAD_DAYS ?> working days from today.
                                    Earliest: <strong><?= date('M j, Y', strtotime($minReservationDate)) ?></strong>.
                                </small>
                            </div>
                            <div class="form-group">
                                <label>Time Slot <span class="req">*</span></label>
                                <select name="time_slot" class="form-select">
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
                                <input type="text" name="event_name" class="form-input"
                                    placeholder="e.g. Birthday Party, Community Meeting">
                            </div>
                            <div class="form-group">
                                <label>Expected Number of Attendees <span class="req">*</span></label>
                                <input type="number" name="expected_attendees" class="form-input"
                                    min="1" placeholder="e.g. 50">
                                <div id="capacity-warning" class="capacity-warning" style="display:none;"></div>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Contact Person Name <span class="req">*</span></label>
                                <input type="text" name="contact_person" class="form-input"
                                    placeholder="Name of person in charge during the event">
                            </div>
                            <div class="form-group">
                                <label>Contact Person Number <span class="req">*</span></label>
                                <input type="text" name="contact_person_number" class="form-input"
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
                                <li>Any <strong>damages caused during the event</strong> will be the <strong>full financial responsibility</strong> of the reserver.</li>
                                <li>Non-compliance may result in <strong>cancellation of current and future reservations</strong>.</li>
                            </ol>
                            <label class="rules-check">
                                <input type="checkbox" name="agreed_to_rules" value="1">
                                <span>I have read, understood, and agree to all the facility rules stated above. <span class="req">*</span></span>
                            </label>
                        </div>

                    </div>

                    <!-- Complaint Fields -->
                    <div id="complaint-fields" style="display:none;">
                        <div class="form-group">
                            <label>Respondent Name *</label>
                            <input type="text"
                                name="respondent_name"
                                class="form-input"
                                placeholder="Full name of respondent..." />
                        </div>
                        <div class="form-group">
                            <label>Respondent Contact</label>
                            <input type="text"
                                name="respondent_contact"
                                class="form-input"
                                placeholder="09XXXXXXXXX" />
                        </div>
                        <div class="form-group">
                            <label>Incident Date *</label>
                            <input type="date"
                                name="incident_date"
                                class="form-input" />
                        </div>
                        <div class="form-group">
                            <label>Incident Location *</label>
                            <input type="text"
                                name="incident_location"
                                class="form-input"
                                placeholder="Where did the incident happen?" />
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>

                </form>
            </div>

        </div>
    </main>
</div>
<!-- FIXED: wrong path was assets/css/js/ → corrected to assets/js/ -->
<script src="/barangay_connect/assets/js/form_validation.js"></script>
<script>
    document.getElementById('req-type-select').addEventListener('change', function() {
        document.getElementById('facility-fields').style.display =
            this.value === 'FacilityReservation' ? 'block' : 'none';
        document.getElementById('complaint-fields').style.display =
            this.value === 'Complaint' ? 'block' : 'none';

        // Toggle required attributes for facility sub-fields
        const isFacility = this.value === 'FacilityReservation';
        document.querySelector('[name="facility_id"]').required       = isFacility;
        document.querySelector('[name="reservation_date"]').required  = isFacility;
        document.querySelector('[name="event_name"]').required        = isFacility;
        document.querySelector('[name="expected_attendees"]').required = isFacility;
        document.querySelector('[name="contact_person"]').required    = isFacility;
        document.querySelector('[name="contact_person_number"]').required = isFacility;
        document.querySelector('[name="agreed_to_rules"]').required   = isFacility;
    });

    function updateFacilityInfo(sel) {
        const opt  = sel.options[sel.selectedIndex];
        const info = document.getElementById('facilityInfo');
        if (opt.value) {
            document.getElementById('facilityFee').textContent =
                'Fee: ₱' + parseFloat(opt.dataset.fee).toFixed(2);
            document.getElementById('facilityCapacity').textContent =
                'Capacity: ' + opt.dataset.cap + ' persons';
            info.style.display = 'block';
            // Update max on attendees field
            const attendeesInput = document.querySelector('[name="expected_attendees"]');
            if (attendeesInput) {
                attendeesInput.max = opt.dataset.cap;
                validateAttendees(attendeesInput, parseInt(opt.dataset.cap));
            }
        } else {
            info.style.display = 'none';
            const attendeesInput = document.querySelector('[name="expected_attendees"]');
            if (attendeesInput) attendeesInput.removeAttribute('max');
        }
    }

    function validateAttendees(input, capacity) {
        const warning = document.getElementById('capacity-warning');
        if (input.value && parseInt(input.value) > capacity) {
            warning.textContent = '⚠️ Exceeds facility capacity of ' + capacity + ' persons.';
            warning.style.display = 'block';
            input.setCustomValidity('Exceeds facility capacity of ' + capacity + ' persons.');
        } else {
            warning.textContent = '';
            warning.style.display = 'none';
            input.setCustomValidity('');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const attendeesInput = document.querySelector('[name="expected_attendees"]');
        if (attendeesInput) {
            attendeesInput.addEventListener('input', function () {
                const facilitySelect = document.getElementById('facility-select');
                const opt = facilitySelect ? facilitySelect.options[facilitySelect.selectedIndex] : null;
                const cap = opt && opt.value ? parseInt(opt.dataset.cap) : Infinity;
                validateAttendees(this, cap);
            });
        }
    });
</script>

<style>
    .req { color: #e53e3e; }

    .capacity-warning {
        margin-top: 5px;
        font-size: 0.82rem;
        color: #dc2626;
        font-weight: 600;
        background: #fee2e2;
        border: 1px solid #fca5a5;
        border-radius: 5px;
        padding: 5px 10px;
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
        .form-row-2 { grid-template-columns: 1fr; }
    }

    .facility-info {
        margin-top: 6px;
        font-size: 0.82rem;
        color: #2e7d32;
        font-weight: 600;
    }

    .form-hint {
        font-size: 0.78rem;
        color: #6b7280;
        margin-top: 4px;
        display: block;
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
</style>
<?php include '../includes/footer.php'; ?>