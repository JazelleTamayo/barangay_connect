<?php
// Barangay Connect – Top Navbar
// includes/navbar.php

require_once __DIR__ . '/../config/settings.php';
$role         = $_SESSION['role'] ?? '';
$brgy_name    = get_setting('barangay_name', 'Barangay Connect');

$role_labels = [
    'captain'   => 'Barangay Captain',
    'secretary' => 'Barangay Secretary',
    'staff'     => 'Barangay Staff',
    'sysadmin'  => 'System Administrator',
    'resident'  => 'Resident',
];

// Pages can set $page_back_url and/or $page_subtitle before including header.php
$page_back_url  = $page_back_url  ?? null;
$page_subtitle  = $page_subtitle  ?? null;
$nav_title      = $page_title     ?? $brgy_name;
?>
<nav class="topnav">
    <div class="topnav-left">
        <?php if ($page_back_url): ?>
            <a href="<?= htmlspecialchars($page_back_url) ?>" class="topnav-back" title="Go back">&#8592;</a>
        <?php endif; ?>
        <div class="topnav-page-info">
            <span class="topnav-title"><?= htmlspecialchars($nav_title) ?></span>
            <?php if ($page_subtitle): ?>
                <span class="topnav-page-subtitle"><?= htmlspecialchars($page_subtitle) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="topnav-right">
        <div class="topnav-user">
            <span class="topnav-role">
                <?= htmlspecialchars($role_labels[$role] ?? 'User') ?>
            </span>
            <span class="topnav-name">
                <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?>
            </span>
        </div>
        <a href="/BARANGAY_CONNECT/public/logout.php" class="topnav-logout">
            🚪 Logout
        </a>
    </div>
</nav>
