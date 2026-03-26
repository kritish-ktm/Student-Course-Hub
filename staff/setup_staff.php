<?php
/**
 * Staff Portal — Account Setup Helper
 * Run this once to assign email + password to a staff member.
 * DELETE this file after use.
 *
 * Usage: visit /student_course_hub/staff/setup_staff.php
 */
session_start();
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// ----------------------------------------------------------------
// Edit these defaults before running, or submit the form below
// ----------------------------------------------------------------
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffId  = (int)($_POST['staff_id'] ?? 0);
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$staffId || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $check = $db->prepare("SELECT StaffID FROM Staff WHERE StaffID = ?");
        $check->execute([$staffId]);
        if (!$check->fetch()) {
            $error = "No staff member found with ID $staffId.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE Staff SET Email = ?, PasswordHash = ? WHERE StaffID = ?")
               ->execute([$email, $hash, $staffId]);
            $message = "Staff account set up successfully. They can now log in at <a href='/student_course_hub/staff/login.php'>staff/login.php</a>.";
        }
    }
}

$allStaff = $db->query("SELECT StaffID, Name, Department, Email FROM Staff ORDER BY Name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Account Setup — UniHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'IBM Plex Sans', sans-serif; background: #f5f5f5; margin: 0; padding: 40px; color: #111; }
        .card { background: #fff; border: 1px solid #e0e0e0; border-top: 3px solid #1a3a4a; max-width: 520px; padding: 32px; }
        h1 { font-size: 1.1rem; margin-bottom: 4px; }
        p.sub { color: #666; font-size: 0.85rem; margin-bottom: 24px; }
        label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: #333; }
        input, select { width: 100%; padding: 9px 11px; border: 1px solid #ddd; font-family: inherit; font-size: 0.88rem; margin-bottom: 14px; box-sizing: border-box; }
        button { background: #1a3a4a; color: #fff; border: none; padding: 10px 20px; font-family: inherit; font-size: 0.88rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #122835; }
        .success { background: #e8f5e9; color: #1b5e20; border-left: 3px solid #2e7d32; padding: 10px 14px; margin-bottom: 16px; font-size: 0.88rem; }
        .error   { background: #fce4e4; color: #7b1c1c; border-left: 3px solid #b71c1c; padding: 10px 14px; margin-bottom: 16px; font-size: 0.88rem; }
        .warning { background: #fff3cd; color: #856404; border-left: 3px solid #ffc107; padding: 10px 14px; margin-top: 20px; font-size: 0.82rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 0.82rem; }
        th { text-align: left; padding: 8px; background: #f7f7f7; border-bottom: 2px solid #e0e0e0; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: #666; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .has-account { color: #2e7d32; }
        .no-account  { color: #9e9e9e; font-style: italic; }
    </style>
</head>
<body>
<div class="card">
    <h1>Staff Account Setup</h1>
    <p class="sub">Assign an email address and password to a staff member so they can log into the Staff Portal.</p>

    <?php if ($message): ?>
    <div class="success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="staff_id">Staff Member</label>
        <select id="staff_id" name="staff_id" required>
            <option value="">Select a staff member…</option>
            <?php foreach ($allStaff as $s): ?>
            <option value="<?= $s['StaffID'] ?>"><?= htmlspecialchars($s['Name']) ?> (<?= htmlspecialchars($s['Department'] ?? 'No dept') ?>)</option>
            <?php endforeach; ?>
        </select>

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="e.g. a.johnson@unihub.ac.uk" required>

        <label for="password">Password (min. 8 characters)</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Set Up Account</button>
    </form>

    <div class="warning">⚠ DELETE this file (staff/setup_staff.php) after use!</div>

    <table>
        <thead><tr><th>Name</th><th>Department</th><th>Account</th></tr></thead>
        <tbody>
        <?php foreach ($allStaff as $s): ?>
        <tr>
            <td><?= htmlspecialchars($s['Name']) ?></td>
            <td><?= htmlspecialchars($s['Department'] ?? '—') ?></td>
            <td>
                <?php if (!empty($s['Email'])): ?>
                <span class="has-account">✓ <?= htmlspecialchars($s['Email']) ?></span>
                <?php else: ?>
                <span class="no-account">No account</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>