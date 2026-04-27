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

$stmt = $pdo->prepare("
    SELECT r.*, ua.Username, ua.AccountStatus, ua.Email AS account_email
    FROM resident r
    JOIN useraccount ua ON r.ResidentID = ua.ResidentID
    WHERE ua.UserAccountID = ?
");
$stmt->execute([$user_id]);
$resident = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resident) {
    die("Resident profile not found.");
}

$msg = $_GET['msg'] ?? '';

// Build correct web path for profile picture
// Stored as: uploads/profile_pictures/filename.jpg
// Served as: /BARANGAY_CONNECT/uploads/profile_pictures/filename.jpg
$profile_pic_url = !empty($resident['ProfilePicture'])
    ? '/BARANGAY_CONNECT/' . htmlspecialchars($resident['ProfilePicture'])
    : null;
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>My Profile</h1>
            <span class="page-subtitle">View and update your information</span>
        </div>
        <div class="page-body">

            <?php if ($msg === 'updated'): ?>
                <div class="alert alert-success">✅ Profile updated successfully.</div>
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
            <?php elseif ($msg === 'password_changed'): ?>
                <div class="alert alert-success">✅ Password changed successfully.</div>
            <?php endif; ?>

            <!-- ===== PROFILE PICTURE CARD ===== -->
            <div class="card">
                <div class="card-header">
                    <h3>Profile Picture</h3>
                </div>

                <form id="avatarForm" method="POST"
                      action="../handlers/profile_update_handler.php"
                      enctype="multipart/form-data">
                    <input type="file" name="profile_picture" id="profile_picture"
                           accept="image/jpeg,image/png,image/jpg" style="display:none;">

                    <div style="padding:28px; display:flex; flex-direction:column; align-items:center; gap:14px;">

                        <div id="avatarWrapper"
                             onclick="document.getElementById('profile_picture').click()"
                             title="Click to change photo"
                             style="position:relative; cursor:pointer; width:110px; height:110px; flex-shrink:0;">

                            <?php if ($profile_pic_url): ?>
                                <!-- Has saved profile picture -->
                                <img id="avatarPreview"
                                     src="<?= $profile_pic_url ?>?v=<?= time() ?>"
                                     alt="Profile"
                                     style="width:110px; height:110px; border-radius:50%;
                                            object-fit:cover; border:3px solid #e2e8f0; display:block;">
                                <div id="avatarInitial" style="display:none;"></div>
                            <?php else: ?>
                                <!-- No picture — show initial -->
                                <div id="avatarInitial"
                                     style="width:110px; height:110px; border-radius:50%;
                                            background:#e8edf4; display:flex; align-items:center;
                                            justify-content:center; font-size:44px; font-weight:600;
                                            color:#2c3e50; border:3px solid #e2e8f0;">
                                    <?= strtoupper(substr($resident['FirstName'] ?? 'U', 0, 1)) ?>
                                </div>
                                <img id="avatarPreview" src="" alt="Profile"
                                     style="width:110px; height:110px; border-radius:50%;
                                            object-fit:cover; border:3px solid #e2e8f0;
                                            display:none; position:absolute; top:0; left:0;">
                            <?php endif; ?>

                            <!-- Camera badge -->
                            <div style="position:absolute; bottom:4px; right:4px;
                                        width:30px; height:30px; border-radius:50%;
                                        background:#3b82f6; border:2px solid #fff;
                                        display:flex; align-items:center; justify-content:center;
                                        box-shadow:0 1px 4px rgba(0,0,0,0.18);">
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
                        <div style="text-align:center;">
                            <div style="font-size:1.2rem; font-weight:600; color:#1a202c;">
                                <?= htmlspecialchars(trim(
                                    $resident['FirstName'] . ' ' .
                                    ($resident['MiddleName'] ? $resident['MiddleName'] . ' ' : '') .
                                    $resident['LastName']
                                )) ?>
                            </div>
                            <div style="font-size:0.875rem; color:#64748b; margin-top:2px;">
                                @<?= htmlspecialchars($resident['Username']) ?>
                            </div>
                            <div style="margin-top:6px;">
                                <span class="status-badge status-<?= strtolower($resident['AccountStatus']) ?>">
                                    <?= htmlspecialchars($resident['AccountStatus']) ?>
                                </span>
                            </div>
                        </div>

                        <small style="color:#94a3b8; font-size:0.78rem;">
                            Click the photo to change &middot; JPEG or PNG &middot; max 2MB
                        </small>
                    </div>
                </form>
            </div>

            <script>
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
                    if (preview) {
                        preview.src    = e.target.result;
                        preview.style.display = 'block';
                    }
                    if (initial) initial.style.display = 'none';
                    // Short delay so the preview is visible before submit
                    setTimeout(function () {
                        document.getElementById('avatarForm').submit();
                    }, 500);
                };
                reader.readAsDataURL(file);
            });
            </script>

            <!-- ===== PERSONAL INFO CARD ===== -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Personal Information</h3>
                </div>
                <form method="POST" action="../handlers/profile_update_handler.php"
                      class="form-grid validate-form">

                    <!-- Read-only -->
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" class="form-input"
                               value="<?= htmlspecialchars($resident['FirstName']) ?>"
                               readonly disabled>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" class="form-input"
                               value="<?= htmlspecialchars($resident['MiddleName'] ?? '') ?>"
                               readonly disabled>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" class="form-input"
                               value="<?= htmlspecialchars($resident['LastName']) ?>"
                               readonly disabled>
                    </div>
                    <div class="form-group">
                        <label>Birthdate</label>
                        <input type="date" class="form-input"
                               value="<?= htmlspecialchars($resident['Birthdate'] ?? '') ?>"
                               readonly disabled>
                    </div>
                    <div class="form-group">
                        <label>Sex</label>
                        <input type="text" class="form-input"
                               value="<?= htmlspecialchars($resident['Sex'] ?? '') ?>"
                               readonly disabled>
                    </div>
                    <div class="form-group form-full">
                        <label>Complete Address</label>
                        <input type="text" class="form-input"
                               value="<?= htmlspecialchars($resident['Address'] ?? '') ?>"
                               readonly disabled>
                    </div>
                    <div class="form-group">
                        <label>Purok / Sitio</label>
                        <input type="text" class="form-input"
                               value="<?= htmlspecialchars($resident['Purok'] ?? '') ?>"
                               readonly disabled>
                    </div>

                    <div class="form-group form-full">
                        <hr style="border:none; border-top:1px solid #e2e8f0; margin:4px 0;">
                        <p style="font-size:0.8rem; color:#94a3b8; margin:8px 0 0;">
                            Only Contact Number and Email can be updated here.
                            For name or address changes, please visit the barangay office.
                        </p>
                    </div>

                    <!-- Editable -->
                    <div class="form-group">
                        <label>Contact Number <span style="color:#e53e3e;">*</span></label>
                        <input type="text" name="contact" class="form-input"
                               value="<?= htmlspecialchars($resident['ContactNumber'] ?? '') ?>"
                               placeholder="09XXXXXXXXX"
                               pattern="^(09|\+639)\d{9}$"
                               title="Enter a valid Philippine mobile number (e.g. 09XXXXXXXXX)"
                               required>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="color:#e53e3e;">*</span></label>
                        <input type="email" name="email" class="form-input"
                               value="<?= htmlspecialchars($resident['Email'] ?? '') ?>"
                               placeholder="you@example.com"
                               required>
                    </div>

                    <div class="form-group form-full">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
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
                      class="form-vertical validate-form">
                    <div class="form-group">
                        <label>Current Password <span style="color:#e53e3e;">*</span></label>
                        <input type="password" name="current_password" class="form-input"
                               placeholder="Enter current password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password <span style="color:#e53e3e;">*</span></label>
                        <input type="password" name="password" class="form-input"
                               placeholder="Min. 8 characters" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password <span style="color:#e53e3e;">*</span></label>
                        <input type="password" name="confirm_password" class="form-input"
                               placeholder="Re-enter new password" minlength="8" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>

        </div><!-- /page-body -->
    </main>
</div>
<script src="/BARANGAY_CONNECT/assets/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>