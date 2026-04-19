<?php
// Barangay Connect – Self Registration (Public)
// public/register.php

require_once '../config/session.php';
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    redirect_to_dashboard();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name   = trim($_POST['first_name']   ?? '');
    $middle_name  = trim($_POST['middle_name']  ?? '');
    $last_name    = trim($_POST['last_name']    ?? '');
    $birthdate    = trim($_POST['birthdate']    ?? '');
    $sex          = trim($_POST['sex']          ?? '');
    $address      = trim($_POST['address']      ?? '');
    $purok        = trim($_POST['purok']        ?? '');
    $contact      = trim($_POST['contact']      ?? '');
    $email        = trim($_POST['email']        ?? '');
    $username     = trim($_POST['username']     ?? '');
    $password     = $_POST['password']          ?? '';
    $confirm_pass = $_POST['confirm_password']  ?? '';

    if (
        empty($first_name) || empty($last_name)  ||
        empty($birthdate)  || empty($sex)         ||
        empty($address)    || empty($username)    ||
        empty($password)
    ) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        require_once '../classes/Resident.php';
        require_once '../classes/UserAccount.php';
        require_once '../classes/AuditLog.php';

        $resident = new Resident();
        $ua       = new UserAccount();
        $audit    = new AuditLog();

        // Check duplicate resident
        if ($resident->isDuplicate(
            $first_name,
            $last_name,
            $birthdate,
            $address
        )) {
            $error = 'A resident with the same name, birthdate, and address already exists.';
        }
        // Check duplicate username
        elseif ($ua->findByUsername($username)) {
            $error = 'That username is already taken. Please choose another.';
        } else {
            // Handle government ID upload
            $gov_id_path = null;
            if (
                isset($_FILES['gov_id_image']) &&
                $_FILES['gov_id_image']['error'] === UPLOAD_ERR_OK
            ) {
                $upload_dir = '../uploads/government_ids/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext         = pathinfo(
                    $_FILES['gov_id_image']['name'],
                    PATHINFO_EXTENSION
                );
                $filename    = uniqid('gov_id_', true) . '.' . $ext;
                $destination = $upload_dir . $filename;
                if (move_uploaded_file(
                    $_FILES['gov_id_image']['tmp_name'],
                    $destination
                )) {
                    $gov_id_path = 'uploads/government_ids/' . $filename;
                }
            }

            // Create resident record
            $resident_id = $resident->create([
                'first_name'   => $first_name,
                'middle_name'  => $middle_name,
                'last_name'    => $last_name,
                'birthdate'    => $birthdate,
                'sex'          => $sex,
                'address'      => $address,
                'purok'        => $purok,
                'contact'      => $contact,
                'email'        => $email,
                'gov_id_path'  => $gov_id_path,
            ]);

            // Create user account with PendingVerification
            $ua->create([
                'resident_id' => $resident_id,
                'username'    => $username,
                'password'    => $password,
                'role'        => 'resident',
                'status'      => 'PendingVerification',
                'full_name'   => trim("$first_name $last_name"),
                'email'       => $email,
            ]);

            $audit->log(
                "New self-registration submitted",
                "ResidentID: $resident_id | Username: $username"
            );

            $success = 'Your registration has been submitted! A barangay staff will review your information within 24 hours. You will be notified once your account is activated.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register – Barangay Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/auth.css" />
    <link rel="stylesheet" href="../assets/css/forms.css" />
</head>

