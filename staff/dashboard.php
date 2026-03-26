<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!isset($_SESSION['staff_id'])) {
    redirect(BASE_URL . '/staff/login.php');
}

$db      = getDB();
$staffId = (int)$_SESSION['staff_id'];

// Full staff profile
$stmt = $db->prepare("SELECT * FROM Staff WHERE StaffID = ?");
$stmt->execute([$staffId]);
$staff = $stmt->fetch();

// Modules this staff member leads
$modStmt = $db->prepare("
    SELECT m.*,
           COUNT(DISTINCT pm.ProgrammeID) AS ProgrammeCount
    FROM Modules m
    LEFT JOIN ProgrammeModules pm ON pm.ModuleID = m.ModuleID
    WHERE m.ModuleLeaderID = ?
    GROUP BY m.ModuleID
    ORDER BY m.ModuleName
");
$modStmt->execute([$staffId]);
$modules = $modStmt->fetchAll();

// Programmes that include those modules
$progStmt = $db->prepare("
    SELECT DISTINCT p.ProgrammeID, p.ProgrammeName, p.Description, p.Image,
                    l.LevelName, p.LevelID,
                    m.ModuleName AS ViaModule
    FROM ProgrammeModules pm
    JOIN Programmes p ON pm.ProgrammeID = p.ProgrammeID
    JOIN Levels l ON p.LevelID = l.LevelID
    JOIN Modules m ON pm.ModuleID = m.ModuleID
    WHERE m.ModuleLeaderID = ?
    AND p.Published = 1
    ORDER BY p.ProgrammeName
");
$progStmt->execute([$staffId]);
$programmes = $progStmt->fetchAll();

// Programmes this staff member leads directly
$leadStmt = $db->prepare("
    SELECT p.ProgrammeID, p.ProgrammeName, l.LevelName,
           COUNT(DISTINCT i.InterestID) AS InterestCount
    FROM Programmes p
    JOIN Levels l ON p.LevelID = l.LevelID
    LEFT JOIN InterestedStudents i ON i.ProgrammeID = p.ProgrammeID
    WHERE p.ProgrammeLeaderID = ?
    GROUP BY p.ProgrammeID
");
$leadStmt->execute([$staffId]);
$leadProgrammes = $leadStmt->fetchAll();

$pageTitle = 'Staff Dashboard';

// Build initials
$initials = '';
foreach (explode(' ', $staff['Name']) as $part) $initials .= strtoupper(substr($part, 0, 1));
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($staff['Name']) ?> — UniHub Staff Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/student_course_hub/assets/css/admin.css">
    <style>
        :root {
            --staff-navy: #1a3a4a;
            --staff-navy-dark: #122835;
            --staff-navy-light: #e8f0f4;
        }

        /* Override sidebar KU red to staff navy */
        .sidebar { background: var(--staff-navy); border-right-color: var(--staff-navy-dark); }
        .sidebar-nav a.active { background: var(--staff-navy-dark); border-left-color: var(--white); }
        .sidebar-nav a:hover { background: rgba(0,0,0,0.2); border-left-color: rgba(255,255,255,0.5); }
        .sidebar-footer a.danger:hover { color: #ef9a9a; }
        *:focus-visible { outline-color: var(--staff-navy); }

        /* Profile hero card */
        .profile-hero {
            background: var(--staff-navy);
            color: var(--white);
            border-radius: 3px;
            padding: 32px 36px;
            display: flex;
            align-items: center;
            gap: 28px;
            margin-bottom: 28px;
        }
        .profile-avatar-lg {
            width: 72px; height: 72px; border-radius: 50%;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-serif);
            font-size: 1.6rem; font-weight: 700; color: var(--white);
            flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.2);
        }
        .profile-info h2 {
            font-family: var(--font-serif);
            font-size: 1.4rem; font-weight: 700; margin-bottom: 4px;
        }
        .profile-info .meta { color: rgba(255,255,255,0.65); font-size: 0.85rem; display: flex; gap: 16px; flex-wrap: wrap; }
        .profile-info .meta span { display: flex; align-items: center; gap: 6px; }

        /* Stat cards with navy top */
        .stat-card.navy { border-top-color: var(--staff-navy); }
        .stat-card.navy .stat-card-icon { color: var(--staff-navy); }

        /* Module card with navy accent */
        .module-item {
            background: var(--white);
            border: 1px solid var(--grey-200);
            border-left: 3px solid var(--staff-navy);
            padding: 20px 24px;
            margin-bottom: 1px;
            transition: background 0.18s ease;
        }
        .module-item:hover { background: var(--grey-50); }
        .module-item h4 { font-family: var(--font-serif); font-size: 0.95rem; font-weight: 700; margin-bottom: 6px; }
        .module-item p  { font-size: 0.83rem; color: var(--grey-600); margin-bottom: 10px; line-height: 1.5; }
        .module-meta    { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* Programme card grid */
        .prog-item {
            background: var(--white);
            border: 1px solid var(--grey-200);
            padding: 20px 24px;
            display: flex; align-items: flex-start; gap: 16px;
            margin-bottom: 1px;
            transition: background 0.18s ease;
        }
        .prog-item:hover { background: var(--grey-50); }
        .prog-icon {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--staff-navy-light);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .prog-icon i { color: var(--staff-navy); font-size: 1rem; }
        .prog-name { font-weight: 600; font-size: 0.88rem; margin-bottom: 4px; }
        .prog-via  { font-size: 0.78rem; color: var(--grey-600); }

        .section-title {
            font-family: var(--font-serif);
            font-size: 1rem; font-weight: 700;
            margin-bottom: 16px; padding-bottom: 10px;
            border-bottom: 2px solid var(--grey-200);
            display: flex; align-items: center; justify-content: space-between;
        }
        .section-title span {
            font-family: var(--font-sans);
            font-size: 0.75rem; font-weight: 500;
            color: var(--grey-400);
        }

        .empty-notice {
            text-align: center; padding: 40px;
            color: var(--grey-400); font-style: italic;
            font-size: 0.85rem;
            background: var(--white); border: 1px solid var(--grey-200);
        }

        .two-col-staff { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 900px) { .two-col-staff { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="admin-layout">

<!-- Sidebar -->
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
            <li>
                <a href="/student_course_hub/staff/dashboard.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 nav-icon"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="/student_course_hub/staff/profile.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
                    <i class="bi bi-person-circle nav-icon"></i> My Profile
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-section">
        <p class="sidebar-section-label">Teaching</p>
        <ul class="sidebar-nav">
            <li>
                <a href="/student_course_hub/staff/dashboard.php#modules">
                    <i class="bi bi-journal-text nav-icon"></i> My Modules
                    <?php if (count($modules) > 0): ?>
                    <span style="margin-left:auto; background:rgba(255,255,255,0.2); color:var(--white);
                                 font-size:0.7rem; font-weight:600; padding:1px 7px; border-radius:10px">
                        <?= count($modules) ?>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="/student_course_hub/staff/dashboard.php#programmes">
                    <i class="bi bi-mortarboard nav-icon"></i> My Programmes
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <a href="/student_course_hub/" target="_blank">
            <i class="bi bi-globe nav-icon"></i> Student Site
        </a>
        <a href="/student_course_hub/staff/logout.php" class="danger">
            <i class="bi bi-box-arrow-right nav-icon"></i> Sign Out
        </a>
    </div>
</aside>

<!-- Main content -->
<div class="admin-main">
    <header class="admin-topbar">
        <div class="topbar-left">
            <span class="topbar-title">UniHub</span>
            <span class="topbar-divider">/</span>
            <span class="topbar-page">Staff Portal</span>
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

        <!-- Profile Hero -->
        <div class="profile-hero">
            <div class="profile-avatar-lg"><?= h($initials) ?></div>
            <div class="profile-info">
                <h2><?= h($staff['Name']) ?></h2>
                <div class="meta">
                    <?php if (!empty($staff['Title'])): ?>
                    <span><i class="bi bi-award"></i> <?= h($staff['Title']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($staff['Department'])): ?>
                    <span><i class="bi bi-building"></i> <?= h($staff['Department']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($staff['Email'])): ?>
                    <span><i class="bi bi-envelope"></i> <?= h($staff['Email']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($staff['Bio'])): ?>
                <p style="margin-top:10px; color:rgba(255,255,255,0.65); font-size:0.85rem; font-weight:300; max-width:600px; line-height:1.6">
                    <?= h($staff['Bio']) ?>
                </p>
                <?php endif; ?>
            </div>
            <div style="margin-left:auto; flex-shrink:0">
                <a href="/student_course_hub/staff/profile.php"
                   style="display:inline-flex; align-items:center; gap:6px;
                          background:rgba(255,255,255,0.12); color:rgba(255,255,255,0.85);
                          border:1px solid rgba(255,255,255,0.2); padding:8px 16px;
                          border-radius:3px; font-size:0.82rem; font-weight:500; text-decoration:none;
                          transition:0.18s ease"
                   onmouseover="this.style.background='rgba(255,255,255,0.2)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.12)'">
                    <i class="bi bi-pencil"></i> Edit Profile
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stat-cards" style="margin-bottom:32px">
            <div class="stat-card navy">
                <div class="stat-card-icon"><i class="bi bi-journal-text"></i></div>
                <div class="stat-card-value"><?= count($modules) ?></div>
                <div class="stat-card-label">Modules Leading</div>
                <div class="stat-card-sub">As module leader</div>
            </div>
            <div class="stat-card navy">
                <div class="stat-card-icon"><i class="bi bi-mortarboard"></i></div>
                <div class="stat-card-value"><?= count($programmes) ?></div>
                <div class="stat-card-label">Programmes</div>
                <div class="stat-card-sub">Include your modules</div>
            </div>
            <div class="stat-card navy">
                <div class="stat-card-icon"><i class="bi bi-star"></i></div>
                <div class="stat-card-value"><?= count($leadProgrammes) ?></div>
                <div class="stat-card-label">Programme Leader</div>
                <div class="stat-card-sub">Programmes you lead</div>
            </div>
            <div class="stat-card navy">
                <div class="stat-card-icon"><i class="bi bi-building"></i></div>
                <div class="stat-card-value" style="font-size:1rem; padding-top:4px; line-height:1.3">
                    <?= !empty($staff['Department']) ? h($staff['Department']) : '—' ?>
                </div>
                <div class="stat-card-label">Department</div>
            </div>
        </div>

        <div class="two-col-staff">

            <!-- Modules I lead -->
            <div id="modules">
                <div class="section-title">
                    <span style="font-family:var(--font-serif)">
                        <i class="bi bi-journal-text" style="color:var(--staff-navy); margin-right:8px"></i>
                        Modules I Lead
                    </span>
                    <span><?= count($modules) ?> module<?= count($modules) !== 1 ? 's' : '' ?></span>
                </div>

                <?php if (empty($modules)): ?>
                <div class="empty-notice">
                    <i class="bi bi-journal-x" style="font-size:1.5rem; display:block; margin-bottom:8px; color:var(--grey-300)"></i>
                    You are not currently assigned as a module leader.
                </div>
                <?php else: foreach ($modules as $mod): ?>
                <div class="module-item">
                    <h4><?= h($mod['ModuleName']) ?></h4>
                    <?php if (!empty($mod['Description'])): ?>
                    <p><?= h(substr($mod['Description'], 0, 120)) ?><?= strlen($mod['Description']) > 120 ? '…' : '' ?></p>
                    <?php endif; ?>
                    <div class="module-meta">
                        <span class="badge badge-blue">
                            <i class="bi bi-mortarboard"></i>
                            <?= $mod['ProgrammeCount'] ?> programme<?= $mod['ProgrammeCount'] !== 1 ? 's' : '' ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Programmes that include my modules -->
            <div id="programmes">
                <div class="section-title">
                    <span style="font-family:var(--font-serif)">
                        <i class="bi bi-mortarboard" style="color:var(--staff-navy); margin-right:8px"></i>
                        Programmes Using My Modules
                    </span>
                    <span><?= count($programmes) ?> programme<?= count($programmes) !== 1 ? 's' : '' ?></span>
                </div>

                <?php if (empty($programmes)): ?>
                <div class="empty-notice">
                    <i class="bi bi-mortarboard" style="font-size:1.5rem; display:block; margin-bottom:8px; color:var(--grey-300)"></i>
                    None of your modules appear in any published programmes yet.
                </div>
                <?php else: foreach ($programmes as $prog): ?>
                <div class="prog-item">
                    <div class="prog-icon">
                        <i class="bi bi-<?= $prog['LevelID'] == 1 ? 'book' : 'journal-bookmark' ?>"></i>
                    </div>
                    <div style="flex:1; min-width:0">
                        <div class="prog-name"><?= h($prog['ProgrammeName']) ?></div>
                        <div class="prog-via">
                            <span class="badge <?= $prog['LevelID'] == 1 ? 'badge-blue' : 'badge-orange' ?>" style="margin-right:6px">
                                <?= h($prog['LevelName']) ?>
                            </span>
                            via <em><?= h($prog['ViaModule']) ?></em>
                        </div>
                    </div>
                    <a href="/student_course_hub/programme.php?id=<?= (int)$prog['ProgrammeID'] ?>"
                       target="_blank"
                       style="color:var(--staff-navy); font-size:0.85rem; text-decoration:none; flex-shrink:0; margin-left:8px"
                       title="View public page">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>
                <?php endforeach; endif; ?>

                <?php if (!empty($leadProgrammes)): ?>
                <div class="section-title" style="margin-top:24px">
                    <span style="font-family:var(--font-serif)">
                        <i class="bi bi-star" style="color:var(--staff-navy); margin-right:8px"></i>
                        Programmes I Lead
                    </span>
                </div>
                <?php foreach ($leadProgrammes as $lp): ?>
                <div class="prog-item">
                    <div class="prog-icon" style="background:#fff8e1">
                        <i class="bi bi-star-fill" style="color:#f59e0b"></i>
                    </div>
                    <div style="flex:1; min-width:0">
                        <div class="prog-name"><?= h($lp['ProgrammeName']) ?></div>
                        <div class="prog-via">
                            <?= h($lp['LevelName']) ?> &mdash;
                            <span class="badge badge-grey"><?= $lp['InterestCount'] ?> interested</span>
                        </div>
                    </div>
                    <a href="/student_course_hub/programme.php?id=<?= (int)$lp['ProgrammeID'] ?>"
                       target="_blank"
                       style="color:var(--staff-navy); font-size:0.85rem; text-decoration:none; flex-shrink:0">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div><!-- /.admin-content -->
</div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<script>
// Smooth scroll for anchor links in sidebar
document.querySelectorAll('a[href*="#"]').forEach(link => {
    link.addEventListener('click', e => {
        const hash = link.getAttribute('href').split('#')[1];
        const el = document.getElementById(hash);
        if (el) {
            e.preventDefault();
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>