<?php
// Barangay Connect – Resident Management
// secretary/resident_management.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$page_title = 'Resident Management';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Resident Management</h1>
            <span class="page-subtitle">View, search, and manage all residents</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">✅ Resident record updated successfully.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>All Residents</h3>
                    <div class="card-actions">
                        <input type="text"
                            class="search-input"
                            placeholder="Search by name, purok..." />
                        <select class="filter-select">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <a href="../staff/resident_encoding.php"
                            class="btn btn-primary btn-small">+ Add Resident</a>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Resident ID</th>
                            <th>Full Name</th>
                            <th>Birthdate</th>
                            <th>Purok</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="empty-row">No residents found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>