<body>

    <div class="page-transition"></div>

    <div class="register-page-layout">

        <!-- Left Panel (same as login) -->
        <div class="login-left">
            <div class="login-left-inner">
                <div class="login-circles">
                    <div class="circle circle-1"></div>
                    <div class="circle circle-2"></div>
                </div>
                <div class="login-left-content">
                    <div class="login-seal">🏛️</div>
                    <h1>Barangay Connect</h1>
                    <p>The official portal for barangay services,
                        document requests, and community management.</p>
                    <ul class="login-features">
                        <li>
                            <span class="feat-icon-box">📄</span>
                            Request Clearances &amp; Certificates
                        </li>
                        <li>
                            <span class="feat-icon-box">📢</span>
                            File and Track Complaints
                        </li>
                        <li>
                            <span class="feat-icon-box">🏟️</span>
                            Reserve Barangay Facilities
                        </li>
                        <li>
                            <span class="feat-icon-box">🔍</span>
                            Track Your Request in Real Time
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="register-right">
            <div class="register-form-box">

                <a href="login.php" class="back-link">← Back to Login</a>
                <h2>Create Account</h2>
                <p class="form-subtitle">
                    Fill in your details to register as a barangay resident.
                    Your account will be reviewed within 24 hours.
                </p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        ⚠️ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        ✅ <?= htmlspecialchars($success) ?>
                    </div>
                    <div style="text-align:center; margin-top: 24px;">
                        <a href="login.php" class="btn-login"
                            style="display:inline-block; padding: 12px 32px;
                              text-decoration:none;">
                            Go to Login
                        </a>
                    </div>
                <?php else: ?>

                    <form method="POST"
                        action="register.php"
                        enctype="multipart/form-data"
                        class="register-form-grid validate-form">

                        <!-- Personal Info -->
                        <div class="register-section-title">
                            Personal Information
                        </div>

                        <div class="form-group">
                            <label>FIRST NAME *</label>
                            <div class="input-wrap">
                                <input type="text"
                                    name="first_name"
                                    class="form-input"
                                    placeholder="First name"
                                    value="<?= htmlspecialchars(
                                                $_POST['first_name'] ?? ''
                                            ) ?>"
                                    required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>MIDDLE NAME</label>
                            <div class="input-wrap">
                                <input type="text"
                                    name="middle_name"
                                    class="form-input"
                                    placeholder="Middle name"
                                    value="<?= htmlspecialchars(
                                                $_POST['middle_name'] ?? ''
                                            ) ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>LAST NAME *</label>
                            <div class="input-wrap">
                                <input type="text"
                                    name="last_name"
                                    class="form-input"
                                    placeholder="Last name"
                                    value="<?= htmlspecialchars(
                                                $_POST['last_name'] ?? ''
                                            ) ?>"
                                    required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>BIRTHDATE *</label>
                            <input type="date"
                                name="birthdate"
                                class="form-input"
                                value="<?= htmlspecialchars(
                                            $_POST['birthdate'] ?? ''
                                        ) ?>"
                                required />
                        </div>
                        <div class="form-group">
                            <label>SEX *</label>
                            <select name="sex" class="form-input" required>
                                <option value="">-- Select --</option>
                                <option <?= ($_POST['sex'] ?? '') === 'Male'
                                            ? 'selected' : '' ?>>Male</option>
                                <option <?= ($_POST['sex'] ?? '') === 'Female'
                                            ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>CONTACT NUMBER</label>
                            <div class="input-wrap">
                                <input type="text"
                                    name="contact"
                                    class="form-input"
                                    placeholder="09XXXXXXXXX"
                                    value="<?= htmlspecialchars(
                                                $_POST['contact'] ?? ''
                                            ) ?>" />
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="register-section-title register-full">
                            Address
                        </div>

                        <div class="form-group register-full">
                            <label>COMPLETE ADDRESS *</label>
                            <input type="text"
                                name="address"
                                class="form-input"
                                placeholder="House No., Street, Barangay"
                                value="<?= htmlspecialchars(
                                            $_POST['address'] ?? ''
                                        ) ?>"
                                required />
                        </div>
                        <div class="form-group">
                            <label>PUROK / SITIO</label>
                            <input type="text"
                                name="purok"
                                class="form-input"
                                placeholder="Purok or Sitio"
                                value="<?= htmlspecialchars(
                                            $_POST['purok'] ?? ''
                                        ) ?>" />
                        </div>
                        <div class="form-group">
                            <label>EMAIL ADDRESS</label>
                            <input type="email"
                                name="email"
                                class="form-input"
                                placeholder="your@email.com"
                                value="<?= htmlspecialchars(
                                            $_POST['email'] ?? ''
                                        ) ?>" />
                        </div>

                        <!-- Government ID -->
                        <div class="register-section-title register-full">
                            Government ID
                        </div>

                        <div class="form-group register-full">
                            <label>UPLOAD GOVERNMENT ID *</label>
                            <input type="file"
                                name="gov_id_image"
                                class="form-input"
                                accept="image/*,.pdf"
                                required />
                            <small class="form-hint">
                                Upload a clear photo of your government-issued ID
                                showing your name and address.
                            </small>
                        </div>

                        <!-- Account Credentials -->
                        <div class="register-section-title register-full">
                            Account Credentials
                        </div>

                        <div class="form-group register-full">
                            <label>USERNAME *</label>
                            <div class="input-wrap">
                                <span class="input-icon">👤</span>
                                <input type="text"
                                    name="username"
                                    class="form-input"
                                    placeholder="Choose a username"
                                    value="<?= htmlspecialchars(
                                                $_POST['username'] ?? ''
                                            ) ?>"
                                    required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>PASSWORD * (min. 8 characters)</label>
                            <div class="input-wrap">
                                <span class="input-icon">🔒</span>
                                <input type="password"
                                    name="password"
                                    class="form-input"
                                    placeholder="Create a password"
                                    required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>CONFIRM PASSWORD *</label>
                            <div class="input-wrap">
                                <span class="input-icon">🔒</span>
                                <input type="password"
                                    name="confirm_password"
                                    class="form-input"
                                    placeholder="Repeat your password"
                                    required />
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="form-group register-full">
                            <button type="submit" class="btn-login">
                                Submit Registration →
                            </button>
                            <p style="text-align:center; margin-top:16px;
                              font-size:0.88rem; color:var(--text-light);">
                                Already have an account?
                                <a href="login.php"
                                    style="color:var(--green-dark);font-weight:600;">
                                    Sign in here
                                </a>
                            </p>
                        </div>

                    </form>
                <?php endif; ?>

            </div>
        </div>

    </div>

    <script src="../assets/js/form_validation.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>