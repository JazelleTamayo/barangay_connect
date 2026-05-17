<?php
// Barangay Connect – Forgot Password
// public/forgot_password.php

require_once '../config/session.php';
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect_to_dashboard();
}

$step  = 1;   // Step 1: verify identity | Step 2: set new password
$error = '';
$success = '';

// ── Step 2: Save new password ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    $account_id      = (int) ($_SESSION['reset_account_id'] ?? 0);
    $new_password    = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$account_id) {
        $error = 'Session expired. Please start over.';
        $step  = 1;
        unset($_SESSION['reset_account_id']);
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in both password fields.';
        $step  = 2;
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
        $step  = 2;
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters.';
        $step  = 2;
    } else {
        $pdo  = get_db();
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE UserAccount SET PasswordHash = ? WHERE UserAccountID = ?");
        $stmt->execute([$hash, $account_id]);
        unset($_SESSION['reset_account_id']);
        $success = 'Password changed successfully. You can now log in.';
        $step = 1;
    }
}

// ── Step 1: Verify username + email ──────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');

    if (empty($username) || empty($email)) {
        $error = 'Please enter both your username and email.';
        $step  = 1;
    } else {
        $pdo  = get_db();
        $stmt = $pdo->prepare(
            "SELECT UserAccountID, AccountStatus FROM UserAccount
             WHERE Username = ? AND Email = ? LIMIT 1"
        );
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = 'No account found matching that username and email.';
            $step  = 1;
        } elseif ($user['AccountStatus'] === 'Inactive') {
            $error = 'This account is disabled. Please contact the barangay office.';
            $step  = 1;
        } else {
            // Identity verified — move to step 2
            $_SESSION['reset_account_id'] = $user['UserAccountID'];
            $step = 2;
        }
    }
}

// If session has reset_account_id set, stay on step 2
elseif (isset($_SESSION['reset_account_id'])) {
    $step = 2;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password – Barangay Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/auth.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <div class="page-transition"></div>

    <div class="login-page">

        <!-- Left Panel -->
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
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="login-right">
            <div class="login-form-box">
                <a href="login.php" class="back-link">← Back to Login</a>

                <?php if ($step === 1): ?>

                    <h2>Forgot Password</h2>
                    <p class="form-subtitle">
                        Enter your username and registered email to reset your password.
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?>
                            <a href="login.php">Log in now →</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="forgot_password.php" class="login-form">
                        <input type="hidden" name="action" value="verify">
                        <div class="form-group">
                            <label for="username">USERNAME</label>
                            <div class="input-wrap">
                                <span class="input-icon">👤</span>
                                <input type="text"
                                    id="username"
                                    name="username"
                                    placeholder="Enter your username"
                                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                    required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email">REGISTERED EMAIL</label>
                            <div class="input-wrap">
                                <span class="input-icon">✉️</span>
                                <input type="email"
                                    id="email"
                                    name="email"
                                    placeholder="Enter your registered email"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    required />
                            </div>
                        </div>
                        <button type="submit" class="btn-login">Verify Identity →</button>
                    </form>

                <?php else: ?>

                    <h2>Set New Password</h2>
                    <p class="form-subtitle">
                        Identity verified. Enter your new password below.
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="forgot_password.php" class="login-form">
                        <input type="hidden" name="action" value="reset">
                        <div class="form-group">
                            <label for="new_password">NEW PASSWORD</label>
                            <div class="input-wrap">
                                <span class="input-icon">🔒</span>
                                <input type="password"
                                    id="new_password"
                                    name="new_password"
                                    placeholder="Min. 8 characters"
                                    required
                                    minlength="8" />
                                <button type="button" class="input-toggle" onclick="togglePassword(this)" tabindex="-1">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">CONFIRM PASSWORD</label>
                            <div class="input-wrap">
                                <span class="input-icon">🔒</span>
                                <input type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Repeat new password"
                                    required />
                                <button type="button" class="input-toggle" onclick="togglePassword(this)" tabindex="-1">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn-login">Save New Password →</button>
                    </form>

                    <div style="margin-top: 12px; text-align: center;">
                        <a href="forgot_password.php" style="font-size: 0.85rem; color: #6b7280;">
                            ← Start over
                        </a>
                    </div>

                <?php endif; ?>

            </div>
        </div>

    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function togglePassword(btn) {
            const input = btn.closest('.input-wrap').querySelector('input[type="password"], input[type="text"]');
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>