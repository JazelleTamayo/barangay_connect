<?php
// Barangay Connect – Top Navbar
// includes/navbar.php

$role = $_SESSION['role'] ?? '';

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
        <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
        <span class="topnav-title">Barangay Connect</span>
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