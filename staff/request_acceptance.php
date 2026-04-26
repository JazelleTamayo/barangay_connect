<?php
// Barangay Connect – Request Acceptance
// staff/request_acceptance.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$page_title = 'Accept Service Request';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Accept Service Request</h1>
            <span class="page-subtitle">Create a new request on behalf of a resident</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
                <div class="alert alert-success">✅ Request created successfully.</div>
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

                    <input type="hidden" name="submitted_by" value="staff"> <!-- FIXED: added hidden input so handler knows this is a staff submission -->

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
                    <div class="form-group">
                        <label>Resident Name *</label>
                        <input type="text"
                            name="resident_search"
                            class="form-input"
                            placeholder="Search resident by name or ID..."
                            required />
                        <input type="hidden" name="resident_id" />
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
                        <div class="form-group">
                            <label>Facility</label>
                            <select name="facility_id" class="form-select">
                                <option value="">-- Select Facility --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reservation Date *</label>
                            <input type="date"
                                name="reservation_date"
                                class="form-input"
                                min="<?= date('Y-m-d', strtotime('+' . RESERVATION_LEAD_DAYS . ' days')) ?>" />
                        </div>
                        <div class="form-group">
                            <label>Time Slot</label>
                            <select name="time_slot" class="form-select">
                                <option value="Morning (8AM-12PM)">Morning (8AM–12PM)</option>
                                <option value="Afternoon (1PM-5PM)">Afternoon (1PM–5PM)</option>
                                <option value="Full Day">Full Day</option>
                            </select>
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
<script src="/barangay_connect/assets/css/js/form_validation.js"></script>
<script>
    document.getElementById('req-type-select').addEventListener('change', function() {
        document.getElementById('facility-fields').style.display =
            this.value === 'FacilityReservation' ? 'block' : 'none';
        document.getElementById('complaint-fields').style.display =
            this.value === 'Complaint' ? 'block' : 'none';
    });
</script>
<?php include '../includes/footer.php'; ?>