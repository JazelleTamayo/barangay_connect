<?php
// Barangay Connect – Facility Management
// secretary/facility_management.php
//
// Implements FDD 3.1: Secretary can add, edit, or deactivate facility records.
// Fields: FacilityName, Description, Capacity, ReservationFee, Status (Active/Inactive).
// Uses facility_save_handler.php for all POST actions.
// CSRF-protected on all forms.

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../classes/Facility.php';
require_role('secretary');

$pdo          = get_db();
$facilityClass = new Facility();

// Load all facilities (no status filter — show both Active and Inactive)
$facilities = $facilityClass->getAll('');

// Pre-fill edit form if ?edit=ID is in URL
$editing = null;
if (!empty($_GET['edit'])) {
    $editing = $facilityClass->getById((int) $_GET['edit']);
}

$page_title = 'Facility Management';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-body">

            <?php
            $msg = $_GET['msg'] ?? '';
            if ($msg === 'facility_saved'):   ?>
                <div class="alert alert-success">✅ Facility saved successfully.</div>
            <?php elseif ($msg === 'missing_fields'): ?>
                <div class="alert alert-danger">❌ Facility name is required.</div>
            <?php elseif ($msg === 'deactivated'): ?>
                <div class="alert alert-success">✅ Facility deactivated.</div>
            <?php elseif ($msg === 'activated'): ?>
                <div class="alert alert-success">✅ Facility reactivated.</div>
            <?php endif; ?>

            <div style="display:flex; gap:24px; align-items:flex-start; flex-wrap:wrap;">

                <!-- ── LEFT: Facility List ─────────────────────────────── -->
                <div class="card" style="flex:1; min-width:320px;">
                    <div class="card-header">
                        <h3>Facilities
                            <span style="font-size:0.82rem;font-weight:400;color:var(--text-light);margin-left:8px;">
                                (<?= count($facilities) ?> total)
                            </span>
                        </h3>
                        <div class="card-actions">
                            <a href="facility_management.php" class="btn btn-primary btn-small">+ Add New Facility</a>
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Facility Name</th>
                                <th>Capacity</th>
                                <th>Fee (PHP)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($facilities)): ?>
                                <tr>
                                    <td colspan="5" class="empty-row">No facilities found. Add one using the form.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($facilities as $f): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($f['FacilityName']) ?></strong>
                                            <?php if (!empty($f['Description'])): ?>
                                                <br><span style="font-size:0.8rem;color:var(--text-light);">
                                                    <?= htmlspecialchars(mb_strimwidth($f['Description'], 0, 60, '…')) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $f['Capacity'] ? number_format((int)$f['Capacity']) : '—' ?></td>
                                        <td><?= number_format((float)($f['ReservationFee'] ?? 0), 2) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $f['Status'] === 'Active' ? 'active' : 'inactive' ?>">
                                                <?= htmlspecialchars($f['Status']) ?>
                                            </span>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <!-- Edit -->
                                            <a href="facility_management.php?edit=<?= (int)$f['FacilityID'] ?>"
                                               class="btn btn-small btn-secondary">✏️ Edit</a>

                                            <!-- Deactivate / Reactivate -->
                                            <form method="POST"
                                                  action="../handlers/facility_save_handler.php"
                                                  style="display:inline;"
                                                  onsubmit="return confirm('<?= $f['Status'] === 'Active'
                                                      ? 'Deactivate this facility? Existing approved reservations are not affected.'
                                                      : 'Reactivate this facility?' ?>')">
                                                <input type="hidden" name="csrf_token"
                                                       value="<?= htmlspecialchars(csrf_generate()) ?>">
                                                <input type="hidden" name="facility_id"
                                                       value="<?= (int)$f['FacilityID'] ?>">
                                                <input type="hidden" name="facility_name"
                                                       value="<?= htmlspecialchars($f['FacilityName']) ?>">
                                                <input type="hidden" name="capacity"
                                                       value="<?= (int)($f['Capacity'] ?? 0) ?>">
                                                <input type="hidden" name="reservation_fee"
                                                       value="<?= (float)($f['ReservationFee'] ?? 0) ?>">
                                                <input type="hidden" name="description"
                                                       value="<?= htmlspecialchars($f['Description'] ?? '') ?>">
                                                <input type="hidden" name="status"
                                                       value="<?= $f['Status'] === 'Active' ? 'Inactive' : 'Active' ?>">
                                                <input type="hidden" name="redirect_msg"
                                                       value="<?= $f['Status'] === 'Active' ? 'deactivated' : 'activated' ?>">
                                                <button type="submit"
                                                        class="btn btn-small <?= $f['Status'] === 'Active' ? 'btn-danger' : 'btn-secondary' ?>">
                                                    <?= $f['Status'] === 'Active' ? '🔴 Deactivate' : '🟢 Reactivate' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ── RIGHT: Add / Edit Form ──────────────────────────── -->
                <div class="card" style="width:360px; flex-shrink:0;">
                    <div class="card-header">
                        <h3><?= $editing ? '✏️ Edit Facility' : '➕ Add Facility' ?></h3>
                        <?php if ($editing): ?>
                            <a href="facility_management.php"
                               class="btn btn-small btn-secondary">← New</a>
                        <?php endif; ?>
                    </div>

                    <form method="POST"
                          action="../handlers/facility_save_handler.php"
                          style="padding:20px 24px;">

                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars(csrf_generate()) ?>">

                        <?php if ($editing): ?>
                            <input type="hidden" name="facility_id"
                                   value="<?= (int)$editing['FacilityID'] ?>">
                        <?php endif; ?>

                        <!-- Facility Name -->
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="facility_name"
                                   style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem;">
                                Facility Name <span style="color:var(--danger);">*</span>
                            </label>
                            <input type="text"
                                   id="facility_name"
                                   name="facility_name"
                                   class="form-input"
                                   style="width:100%;box-sizing:border-box;"
                                   placeholder="e.g. Barangay Hall"
                                   value="<?= htmlspecialchars($editing['FacilityName'] ?? '') ?>"
                                   required>
                        </div>

                        <!-- Description -->
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="description"
                                   style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem;">
                                Description
                            </label>
                            <textarea id="description"
                                      name="description"
                                      class="form-input"
                                      rows="3"
                                      style="width:100%;box-sizing:border-box;resize:vertical;"
                                      placeholder="Brief description of the facility..."><?= htmlspecialchars($editing['Description'] ?? '') ?></textarea>
                        </div>

                        <!-- Capacity -->
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="capacity"
                                   style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem;">
                                Capacity (persons)
                            </label>
                            <input type="number"
                                   id="capacity"
                                   name="capacity"
                                   class="form-input"
                                   style="width:100%;box-sizing:border-box;"
                                   min="0"
                                   placeholder="e.g. 200"
                                   value="<?= (int)($editing['Capacity'] ?? 0) ?: '' ?>">
                        </div>

                        <!-- Reservation Fee -->
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="reservation_fee"
                                   style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem;">
                                Reservation Fee (PHP)
                            </label>
                            <input type="number"
                                   id="reservation_fee"
                                   name="reservation_fee"
                                   class="form-input"
                                   style="width:100%;box-sizing:border-box;"
                                   min="0"
                                   step="0.01"
                                   placeholder="0.00"
                                   value="<?= number_format((float)($editing['ReservationFee'] ?? 0), 2, '.', '') ?>">
                        </div>

                        <!-- Status (edit only) -->
                        <?php if ($editing): ?>
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="status"
                                   style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem;">
                                Status
                            </label>
                            <select id="status"
                                    name="status"
                                    class="form-input"
                                    style="width:100%;box-sizing:border-box;">
                                <option value="Active"
                                    <?= ($editing['Status'] ?? '') === 'Active' ? 'selected' : '' ?>>
                                    Active
                                </option>
                                <option value="Inactive"
                                    <?= ($editing['Status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>
                                    Inactive
                                </option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div style="display:flex;gap:10px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary" style="flex:1;">
                                <?= $editing ? '💾 Save Changes' : '➕ Add Facility' ?>
                            </button>
                            <?php if ($editing): ?>
                                <a href="facility_management.php"
                                   class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>

                    </form>
                </div>

            </div><!-- end flex row -->

        </div><!-- page-body -->
    </main>
</div>
<?php include '../includes/footer.php'; ?>