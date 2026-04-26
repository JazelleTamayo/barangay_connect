<?php
// Barangay Connect – System Settings
// sysadmin/system_settings.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');
require_once '../config/settings.php';
$settings = load_settings();

$page_title = 'System Settings';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>System Settings</h1>
            <span class="page-subtitle">Configure application settings</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
                <div class="alert alert-success">✅ Settings saved successfully.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Application Settings</h3>
                </div>
                <form method="POST"
                    action="../handlers/settings_handler.php"
                    class="form-grid validate-form">

                    <div class="form-group">
                        <label>Barangay Name *</label>
                        <input type="text"
                            name="brgy_name"
                            class="form-input"
                            value="<?= htmlspecialchars($settings['barangay_name']) ?>"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Municipality / City</label>
                        <input type="text"
                            name="municipality"
                            class="form-input"
                            value="<?= htmlspecialchars($settings['municipality']) ?>" />
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text"
                            name="contact"
                            class="form-input"
                            value="<?= htmlspecialchars($settings['contact']) ?>"
                            placeholder="09XXXXXXXXX" />
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email"
                            name="email"
                            class="form-input"
                            value="<?= htmlspecialchars($settings['email']) ?>" />
                    </div>
                    <div class="form-group">
                        <label>Clearance Fee (₱)</label>
                        <input type="number"
                            name="clearance_fee"
                            class="form-input"
                            step="0.01"
                            min="0"
                            value="<?= htmlspecialchars($settings['clearance_fee']) ?>" />
                    </div>
                    <div class="form-group">
                        <label>Maintenance Mode</label>
                        <select name="maintenance" class="form-select">
                            <option value="0" <?= $settings['maintenance_mode'] === '0' ? 'selected' : '' ?>>Off</option>
                            <option value="1" <?= $settings['maintenance_mode'] === '1' ? 'selected' : '' ?>>On</option>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
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