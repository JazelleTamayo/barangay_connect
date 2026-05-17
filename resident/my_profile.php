<?php
// Barangay Connect – My Profile
// resident/my_profile.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$page_title = 'My Profile';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$pdo     = get_db();

// BUG FIX #1: Table names were lowercase ('resident', 'useraccount').
// On Linux/production MySQL, table names are case-sensitive.
// Must match the exact casing used in the CREATE TABLE statements.
$stmt = $pdo->prepare("
    SELECT r.*, ua.Username, ua.AccountStatus, ua.Email AS account_email
    FROM Resident r
    JOIN UserAccount ua ON r.ResidentID = ua.ResidentID
    WHERE ua.UserAccountID = ?
");
$stmt->execute([$user_id]);
$resident = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resident) {
    die("Resident profile not found.");
}

$msg = $_GET['msg'] ?? '';

// BUG FIX #2: Store the raw path; escape only at the output point (src="...").
// Previously htmlspecialchars() was applied inside the variable assignment,
// which is inconsistent and risky if the variable is reused elsewhere.
$profile_pic_path = !empty($resident['ProfilePicture'])
    ? '/BARANGAY_CONNECT/' . $resident['ProfilePicture']
    : null;
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-header">
            <h1>My Profile</h1>
            <span class="page-subtitle">Manage your personal information and account settings</span>
        </div>

        <div class="page-body">

            <!-- ── Flash messages ── -->
            <?php if ($msg === 'updated'): ?>
                <div class="alert alert-success">✅ Profile updated successfully.</div>
            <?php elseif ($msg === 'password_changed'): ?>
                <div class="alert alert-success">✅ Password changed successfully.</div>
            <?php elseif ($msg === 'error'): ?>
                <div class="alert alert-error">An error occurred. Please try again.</div>
            <?php elseif ($msg === 'wrong_password'): ?>
                <div class="alert alert-error">Current password is incorrect.</div>
            <?php elseif ($msg === 'password_mismatch'): ?>
                <div class="alert alert-error">New passwords do not match.</div>
            <?php elseif ($msg === 'missing_password'): ?>
                <div class="alert alert-error">Please fill in all password fields.</div>
            <?php elseif ($msg === 'invalid_email'): ?>
                <div class="alert alert-error">Invalid email address format.</div>
            <?php elseif ($msg === 'invalid_picture'): ?>
                <div class="alert alert-error">Only JPEG and PNG images are allowed.</div>
            <?php elseif ($msg === 'picture_too_large'): ?>
                <div class="alert alert-error">Image must be 2MB or smaller.</div>
            <?php elseif ($msg === 'upload_failed'): ?>
                <div class="alert alert-error">Upload failed. Please try again.</div>
            <?php elseif ($msg === 'update_failed'): ?>
                <div class="alert alert-error">Could not save changes. Please try again.</div>
            <?php endif; ?>

            <!-- ── TOP ROW: Avatar card + Account Info card side by side ── -->
            <div class="profile-top-row">

                <!-- ===== PROFILE PICTURE CARD ===== -->
                <div class="card profile-avatar-card">
                    <div class="card-header">
                        <h3>Profile Picture</h3>
                    </div>

                    <!-- BUG FIX #3: Added hidden input name="action" value="update_picture"
                         so the handler can tell this form apart from the personal info form.
                         Both POST to the same handler; without this, the handler must guess
                         based on $_FILES existing — fragile and order-dependent. -->
                    <form id="avatarForm" method="POST"
                          action="../handlers/profile_update_handler.php"
                          enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_picture">
                        <input type="file" name="profile_picture" id="profile_picture"
                               accept="image/jpeg,image/png,image/jpg" style="display:none;">

                        <div class="avatar-body">
                            <div id="avatarWrapper"
                                 onclick="document.getElementById('profile_picture').click()"
                                 title="Click to change photo"
                                 class="avatar-ring">

                                <?php if ($profile_pic_path): ?>
                                    <!-- BUG FIX #2: htmlspecialchars applied at output point -->
                                    <img id="avatarPreview"
                                         src="<?= htmlspecialchars($profile_pic_path) ?>?v=<?= time() ?>"
                                         alt="Profile" class="avatar-img" style="display:block;">
                                    <div id="avatarInitial" style="display:none;"></div>
                                <?php else: ?>
                                    <div id="avatarInitial" class="avatar-initial">
                                        <?= strtoupper(substr($resident['FirstName'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <img id="avatarPreview" src="" alt="Profile"
                                         class="avatar-img avatar-img--absolute" style="display:none;">
                                <?php endif; ?>

                                <!-- Camera badge -->
                                <div class="avatar-camera-badge">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                         fill="white" viewBox="0 0 24 24">
                                        <path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z"/>
                                        <path d="M9 3 7.17 5H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16
                                                 a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3.17L15 3H9Zm3 14
                                                 a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Name & status -->
                            <div class="avatar-name">
                                <?= htmlspecialchars(trim(
                                    $resident['FirstName'] . ' ' .
                                    ($resident['MiddleName'] ? $resident['MiddleName'] . ' ' : '') .
                                    $resident['LastName']
                                )) ?>
                            </div>
                            <div class="avatar-username">
                                @<?= htmlspecialchars($resident['Username']) ?>
                            </div>
                            <div style="margin-top:6px;">
                                <span class="status-badge status-<?= strtolower($resident['AccountStatus']) ?>">
                                    <?= htmlspecialchars($resident['AccountStatus']) ?>
                                </span>
                            </div>
                            <small class="avatar-hint">
                                Click the photo to change &middot; JPEG or PNG &middot; max 2MB
                            </small>
                        </div>
                    </form>
                </div><!-- /avatar card -->

                <!-- ===== ACCOUNT INFO CARD ===== -->
                <div class="card profile-info-card">
                    <div class="card-header">
                        <h3>Account Information</h3>
                    </div>
                    <div class="info-table-wrap">
                        <table class="info-table">
                            <tr>
                                <th>Username</th>
                                <td>@<?= htmlspecialchars($resident['Username']) ?></td>
                            </tr>
                            <tr>
                                <th>Account Status</th>
                                <td>
                                    <span class="status-badge status-<?= strtolower($resident['AccountStatus']) ?>">
                                        <?= htmlspecialchars($resident['AccountStatus']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Full Name</th>
                                <td><?= htmlspecialchars(trim(
                                    $resident['FirstName'] . ' ' .
                                    ($resident['MiddleName'] ? $resident['MiddleName'] . ' ' : '') .
                                    $resident['LastName']
                                )) ?></td>
                            </tr>
                            <tr>
                                <th>Birthdate</th>
                                <td><?= $resident['Birthdate']
                                    ? date('F d, Y', strtotime($resident['Birthdate']))
                                    : '—' ?></td>
                            </tr>
                            <tr>
                                <th>Sex</th>
                                <td><?= htmlspecialchars($resident['Sex'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td><?= htmlspecialchars($resident['Address'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Purok / Sitio</th>
                                <td><?= htmlspecialchars($resident['Purok'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Contact No.</th>
                                <td><?= htmlspecialchars($resident['ContactNumber'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?= htmlspecialchars($resident['Email'] ?? '—') ?></td>
                            </tr>
                        </table>
                        <p class="info-note">
                            ℹ️ To update your name, birthdate, sex, or address, please visit the
                            barangay office in person.
                        </p>
                    </div>
                </div><!-- /account info card -->

            </div><!-- /profile-top-row -->


            <!-- ===== EDIT CONTACT & EMAIL CARD ===== -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Update Contact Details</h3>
                </div>

                <!-- BUG FIX #3: Added hidden action field so the handler knows this is
                     the personal-info form, not the avatar form. -->
                <!-- BUG FIX #4: Removed 'required' from contact and email.
                     These fields are nullable in the DB and optional during walk-in
                     registration. A resident encoded without a contact number would be
                     permanently unable to save their profile if these stay required. -->
                <form method="POST" action="../handlers/profile_update_handler.php"
                      class="form-grid validate-form">
                    <input type="hidden" name="action" value="update_info">

                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <input type="text" id="contact" name="contact" class="form-input"
                               value="<?= htmlspecialchars($resident['ContactNumber'] ?? '') ?>"
                               placeholder="09XXXXXXXXX"
                               pattern="^(09|\+639)\d{9}$"
                               title="Enter a valid Philippine mobile number (e.g. 09XXXXXXXXX)">
                        <span class="field-hint">Philippine mobile number format (09XXXXXXXXX)</span>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input"
                               value="<?= htmlspecialchars($resident['Email'] ?? '') ?>"
                               placeholder="you@example.com">
                        <span class="field-hint">Used for account notifications</span>
                    </div>

                    <div class="form-group form-full">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>


            <!-- ===== CHANGE PASSWORD CARD ===== -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Change Password</h3>
                </div>
                <form method="POST" action="../handlers/password_change_handler.php"
                      class="form-vertical validate-form" style="padding: 20px 24px;">

                    <div class="form-group">
                        <label for="current_password">
                            Current Password <span class="req">*</span>
                        </label>
                        <div class="input-wrap">
                            <input type="password" id="current_password"
                                   name="current_password" class="form-input pw-input"
                                   placeholder="Enter your current password" required>
                            <button type="button" class="input-toggle"
                                    onclick="togglePassword(this)" tabindex="-1">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">
                            New Password <span class="req">*</span>
                        </label>
                        <div class="input-wrap">
                            <input type="password" id="new_password"
                                   name="password" class="form-input pw-input"
                                   placeholder="Min. 8 characters" minlength="8" required>
                            <button type="button" class="input-toggle"
                                    onclick="togglePassword(this)" tabindex="-1">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            Confirm New Password <span class="req">*</span>
                        </label>
                        <div class="input-wrap">
                            <input type="password" id="confirm_password"
                                   name="confirm_password" class="form-input pw-input"
                                   placeholder="Re-enter your new password" minlength="8" required>
                            <button type="button" class="input-toggle"
                                    onclick="togglePassword(this)" tabindex="-1">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions" style="padding-top:4px;">
                        <button type="submit" class="btn btn-primary">🔒 Update Password</button>
                    </div>
                </form>
            </div>

        </div><!-- /page-body -->
    </main>
</div>

<!-- ── Profile-specific styles ── -->
<style>
/* Top row: avatar + account info side by side */
.profile-top-row {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 720px) {
    .profile-top-row { grid-template-columns: 1fr; }
}

/* Avatar card */
.avatar-body {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 24px 20px;
}
.avatar-ring {
    position: relative;
    cursor: pointer;
    width: 110px;
    height: 110px;
    flex-shrink: 0;
}
.avatar-img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e2e8f0;
}
.avatar-img--absolute {
    position: absolute;
    top: 0;
    left: 0;
}
.avatar-initial {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: #e8edf4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 44px;
    font-weight: 600;
    color: #2c3e50;
    border: 3px solid #e2e8f0;
}
.avatar-camera-badge {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #3b82f6;
    border: 2px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.18);
}
.avatar-name {
    font-size: 1.05rem;
    font-weight: 600;
    color: #1a202c;
    text-align: center;
}
.avatar-username {
    font-size: 0.85rem;
    color: #64748b;
}
.avatar-hint {
    color: #94a3b8;
    font-size: 0.76rem;
    text-align: center;
    line-height: 1.5;
}

/* Account info table */
.info-table-wrap { padding: 4px 0 16px; }
.info-table { width: 100%; border-collapse: collapse; }
.info-table th,
.info-table td {
    padding: 10px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
    text-align: left;
    vertical-align: top;
}
.info-table th {
    width: 38%;
    color: #64748b;
    font-weight: 600;
    background: #f8fafc;
}
.info-table td { color: #1e293b; }
.info-table tr:last-child th,
.info-table tr:last-child td { border-bottom: none; }
.info-note {
    margin: 12px 16px 0;
    font-size: 0.8rem;
    color: #94a3b8;
    background: #f8fafc;
    border-left: 3px solid #cbd5e1;
    padding: 8px 12px;
    border-radius: 0 6px 6px 0;
}

/* Edit contact form */
.field-hint {
    display: block;
    font-size: 0.76rem;
    color: #94a3b8;
    margin-top: 4px;
}

/* Required star */
.req { color: #e53e3e; }

/* Spacing */
.mt-4 { margin-top: 20px; }
</style>

<script>
// Avatar file picker → preview → auto-submit
document.getElementById('profile_picture').addEventListener('change', function () {
    if (!this.files || !this.files[0]) return;

    const file = this.files[0];
    if (file.size > 2 * 1024 * 1024) {
        alert('File is too large. Maximum allowed size is 2MB.');
        this.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        const preview = document.getElementById('avatarPreview');
        const initial = document.getElementById('avatarInitial');
        if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
        if (initial) { initial.style.display = 'none'; }
        setTimeout(function () {
            document.getElementById('avatarForm').submit();
        }, 500);
    };
    reader.readAsDataURL(file);
});
</script>

<script src="/BARANGAY_CONNECT/assets/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>