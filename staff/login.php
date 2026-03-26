<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Redirect if already logged in
if (isset($_SESSION['staff_id'])) {
    redirect(BASE_URL . '/staff/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM Staff WHERE Email = ? AND PasswordHash IS NOT NULL LIMIT 1");
        $stmt->execute([$email]);
        $staff = $stmt->fetch();

        if ($staff && password_verify($password, $staff['PasswordHash'])) {
            session_regenerate_id(true);
            $_SESSION['staff_id']   = $staff['StaffID'];
            $_SESSION['staff_name'] = $staff['Name'];
            $_SESSION['staff_dept'] = $staff['Department'];
            redirect(BASE_URL . '/staff/dashboard.php');
        } else {
            $error = 'Invalid email address or password.';
        }
    } else {
        $error = 'Please enter both your email address and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login — UniHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/student_course_hub/assets/css/admin.css">
    <style>
        /* Differentiate staff login from admin with a teal/navy accent on the left panel */
        .login-left { background: #1a3a4a; }
        .login-left::after { background: #122835; }
        .login-left-emblem { background: rgba(255,255,255,0.1); }
        .login-divider { background: #1a3a4a; }
        .btn-staff { background: #1a3a4a; color: #fff; border-color: #1a3a4a; }
        .btn-staff:hover { background: #122835; border-color: #122835; color: #fff; }
        .form-group input:focus { border-color: #1a3a4a; box-shadow: 0 0 0 3px rgba(26,58,74,0.1); }
        *:focus-visible { outline-color: #1a3a4a; }
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.8rem; color: var(--grey-600);
            text-decoration: none; margin-top: 20px;
        }
        .back-link:hover { color: #1a3a4a; }
        .portal-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 4px 12px; border-radius: 20px;
            font-size: 0.72rem; font-weight: 600;
            letter-spacing: 0.08em; text-transform: uppercase;
            color: rgba(255,255,255,0.8);
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
<div class="login-page">

    <!-- Left branding panel -->
    <div class="login-left">
        <div class="login-left-content">
            <div class="login-left-emblem">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <div class="portal-badge">
                <i class="bi bi-shield-check"></i> Staff Portal
            </div>
            <h1>UniHub<br>Staff Portal</h1>
            <p>
                Access your teaching profile, view the modules you lead, and see which
                programmes your modules appear in — all in one place.
            </p>

            <div style="margin-top:36px; display:flex; flex-direction:column; gap:12px">
                <div style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.65); font-size:0.85rem">
                    <i class="bi bi-journal-text" style="color:rgba(255,255,255,0.4)"></i>
                    View modules you are leading
                </div>
                <div style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.65); font-size:0.85rem">
                    <i class="bi bi-mortarboard" style="color:rgba(255,255,255,0.4)"></i>
                    See which programmes use your modules
                </div>
                <div style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.65); font-size:0.85rem">
                    <i class="bi bi-person-circle" style="color:rgba(255,255,255,0.4)"></i>
                    View and update your staff profile
                </div>
            </div>
        </div>
    </div>

    <!-- Right login panel -->
    <div class="login-right">
        <div class="login-card">
            <div class="login-divider"></div>
            <h2>Staff Sign In</h2>
            <p class="login-sub">Use your staff email address to access the portal.</p>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-circle"></i> <?= h($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/student_course_hub/staff/login.php">
                <div class="form-group">
                    <label for="email">Staff Email Address</label>
                    <input type="email" id="email" name="email"
                           value="<?= isset($_POST['email']) ? h($_POST['email']) : '' ?>"
                           placeholder="e.g. a.johnson@unihub.ac.uk"
                           autocomplete="email" autofocus required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-staff"
                        style="width:100%; justify-content:center; padding:11px; margin-top:4px; display:flex; align-items:center; gap:8px; font-size:0.9rem; font-weight:600; border-radius:3px; cursor:pointer; border:1px solid #1a3a4a; transition:0.18s ease">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In to Staff Portal
                </button>
            </form>

            <a href="/student_course_hub/" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to student site
            </a>

            <p style="margin-top:16px; font-size:0.75rem; color:var(--grey-400); text-align:center; line-height:1.6">
                Staff accounts are set up by your administrator.<br>
                Contact <a href="mailto:it@unihub.ac.uk" style="color:var(--grey-600)">it@unihub.ac.uk</a> if you cannot access your account.
            </p>
        </div>
    </div>

</div>
</body>
</html>