<?php
// Barangay Connect – Role-Aware Sidebar
// includes/sidebar.php

$role    = $_SESSION['role'] ?? '';
$current = $_SERVER['PHP_SELF'];

function nav_link(string $href, string $icon, string $label, string $current): string
{
    $active = (strpos($current, basename($href)) !== false) ? 'active' : '';
    return "
        <a href=\"$href\" class=\"nav-link $active\">
            <span class=\"nav-icon\">$icon</span>
            $label
        </a>";
}

$menus = [
    'captain' => [
        ['href' => '/BARANGAY_CONNECT/captain/dashboard.php',       'icon' => '🏛️', 'label' => 'Dashboard'],
        ['href' => '/BARANGAY_CONNECT/captain/final_approvals.php', 'icon' => '✅', 'label' => 'Final Approvals'],
        ['href' => '/BARANGAY_CONNECT/captain/reports.php',         'icon' => '📊', 'label' => 'Reports'],
        ['href' => '/BARANGAY_CONNECT/captain/audit_log.php',       'icon' => '📋', 'label' => 'Audit Log'],
        ['href' => '/BARANGAY_CONNECT/captain/system_override.php', 'icon' => '⚙️', 'label' => 'System Override'],
    ],
    'secretary' => [
        ['href' => '/BARANGAY_CONNECT/secretary/dashboard.php',             'icon' => '🏠', 'label' => 'Dashboard'],
        ['href' => '/BARANGAY_CONNECT/secretary/resident_verification.php', 'icon' => '👤', 'label' => 'Resident Verification'],
        ['href' => '/BARANGAY_CONNECT/secretary/resident_management.php',   'icon' => '👥', 'label' => 'Residents'],
        ['href' => '/BARANGAY_CONNECT/secretary/request_processing.php',    'icon' => '📄', 'label' => 'Process Requests'],
        ['href' => '/BARANGAY_CONNECT/secretary/document_release.php',      'icon' => '📬', 'label' => 'Release Documents'],
        ['href' => '/BARANGAY_CONNECT/secretary/complaint_management.php',  'icon' => '⚠️', 'label' => 'Complaints'],
        ['href' => '/BARANGAY_CONNECT/secretary/reports.php',               'icon' => '📊', 'label' => 'Reports'],
    ],
    'staff' => [
        ['href' => '/BARANGAY_CONNECT/staff/dashboard.php',             'icon' => '🏠', 'label' => 'Dashboard'],
        ['href' => '/BARANGAY_CONNECT/staff/resident_encoding.php',     'icon' => '👤', 'label' => 'Encode Resident'],
        ['href' => '/BARANGAY_CONNECT/staff/request_acceptance.php',    'icon' => '📥', 'label' => 'Accept Request'],
        ['href' => '/BARANGAY_CONNECT/staff/request_status_update.php', 'icon' => '🔄', 'label' => 'Update Status'],
        ['href' => '/BARANGAY_CONNECT/staff/document_preparation.php',  'icon' => '📄', 'label' => 'Prepare Documents'],
    ],
    'sysadmin' => [
        ['href' => '/BARANGAY_CONNECT/sysadmin/dashboard.php',       'icon' => '🖥️', 'label' => 'Dashboard'],
        ['href' => '/BARANGAY_CONNECT/sysadmin/user_accounts.php',   'icon' => '👥', 'label' => 'User Accounts'],
        ['href' => '/BARANGAY_CONNECT/sysadmin/audit_log.php',       'icon' => '📋', 'label' => 'Audit Log'],
        ['href' => '/BARANGAY_CONNECT/sysadmin/backup.php',          'icon' => '💾', 'label' => 'Backup'],
        ['href' => '/BARANGAY_CONNECT/sysadmin/system_settings.php', 'icon' => '⚙️', 'label' => 'Settings'],
    ],
    'resident' => [
        ['href' => '/BARANGAY_CONNECT/resident/dashboard.php',         'icon' => '🏠', 'label' => 'Dashboard'],
        ['href' => '/BARANGAY_CONNECT/resident/my_profile.php',        'icon' => '👤', 'label' => 'My Profile'],
        ['href' => '/BARANGAY_CONNECT/resident/new_request.php',       'icon' => '📄', 'label' => 'New Request'],
        ['href' => '/BARANGAY_CONNECT/resident/track_request.php',     'icon' => '🔍', 'label' => 'Track Request'],
        ['href' => '/BARANGAY_CONNECT/resident/facility_schedule.php', 'icon' => '📅', 'label' => 'Facility Schedule'],
    ],
];

$role_labels = [
    'captain'   => 'Barangay Captain',
    'secretary' => 'Barangay Secretary',
    'staff'     => 'Barangay Staff',
    'sysadmin'  => 'System Administrator',
    'resident'  => 'Resident',
];
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🏛️</span>
        <div>
            <div class="brand-name">Barangay Connect</div>
            <div class="brand-role">
                <?= htmlspecialchars($role_labels[$role] ?? 'User') ?>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($menus[$role] ?? [] as $item): ?>
            <?= nav_link($item['href'], $item['icon'], $item['label'], $current) ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <span><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></span>
        <a href="/BARANGAY_CONNECT/public/logout.php" class="logout-link">Logout</a>
    </div>
</aside>