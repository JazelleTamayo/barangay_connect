<?php
// Barangay Connect – Resident Edit
// secretary/resident_edit.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../classes/Resident.php';
require_once '../classes/AuditLog.php';
require_role('secretary');

$residentClass = new Resident();

// Get resident ID from URL
$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: resident_management.php');
    exit;
}

$resident = $residentClass->getById($id);
if (!$resident) {
    header('Location: resident_management.php?msg=not_found');
    exit;
}

$page_title = 'Edit Resident';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-header">
            <div>
                <h1>Edit Resident</h1>
                <span class="page-subtitle">
                    Updating record for
                    <?= htmlspecialchars(trim($resident['FirstName'] . ' ' . $resident['LastName'])) ?>
                </span>
            </div>
            <a href="resident_management.php" class="btn btn-secondary btn-small">← Back to Residents</a>
        </div>

        <div class="page-body">

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'updated'): ?>
                    <div class="alert alert-success">✅ Resident record updated successfully.</div>
                <?php elseif ($_GET['msg'] === 'duplicate'): ?>
                    <div class="alert alert-danger">❌ Another resident with the same name, birthdate, and address already exists.</div>
                <?php elseif ($_GET['msg'] === 'missing_fields'): ?>
                    <div class="alert alert-danger">❌ Please fill in all required fields.</div>
                <?php elseif ($_GET['msg'] === 'error'): ?>
                    <div class="alert alert-danger">❌ Something went wrong. Please try again.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Resident Information</h3>
                    <span class="page-subtitle">Fields marked * are required</span>
                </div>

                <form method="POST" action="../handlers/resident_edit_handler.php">
                    <input type="hidden" name="resident_id" value="<?= (int) $resident['ResidentID'] ?>">

                    <div class="form-grid">

                        <!-- Name row -->
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text"
                                id="first_name"
                                name="first_name"
                                class="form-input"
                                value="<?= htmlspecialchars($resident['FirstName']) ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text"
                                id="middle_name"
                                name="middle_name"
                                class="form-input"
                                value="<?= htmlspecialchars($resident['MiddleName'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text"
                                id="last_name"
                                name="last_name"
                                class="form-input"
                                value="<?= htmlspecialchars($resident['LastName']) ?>"
                                required>
                        </div>

                        <!-- Birthdate & Sex -->
                        <div class="form-group">
                            <label for="birthdate">Birthdate *</label>
                            <input type="date"
                                id="birthdate"
                                name="birthdate"
                                class="form-input"
                                value="<?= htmlspecialchars($resident['Birthdate']) ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="sex">Sex *</label>
                            <select id="sex" name="sex" class="form-select" required>
                                <option value="Male"   <?= $resident['Sex'] === 'Male'   ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $resident['Sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <!-- Address & Purok -->
                        <div class="form-group form-full">
                            <label for="address">Address *</label>
                            <input type="text"
                                id="address"
                                name="address"
                                class="form-input"
                                value="<?= htmlspecialchars($resident['Address']) ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="purok">Purok</label>
                            <input type="text"
                                id="purok"
                                name="purok"
                                class="form-input"
                                value="<?= htmlspecialchars($resident['Purok'] ?? '') ?>">
                        </div>

                        <!-- Contact & Email -->
                        <div class="form-group">
                            <label for="contact">Contact Number</label>
                            <input type="text"
                                id="contact"
                                name="contact"
                                class="form-input"
                                value="<?= htmlspecialchars($resident['ContactNumber'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email"
                                id="email"
                                name="email"
                                class="form-input"
                                value="<?= htmlspecialchars($resident['Email'] ?? '') ?>">
                        </div>

                        <!-- Gov ID -->
                        <div class="form-group">
                            <label for="gov_id_type">Gov ID Type</label>
                            <input type="text"
                                id="gov_id_type"
                                name="gov_id_type"
                                class="form-input"
                                placeholder="e.g. PhilSys, Driver's License"
                                value="<?= htmlspecialchars($resident['GovIDType'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="gov_id_number">Gov ID Number</label>
                            <input type="text"
                                id="gov_id_number"
                                name="gov_id_number"
                                class="form-input"
                                value="<?= htmlspecialchars($resident['GovIDNumber'] ?? '') ?>">
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="Active"   <?= ($resident['Status'] ?? '') === 'Active'   ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= ($resident['Status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <span class="form-hint">Setting to Inactive will not delete the resident record.</span>
                        </div>

                        <!-- Actions -->
                        <div class="form-group form-full">
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="resident_management.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            <!-- Read-only info block -->
            <div class="card" style="margin-top: 1rem;">
                <div class="card-header">
                    <h3>Record Info</h3>
                </div>
                <div class="form-grid" style="padding: 20px 24px;">
                    <div class="form-group">
                        <label>Resident ID</label>
                        <p style="margin:0; font-size:0.92rem; color:var(--text-dark);">
                            <?= (int) $resident['ResidentID'] ?>
                        </p>
                    </div>
                    <div class="form-group">
                        <label>Date Registered</label>
                        <p style="margin:0; font-size:0.92rem; color:var(--text-dark);">
                            <?= htmlspecialchars($resident['CreatedAt'] ?? '—') ?>
                        </p>
                    </div>
                    <div class="form-group">
                        <label>Last Updated</label>
                        <p style="margin:0; font-size:0.92rem; color:var(--text-dark);">
                            <?= htmlspecialchars($resident['UpdatedAt'] ?? '—') ?>
                        </p>
                    </div>
                    <?php if (!empty($resident['GovIDImagePath'])): ?>
                    <div class="form-group">
                        <label>Gov ID Image on File</label>
                        <a href="/BARANGAY_CONNECT/<?= htmlspecialchars($resident['GovIDImagePath']) ?>"
                           target="_blank"
                           class="btn btn-secondary btn-small"
                           style="width:fit-content;">View ID Image</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>
