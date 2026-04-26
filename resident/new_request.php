<?php
// Barangay Connect – New Request
// resident/new_request.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$page_title = 'New Request';
$type = $_GET['type'] ?? '';
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

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
                <div class="alert alert-success">
                    ✅ Request submitted successfully. Check your dashboard for the reference number.
                </div>
            <?php endif; ?>

            <!-- Request Type Selection -->
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
                        <h3>Submit: <?= htmlspecialchars($type) ?></h3>
                    </div>
                    <form method="POST"
                        action="../handlers/request_create_handler.php"
                        class="form-vertical validate-form">
                        <input type="hidden" name="request_type"
                            value="<?= htmlspecialchars($type) ?>" />
                        <input type="hidden" name="submitted_by" value="resident" />

                        <div class="form-group">
                            <label>Purpose / Description *</label>
                            <textarea name="purpose"
                                rows="4"
                                class="form-textarea"
                                required
                                placeholder="Briefly state your purpose..."></textarea>
                        </div>

                        <?php if ($type === 'FacilityReservation'): ?>
                            <div class="form-group">
                                <label>Preferred Date *
                                    <small class="form-hint">
                                        Must be at least 3 working days from today.
                                    </small>
                                </label>
                                <input type="date"
                                    name="reservation_date"
                                    class="form-input"
                                    min="<?= date('Y-m-d', strtotime('+' . RESERVATION_LEAD_DAYS . ' days')) ?>"
                                    required />
                            </div>
                            <div class="form-group">
                                <label>Time Slot</label>
                                <select name="time_slot" class="form-select">
                                    <option value="Morning (8AM-12PM)">Morning (8AM–12PM)</option>
                                    <option value="Afternoon (1PM-5PM)">Afternoon (1PM–5PM)</option>
                                    <option value="Full Day">Full Day</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($type === 'Complaint'): ?>
                            <div class="form-group">
                                <label>Respondent Name *</label>
                                <input type="text"
                                    name="respondent_name"
                                    class="form-input"
                                    required
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
                                    class="form-input"
                                    required />
                            </div>
                            <div class="form-group">
                                <label>Incident Location *</label>
                                <input type="text"
                                    name="incident_location"
                                    class="form-input"
                                    required
                                    placeholder="Where did the incident happen?" />
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Submit Request
                            </button>
                            <a href="new_request.php" class="btn btn-secondary">
                                Back
                            </a>
                        </div>

                    </form>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>
<script src="/barangay_connect/assets/css/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>