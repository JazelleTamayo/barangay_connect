<?php
// Barangay Connect – Document Preparation
// staff/document_preparation.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('staff');

$page_title = 'Document Preparation';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Document Preparation</h1>
            <span class="page-subtitle">Prepare approved documents for Secretary release</span>
        </div>
        <div class="page-body">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ready'): ?>
                <div class="alert alert-success">✅ Document marked as ready for release.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Approved Requests — Prepare for Release</h3>
                    <p class="card-desc">
                        Mark documents as ready once you have prepared the physical copy
                        for the Secretary to release.
                    </p>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Approved Date</th>
                            <th>Prepared By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="empty-row">No documents to prepare.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>