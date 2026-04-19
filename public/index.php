<?php
// Barangay Connect – Landing Page
// public/index.php

require_once '../config/session.php';

if (isset($_SESSION['user_id'])) {
    redirect_to_dashboard();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Barangay Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/welcome.css" />
</head>

<body>

    <div class="page-transition"></div>

    <!-- Navbar -->
    <nav class="landing-nav">
        <div class="landing-nav-brand">
            <div class="brand-logo">🏛️</div>
            <div>
                <div class="brand-title">Barangay Connect</div>
                <div class="brand-sub">Service Request & Document Tracking</div>
            </div>
        </div>
        <div class="landing-nav-right">
            Republic of the Philippines
        </div>
    </nav>

    <!-- Hero -->
    <main class="landing-main">
        <div class="landing-hero">
            <div class="hero-seal">🏛️</div>
            <p class="hero-label">OFFICIAL BARANGAY PORTAL</p>
            <h1 class="hero-title">
                Your Barangay,<br>
                <span class="hero-accent">Connected.</span>
            </h1>
            <p class="hero-desc">
                Request documents, track complaints, and reserve facilities —<br>
                all in one place. Fast, transparent, and paperless.
            </p>
            <div class="hero-actions">
                <a href="login.php" class="hero-btn-primary">
                    🔐 Login to My Account
                </a>
                <a href="register.php" class="hero-btn-secondary">
                    📋 Register as Resident
                </a>
            </div>
        </div>

        <!-- Service Pills -->
        <div class="service-pills">
            <div class="service-pill">📄 Barangay Clearance</div>
            <div class="service-pill">🤝 Certificate of Indigency</div>
            <div class="service-pill">📢 File a Complaint</div>
            <div class="service-pill">🏟️ Facility Reservation</div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="landing-footer">
        © <?= date('Y') ?> Barangay Connect. All rights reserved.
    </footer>

    <script src="../assets/js/main.js"></script>
</body>

</html>