<?php
// Barangay Connect – Complaint Details
// secretary/complaint_detail.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('secretary');

$pdo = get_db();
$id  = (int) ($_GET['id'] ?? 0);

// Fetch complaint details
$stmt = $pdo->prepare("
    SELECT 
        c.*, 
        sr.ReferenceNo, 
        sr.Status, 
        CONCAT(r.FirstName, ' ', r.LastName) AS ComplainantName
    FROM Complaint c
    JOIN ServiceRequest sr ON c.RequestID = sr.RequestID
    JOIN Resident r ON sr.ResidentID = r.ResidentID
    WHERE c.RequestID = ?
");
$stmt->execute([$id]);
$complaint = $stmt->fetch();

// If complaint doesn't exist, go back
if (!$complaint) {
    header('Location: complaint_management.php?msg=not_found');
    exit;
}

$page_title = 'Complaint Details';
include '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/complaints.css">

<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        
        <div class="page-header">
            <div class="header-with-back">
                <a href="complaint_management.php" class="btn-back">← Back</a>
                <h1>Complaint Details</h1>
            </div>
            <span class="badge badge-<?= strtolower($complaint['Status']) ?>">
                <?= htmlspecialchars($complaint['Status']) ?>
            </span>
        </div>

        <div class="page-body">

            <!-- Summary Card -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">Reference Number</span>
                            <h2 class="text-primary mt-1 mb-0" style="font-weight: 700;">
                                <?= htmlspecialchars($complaint['ReferenceNo']) ?>
                            </h2>
                        </div>
                        <div style="text-align:right;">
                             <span class="text-muted small fw-bold text-uppercase d-block mb-2">Current Status</span>
                             <span class="badge badge-<?= strtolower($complaint['Status']) ?>" style="padding: 10px 20px; font-size: 0.9rem; border-radius: 50px;">
                                <?= htmlspecialchars($complaint['Status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Case Details Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-4">
                    <h4 class="mb-0" style="color: var(--dark-green); font-weight: 600;">
                        <i class="fas fa-info-circle me-2"></i> Case Information
                    </h4>
                </div>

                <div class="card-body p-4">
                    <!-- Information Grid -->
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:30px;">

                        <!-- Complainant -->
                        <div class="detail-item highlight-blue">
                            <label class="text-muted small fw-bold">COMPLAINANT</label>
                            <p class="mb-0" style="font-weight: 600; color: #333;">
                                <?= htmlspecialchars($complaint['ComplainantName']) ?>
                            </p>
                        </div>

                        <!-- Respondent -->
                        <div class="detail-item highlight-red">
                            <label class="text-muted small fw-bold">RESPONDENT</label>
                            <p class="mb-0" style="font-weight: 600; color: #333;">
                                <?= htmlspecialchars($complaint['RespondentName']) ?>
                            </p>
                        </div>

                        <!-- Incident Date -->
                        <div class="detail-item">
                            <label class="text-muted small fw-bold">INCIDENT DATE</label>
                            <p class="mb-0 pt-1">
                                <i class="far fa-calendar-alt me-2 text-muted"></i>
                                <?= date('F d, Y', strtotime($complaint['IncidentDate'])) ?>
                            </p>
                        </div>

                        <!-- Mediation Schedule -->
                        <div class="detail-item">
                            <label class="text-muted small fw-bold">MEDIATION SCHEDULE</label>
                            <p class="mb-0 pt-1">
                                <i class="far fa-clock me-2 text-muted"></i>
                                <?= $complaint['MediationDate']
                                    ? '<span class="text-success fw-bold">' . date('F d, Y', strtotime($complaint['MediationDate'])) . '</span>'
                                    : '<em class="text-muted">Not yet scheduled</em>' ?>
                            </p>
                        </div>

                    </div>

                    <!-- Narrative Section -->
                    <div style="margin-top:40px;">
                        <label class="text-muted small fw-bold mb-3 d-block text-uppercase">
                            Statement / Complaint Details
                        </label>

                        <div class="complaint-box">
                            <i class="fas fa-quote-left text-light me-3" style="padding:5px; font-size: 1.5rem; opacity: 0.5;"></i>
                            <?= nl2br(htmlspecialchars($complaint['ComplaintDetails'] ?? 'No additional details provided by the complainant.')) ?>
                        </div>
                    </div>
                </div>
                
                <!-- Action Footer -->
                <div class="card-footer bg-white p-4 border-top text-end">
                    <button onclick="window.print()" class="btn btn-outline-secondary px-4">
                        <i class="fas fa-print me-2"></i> Print Record
                    </button>
                </div>
            </div>

        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>