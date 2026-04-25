<?php
// Barangay Connect – Resident Management
// secretary/resident_management.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../classes/Resident.php'; //FIXED: added Resident class include

require_role('secretary');

//FIXED: read search and status filter params from GET
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

//FIXED: query DB for residents with search/filter support
$residentClass = new Resident();

if ($search !== '') {
    $residents = $residentClass->search($search);
    if ($status !== '') {
        $residents = array_filter(
            $residents,
            fn($r) => ($r['Status'] ?? '') === $status
        );
    }
} else {
    $residents = $residentClass->getAll($status);
}
//FIXED end

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
                        <!-- FIXED: wrapped search/filter in a GET form so values are submitted to PHP -->
                        <form method="GET" action="" class="inline-form" id="filter-form">
                            <input type="text"
                                name="search"
                                class="search-input"
                                placeholder="Search by name, purok..."
                                value="<?= htmlspecialchars($search) ?>" <!-- FIXED: preserve search value -->
                                oninput="document.getElementById('filter-form').submit()" />
                            <select name="status"
                                class="filter-select"
                                onchange="document.getElementById('filter-form').submit()"> <!-- FIXED: preserve selected status -->
                                <option value="" <?= $status === '' ? 'selected' : '' ?>>All Status</option>
                                <option value="Active"   <?= $status === 'Active'   ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </form>
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
                        <!-- FIXED: replaced hardcoded empty row with dynamic PHP loop -->
                        <?php if (empty($residents)): ?>
                            <tr>
                                <td colspan="7" class="empty-row">No residents found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($residents as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['ResidentID']) ?></td>
                                    <td>
                                        <?= htmlspecialchars(
                                            trim(
                                                $r['FirstName'] . ' '
                                                . ($r['MiddleName'] ? $r['MiddleName'] . ' ' : '')
                                                . $r['LastName']
                                            )
                                        ) ?>
                                    </td>
                                    <td><?= htmlspecialchars($r['Birthdate'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($r['Purok'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($r['ContactNumber'] ?? '—') ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($r['Status'] ?? 'inactive') ?>">
                                            <?= htmlspecialchars($r['Status'] ?? 'Unknown') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="resident_edit.php?id=<?= (int) $r['ResidentID'] ?>"
                                            class="btn btn-small btn-secondary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <!-- FIXED end -->
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>