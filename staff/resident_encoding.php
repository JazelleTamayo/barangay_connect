<?php
// Barangay Connect – Resident Encoding
// staff/resident_encoding.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$page_title = 'Encode Resident';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Encode Resident</h1>
            <span class="page-subtitle">Register a new resident in the system</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
                <div class="alert alert-success">✅ Resident record saved successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'duplicate'): ?>
                <div class="alert alert-error">❌ A resident with the same name, birthdate, and address already exists.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>New Resident Registration</h3>
                    <p class="card-desc">
                        All fields marked * are required. Records will be reviewed
                        by the Barangay Secretary.
                    </p>
                </div>
                <form method="POST"
                    action="../handlers/resident_save_handler.php"
                    class="form-grid validate-form">

                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text"
                            name="first_name"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text"
                            name="middle_name"
                            class="form-input" />
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text"
                            name="last_name"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Birthdate *</label>
                        <input type="date"
                            name="birthdate"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Sex *</label>
                        <select name="sex" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text"
                            name="contact"
                            class="form-input"
                            placeholder="09XXXXXXXXX" />
                    </div>
                    <div class="form-group form-full">
                        <label>Complete Address *</label>
                        <input type="text"
                            name="address"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Purok / Sitio</label>
                        <input type="text"
                            name="purok"
                            class="form-input" />
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email"
                            name="email"
                            class="form-input" />
                    </div>
                    <div class="form-group">
                        <label>Government ID Type</label>
                        <select name="gov_id_type" class="form-select">
                            <option value="">-- Select --</option>
                            <option value="PhilSys ID">PhilSys ID</option>
                            <option value="Voter's ID">Voter's ID</option>
                            <option value="Driver's License">Driver's License</option>
                            <option value="Passport">Passport</option>
                            <option value="SSS ID">SSS ID</option>
                            <option value="GSIS ID">GSIS ID</option>
                            <option value="PhilHealth ID">PhilHealth ID</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Government ID Number</label>
                        <input type="text"
                            name="gov_id_number"
                            class="form-input" />
                    </div>
                    <div class="form-group form-full">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Resident</button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </main>
</div>
<script src="/barangay_connect/assets/css/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>