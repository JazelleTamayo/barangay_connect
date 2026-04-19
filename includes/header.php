<?php
// Barangay Connect – Shared Header
// includes/header.php

$page_title = $page_title ?? 'Barangay Connect';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($page_title) ?> – Barangay Connect</title>
    <link rel="stylesheet" href="/BARANGAY_CONNECT/assets/css/style.css" />
    <link rel="stylesheet" href="/BARANGAY_CONNECT/assets/css/dashboard.css" />
    <link rel="stylesheet" href="/BARANGAY_CONNECT/assets/css/tables.css" />
    <link rel="stylesheet" href="/BARANGAY_CONNECT/assets/css/forms.css" />
</head>

<body>