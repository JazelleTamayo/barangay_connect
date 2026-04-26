<?php
// Barangay Connect – My Profile
// resident/my_profile.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$page_title = 'My Profile';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>My Profile</h1>
            <span class="page-subtitle">View and update your profile</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">✅ Profile updated successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'wrong_password'): ?>
                <div class="alert alert-error">❌ Current password is incorrect.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'password_changed'): ?>
                <div class="alert alert-success">✅ Password changed successfully.</div>
            <?php endif; ?>

            <!-- Profile Info -->
            <div class="card">
                <div class="card-header">
                    <h3>Profile Information</h3>
                </div>
                <div style="padding: 24px; display: flex; align-items: center; gap: 20px;">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="profile-name">
                            <?= htmlspecialchars($_SESSION['full_name'] ?? '—') ?>
                        </div>
                        <div class="profile-role">
                            <?= htmlspecialchars($_SESSION['username'] ?? '—') ?>
                        </div>
                    </div>
                </div>
                <div class="profile-info">
                    <div class="profile-row">
                        <span class="profile-label">Full Name</span>
                        <span class="profile-value">
                            <?= htmlspecialchars($_SESSION['full_name'] ?? '—') ?>
                        </span>
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Username</span>
                        <span class="profile-value">
                            <?= htmlspecialchars($_SESSION['username'] ?? '—') ?>
                        </span>
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Account Status</span>
                        <span class="profile-value">
                            <span class="status-badge status-active">
                                <?= htmlspecialchars($_SESSION['account_status'] ?? '—') ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Change Password</h3>
                </div>
                <form method="POST"
                    action="../handlers/password_change_handler.php"
                    class="form-vertical validate-form">
                    <div class="form-group">
                        <label>Current Password *</label>
                        <input type="password"
                            name="current_password"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>New Password * (min. 8 characters)</label>
                        <input type="password"
                            name="password"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password *</label>
                        <input type="password"
                            name="confirm_password"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>
<script src="/barangay_connect/assets/css/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>