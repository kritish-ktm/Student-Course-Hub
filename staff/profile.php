<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!isset($_SESSION['staff_id'])) {
    redirect(BASE_URL . '/staff/login.php');
}

$db      = getDB();
$staffId = (int)$_SESSION['staff_id'];

$stmt = $db->prepare("SELECT * FROM Staff WHERE StaffID = ?");
$stmt->execute([$staffId]);
$staff = $stmt->fetch();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['Name']  ?? '');
    $title = sanitize($_POST['Title'] ?? '');
    $dept  = sanitize($_POST['Department'] ?? '');
    $bio   = sanitize($_POST['Bio']   ?? '');

    // Password change (optional)
    $newPassword = $_POST['new_password']     ?? '';
    $confPassword = $_POST['confirm_password'] ?? '';

    if (empty($name)) $errors[] = 'Name is required.';
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 8) $errors[] = 'New password must be at least 8 characters.';
        if ($newPassword !== $confPassword) $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        if (!empty($newPassword)) {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE Staff SET Name=?,Title=?,Department=?,Bio=?,PasswordHash=? WHERE StaffID=?")
               ->execute([$name, $title, $dept, $bio, $hash, $staffId]);
        } else {
            $db->prepare("UPDATE Staff SET Name=?,Title=?,Department=?,Bio=? WHERE StaffID=?")
               ->execute([$name, $title, $dept, $bio, $staffId]);
        }
        $_SESSION['staff_name'] = $name;
        $_SESSION['staff_dept'] = $dept;
        $staff['Name'] = $name; $staff['Title'] = $title;
        $staff['Department'] = $dept; $staff['Bio'] = $bio;
        $success = true;
    }
}

$initials = '';
foreach (explode(' ', $staff['Name']) as $part) $initials .= strtoupper(substr($part, 0, 1));
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — UniHub Staff Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/student_course_hub/assets/css/admin.css">
    <style>
        :root { --staff-navy: #1a3a4a; --staff-navy-dark: #122835; --staff-navy-light: #e8f0f4; }
        .sidebar { background: var(--staff-navy); border-right-color: var(--staff-navy-dark); }
        .sidebar-nav a.active { background: var(--staff-navy-dark); border-left-color: var(--white); }
        .sidebar-nav a:hover  { background: rgba(0,0,0,0.2); }
        *:focus-visible { outline-color: var(--staff-navy); }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: var(--staff-navy);
            box-shadow: 0 0 0 3px rgba(26,58,74,0.08);
        }
        .btn-staff-primary {
            background: var(--staff-navy); color: var(--white);
            border-color: var(--staff-navy);
        }
        .btn-staff-primary:hover { background: var(--staff-navy-dark); border-color: var(--staff-navy-dark); color: var(--white); }
    </style>
</head>
<body>
<div class="admin-layout">

<aside class="sidebar" role="navigation" aria-label="Staff navigation">
    <a href="/student_course_hub/staff/dashboard.php" class="sidebar-logo">
        <div class="sidebar-logo-emblem" style="background:rgba(255,255,255,0.1)">
            <i class="bi bi-person-badge-fill"></i>
        </div>
        <div class="sidebar-logo-text">
            <span class="sidebar-logo-name">UniHub</span>
            <span class="sidebar-logo-sub">Staff Portal</span>
        </div>
    </a>
    <div class="sidebar-section">
        <p class="sidebar-section-label">My Portal</p>
        <ul class="sidebar-nav">
            <li><a href="/student_course_hub/staff/dashboard.php"><i class="bi bi-speedometer2 nav-icon"></i> Dashboard</a></li>
            <li><a href="/student_course_hub/staff/profile.php" class="active"><i class="bi bi-person-circle nav-icon"></i> My Profile</a></li>
        </ul>
    </div>
    <div class="sidebar-section">
        <p class="sidebar-section-label">Teaching</p>
        <ul class="sidebar-nav">
            <li><a href="/student_course_hub/staff/dashboard.php#modules"><i class="bi bi-journal-text nav-icon"></i> My Modules</a></li>
            <li><a href="/student_course_hub/staff/dashboard.php#programmes"><i class="bi bi-mortarboard nav-icon"></i> My Programmes</a></li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <a href="/student_course_hub/" target="_blank"><i class="bi bi-globe nav-icon"></i> Student Site</a>
        <a href="/student_course_hub/staff/logout.php" class="danger"><i class="bi bi-box-arrow-right nav-icon"></i> Sign Out</a>
    </div>
</aside>

<div class="admin-main">
    <header class="admin-topbar">
        <div class="topbar-left">
            <span class="topbar-title">UniHub</span>
            <span class="topbar-divider">/</span>
            <span class="topbar-page">My Profile</span>
        </div>
        <div class="topbar-right">
            <div class="topbar-user">
                <i class="bi bi-person-badge" style="font-size:1rem"></i>
                <span><?= h($staff['Name']) ?></span>
                <div class="topbar-avatar" style="background:var(--staff-navy)"><?= h($initials) ?></div>
            </div>
        </div>
    </header>

    <div class="admin-content">
        <div class="page-header">
            <div class="page-header-left">
                <div class="ku-divider-sm" style="background:var(--staff-navy)"></div>
                <h1>My Profile</h1>
                <p>Update your public-facing staff information and account password.</p>
            </div>
            <a href="/student_course_hub/staff/dashboard.php" class="btn btn-outline">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> Your profile has been updated successfully.
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
            <i class="bi bi-exclamation-circle"></i> <?= h($e) ?><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="/student_course_hub/staff/profile.php">

                <div class="form-section">
                    <p class="form-section-title">Personal Information</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="Name">Full Name *</label>
                            <input type="text" id="Name" name="Name"
                                   value="<?= h($staff['Name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="Title">Job Title</label>
                            <input type="text" id="Title" name="Title"
                                   value="<?= h($staff['Title'] ?? '') ?>"
                                   placeholder="e.g. Senior Lecturer">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="Department">Department</label>
                        <input type="text" id="Department" name="Department"
                               value="<?= h($staff['Department'] ?? '') ?>"
                               placeholder="e.g. School of Computing">
                    </div>
                    <div class="form-group">
                        <label for="Bio">Biography</label>
                        <textarea id="Bio" name="Bio" rows="5"
                                  placeholder="A short bio visible to students on module and programme pages…"><?= h($staff['Bio'] ?? '') ?></textarea>
                        <span class="form-hint">This appears on your public staff profile.</span>
                    </div>
                </div>

                <div class="form-section">
                    <p class="form-section-title">Change Password</p>
                    <p style="font-size:0.83rem; color:var(--grey-600); margin-bottom:16px">
                        Leave these fields blank if you do not want to change your password.
                    </p>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password"
                                   autocomplete="new-password" placeholder="Minimum 8 characters">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   autocomplete="new-password">
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-staff-primary">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                    <a href="/student_course_hub/staff/dashboard.php" class="btn btn-outline">Cancel</a>
                </div>

            </form>
        </div>

    </div>
</div>
</div>
</body>
</html>