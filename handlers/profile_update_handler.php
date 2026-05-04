<?php
// handlers/profile_update_handler.php
// FIXED Bug #8: Passwords are no longer trimmed before verification.
//               trim() was silently stripping intentional leading/trailing
//               spaces from passwords set by the user, causing false
//               'wrong password' errors.

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
$stmt = $pdo->prepare("SELECT ResidentID FROM UserAccount WHERE UserAccountID = ?");
$stmt->execute([$user_id]);
$resident_id = $stmt->fetchColumn();

if (!$resident_id) {
    header('Location: ../resident/my_profile.php?msg=error');
    exit;
}

// --- Password change (if requested) ---
// FIXED Bug #8: Do NOT trim passwords – spaces may be intentional.
$current_password = $_POST['current_password'] ?? '';
$new_password     = $_POST['new_password']     ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (!empty($current_password) || !empty($new_password)) {
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        header('Location: ../resident/my_profile.php?msg=missing_password');
        exit;
    }
    if ($new_password !== $confirm_password) {
        header('Location: ../resident/my_profile.php?msg=password_mismatch');
        exit;
    }
    if (strlen($new_password) < 8) {
        header('Location: ../resident/my_profile.php?msg=password_short');
        exit;
    }
    // Verify current password
    $stmt = $pdo->prepare("SELECT PasswordHash FROM UserAccount WHERE UserAccountID = ?");
    $stmt->execute([$user_id]);
    $stored_hash = $stmt->fetchColumn();
    if (!password_verify($current_password, $stored_hash)) {
        header('Location: ../resident/my_profile.php?msg=wrong_password');
        exit;
    }
    // Update password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE UserAccount SET PasswordHash = ? WHERE UserAccountID = ?");
    $stmt->execute([$new_hash, $user_id]);
}

// --- Collect editable fields for Resident table ---
$contact = trim($_POST['contact'] ?? '');
$email   = trim($_POST['email']   ?? '');
$address = trim($_POST['address'] ?? '');
$purok   = trim($_POST['purok']   ?? '');

// Validate email format if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../resident/my_profile.php?msg=invalid_email');
    exit;
}

// --- Profile picture upload (optional) ---
$profile_picture_path = null;
$stmt = $pdo->query("SHOW COLUMNS FROM Resident LIKE 'ProfilePicture'");
$has_profile_pic = $stmt->rowCount() > 0;

if ($has_profile_pic && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['profile_picture']['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        header('Location: ../resident/my_profile.php?msg=invalid_picture');
        exit;
    }
    if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
        header('Location: ../resident/my_profile.php?msg=picture_too_large');
        exit;
    }
    $project_root = dirname(__DIR__);
    $upload_dir = $project_root . '/uploads/profile_pictures/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $resident_id . '_' . uniqid() . '.' . $ext;
    $destination = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
        $profile_picture_path = 'uploads/profile_pictures/' . $filename;
    } else {
        header('Location: ../resident/my_profile.php?msg=upload_failed');
        exit;
    }
}

// --- Build update query for Resident table ---
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
    $sql = "UPDATE Resident SET " . implode(', ', $update_fields) . " WHERE ResidentID = ?";
    $params[] = $resident_id;
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($params)) {
        header('Location: ../resident/my_profile.php?msg=update_failed');
        exit;
    }
}

// Also update email in UserAccount if changed
if (!empty($email)) {
    $stmt = $pdo->prepare("UPDATE UserAccount SET Email = ? WHERE UserAccountID = ?");
    $stmt->execute([$email, $user_id]);
}

header('Location: ../resident/my_profile.php?msg=updated');
exit;
