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
?>
<nav class="topnav">
    <div class="topnav-left">
        <span class="topnav-title"><?= htmlspecialchars($brgy_name) ?></span>
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