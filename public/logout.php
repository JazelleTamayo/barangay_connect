<?php
// Barangay Connect – Logout
// public/logout.php

require_once '../config/session.php';
require_once '../classes/AuditLog.php';

// Log before session is destroyed
$audit = new AuditLog();
$audit->log('User logged out', 'UserAccountID: ' . ($_SESSION['user_id'] ?? 0));

logout();
