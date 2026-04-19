<?php
// Barangay Connect – User Account Management
// sysadmin/user_accounts.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('sysadmin');

$page_title = 'User Account Management';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>User Account Management</h1>
            <span class="page-subtitle">Create, disable, and assign roles to accounts</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
                <div class="alert alert-success">✅ Account created successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'disabled'): ?>
                <div class="alert alert-warning">⚠️ Account has been disabled.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'enabled'): ?>
                <div class="alert alert-success">✅ Account has been enabled.</div>
            <?php endif; ?>

            <!-- Account List -->
            <div class="card">
                <div class="card-header">
                    <h3>All User Accounts</h3>
                    <div class="card-actions">
                        <input type="text"
                            class="search-input"
                            placeholder="Search by username or name..." />
                        <select class="filter-select">
                            <option value="">All Roles</option>
                            <option value="captain">Captain</option>
                            <option value="secretary">Secretary</option>
                            <option value="staff">Staff</option>
                            <option value="sysadmin">Sysadmin</option>
                            <option value="resident">Resident</option>
                        </select>
                        <select class="filter-select">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="PendingVerification">Pending</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                        <button class="btn btn-primary btn-small"
                            onclick="document.getElementById('create-form').style.display='block';
                                     this.style.display='none'">
                            + New Account
                        </button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="empty-row">No accounts found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Create Account Form -->
            <div class="card mt-4" id="create-form" style="display:none;">
                <div class="card-header">
                    <h3>Create New Account</h3>
                </div>
                <form method="POST"
                    action="../handlers/user_account_handler.php"
                    class="form-grid validate-form">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text"
                            name="username"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text"
                            name="full_name"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password"
                            name="password"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password"
                            name="confirm_password"
                            class="form-input"
                            required />
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Select Role --</option>
                            <option value="captain">Barangay Captain</option>
                            <option value="secretary">Barangay Secretary</option>
                            <option value="staff">Barangay Staff</option>
                            <option value="sysadmin">System Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email"
                            name="email"
                            class="form-input" />
                    </div>
                    <div class="form-group form-full">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Create Account</button>
                            <button type="button"
                                class="btn btn-secondary"
                                onclick="document.getElementById('create-form').style.display='none'">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>
<script src="/BARANGAY_CONNECT/assets/js/form_validation.js"></script>
<?php include '../includes/footer.php'; ?>