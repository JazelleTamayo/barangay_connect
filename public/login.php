<?php
// Barangay Connect – Login Page (database only – no hardcoded users)
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';

if (isset($_SESSION['user_id'])) {
    redirect_to_dashboard();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } else {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare("SELECT * FROM UserAccount WHERE Username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['PasswordHash'])) {
                if ($user['AccountStatus'] !== 'Active') {
                    $error = 'Your account is ' . htmlspecialchars($user['AccountStatus']) . '. Please contact the barangay office.';
                } else {
                    $_SESSION['user_id']        = $user['UserAccountID'];
                    $_SESSION['username']       = $user['Username'];
                    $_SESSION['full_name']      = $user['FullName'];
                    $_SESSION['role']           = strtolower($user['Role']);
                    $_SESSION['account_status'] = $user['AccountStatus'];
                    redirect_to_dashboard();
                }
            } else {
                $error = 'Invalid username or password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login – Barangay Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/auth.css" />
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
        <div class="login-right">
            <div class="login-form-box">
                <a href="index.php" class="back-link">← Back to Home</a>
                <h2>Welcome Back</h2>
                <p class="form-subtitle">
                    Sign in to access your barangay services.
                </p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        ⚠️ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if (
                    isset($_GET['error']) &&
                    $_GET['error'] === 'account_inactive'
                ): ?>
                    <div class="alert alert-error">
                        ⚠️ Your account is not active.
                        Please contact the barangay office.
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="login-form">
                    <div class="form-group">
                        <label for="username">USERNAME</label>
                        <div class="input-wrap">
                            <span class="input-icon">👤</span>
                            <input type="text"
                                id="username"
                                name="username"
                                placeholder="Enter your username"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                required
                                autocomplete="username" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">PASSWORD</label>
                        <div class="input-wrap">
                            <span class="input-icon">🔒</span>
                            <input type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password" />
                        </div>
                        <div class="form-row-end">
                            <a href="#">Forgot password?</a>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">
                        Sign In →
                    </button>
                </form>

                <div class="login-divider">or</div>
                <div class="register-link-box">
                    Don't have an account?
                    <a href="register.php"><strong>Register here</strong></a>
                </div>
            </div>
        </div>

    </div>

    <script src="../assets/js/main.js"></script>
</body>

</html>