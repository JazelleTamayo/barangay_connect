<?php
// Barangay Connect – Session & Access Control
// config/session.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require the user to be logged in with a specific role.
 * Redirects to login if not authenticated or wrong role.
 *
 * Usage at the top of every protected page:
 *   require_once '../config/session.php';
 *   require_role('secretary');
 */
function require_role(string $role): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /BARANGAY_CONNECT/public/login.php');
        exit;
    }
    if ($_SESSION['role'] !== $role) {
        redirect_to_dashboard();
    }
    if (in_array($_SESSION['account_status'] ?? '', ['PendingVerification', 'Rejected'])) {
        session_destroy();
        header('Location: /BARANGAY_CONNECT/public/login.php?error=account_inactive');
        exit;
    }
}

/**
 * Require the user to be logged in (any role).
 */
function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /BARANGAY_CONNECT/public/login.php');
        exit;
    }
}

/**
 * Redirect to the correct dashboard based on session role.
 */
function redirect_to_dashboard(): void
{
    $role = $_SESSION['role'] ?? '';
    $map  = [
        'captain'   => '/BARANGAY_CONNECT/captain/dashboard.php',
        'secretary' => '/BARANGAY_CONNECT/secretary/dashboard.php',
        'staff'     => '/BARANGAY_CONNECT/staff/dashboard.php',
        'sysadmin'  => '/BARANGAY_CONNECT/sysadmin/dashboard.php',
        'resident'  => '/BARANGAY_CONNECT/resident/dashboard.php',
    ];
    $url = $map[$role] ?? '/BARANGAY_CONNECT/public/login.php';
    header("Location: $url");
    exit;
}

/**
 * Destroy session and log out.
 */
function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }
    session_destroy();
    header('Location: /BARANGAY_CONNECT/public/login.php');
    exit;
}

/**
 * Handle ?action=logout on any page.
 */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}

/**
 * Get current logged-in user's display name.
 */
function current_user_name(): string
{
    return htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User');
}

function current_role(): string
{
    return $_SESSION['role'] ?? '';
}
