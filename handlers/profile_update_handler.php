<?php
// handlers/profile_update_handler.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../resident/my_profile.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = get_db();

// Get resident ID from user account
$stmt = $pdo->prepare("SELECT ResidentID FROM useraccount WHERE UserAccountID = ?");
$stmt->execute([$user_id]);
$resident_id = $stmt->fetchColumn();

if (!$resident_id) {
    header('Location: ../resident/my_profile.php?msg=error');
    exit;
}

// Collect editable fields
$contact = trim($_POST['contact'] ?? '');
$email   = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$purok   = trim($_POST['purok'] ?? '');

// Validate email format if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../resident/my_profile.php?msg=error');
    exit;
}

// Handle profile picture upload
$profile_picture_path = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['profile_picture']['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        header('Location: ../resident/my_profile.php?msg=error');
        exit;
    }
    // Max 2MB
    if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
        header('Location: ../resident/my_profile.php?msg=error');
        exit;
    }
    $upload_dir = '../uploads/profile_pictures/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $resident_id . '_' . uniqid() . '.' . $ext;
    $destination = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
        $profile_picture_path = 'uploads/profile_pictures/' . $filename;
    } else {
        header('Location: ../resident/my_profile.php?msg=error');
        exit;
    }
}

// Build update query for resident table
$update_fields = [];
$params = [];
if (!empty($contact)) {
    $update_fields[] = "ContactNumber = ?";
    $params[] = $contact;
}
if (!empty($email)) {
    $update_fields[] = "Email = ?";
    $params[] = $email;
}
if (!empty($address)) {
    $update_fields[] = "Address = ?";
    $params[] = $address;
}
if (!empty($purok)) {
    $update_fields[] = "Purok = ?";
    $params[] = $purok;
}
if ($profile_picture_path) {
    $update_fields[] = "ProfilePicture = ?";
    $params[] = $profile_picture_path;
}

if (!empty($update_fields)) {
    $sql = "UPDATE resident SET " . implode(', ', $update_fields) . " WHERE ResidentID = ?";
    $params[] = $resident_id;
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($params)) {
        header('Location: ../resident/my_profile.php?msg=error');
        exit;
    }
}

// Also update email in useraccount if changed
if (!empty($email)) {
    $stmt = $pdo->prepare("UPDATE useraccount SET Email = ? WHERE UserAccountID = ?");
    $stmt->execute([$email, $user_id]);
}

header('Location: ../resident/my_profile.php?msg=updated');
exit;
?>