<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("
    SELECT p.*, l.LevelName, s.Name AS LeaderName
    FROM Programmes p
    JOIN Levels l ON p.LevelID = l.LevelID
    LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
    WHERE p.ProgrammeID = ? AND p.Published = 1
");
$stmt->execute([$id]);
$programme = $stmt->fetch();

if (!$programme) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty-state" style="padding:80px 0">
        <i class="bi bi-exclamation-circle"></i>
        <h3>Programme Not Found</h3>
        <p>This programme does not exist or is not currently available.</p>
        <a href="/student_course_hub/programmes.php" class="btn btn-primary" style="margin-top:20px">Back to Programmes</a>
    </div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$modules = $db->prepare("
    SELECT m.*, pm.Year,
           s.Name AS LeaderName,
           s.Title AS LeaderTitle,
           s.Department AS LeaderDepartment,
           (SELECT COUNT(*) FROM ProgrammeModules pm2 WHERE pm2.ModuleID = m.ModuleID) AS SharedCount
    FROM ProgrammeModules pm
    JOIN Modules m ON pm.ModuleID = m.ModuleID
    LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
    WHERE pm.ProgrammeID = ?
    ORDER BY pm.Year, m.ModuleName
");
$modules->execute([$id]);
$allModules = $modules->fetchAll();

$byYear = [];
foreach ($allModules as $mod) { $byYear[$mod['Year']][] = $mod; }
ksort($byYear);
$years = array_keys($byYear);

$formErrors = [];
$formSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_interest'])) {
    $name  = sanitize($_POST['student_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (empty($name))  $formErrors['name']  = 'Please enter your name.';
    if (empty($email)) $formErrors['email'] = 'Please enter your email address.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $formErrors['email'] = 'Please enter a valid email address.';

    if (empty($formErrors)) {
        $check = $db->prepare("SELECT InterestID FROM InterestedStudents WHERE ProgrammeID = ? AND Email = ?");
        $check->execute([$id, $email]);
        if ($check->fetch()) {
            $formErrors['email'] = 'You have already registered interest in this programme.';
        } else {
            $db->prepare("INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email) VALUES (?, ?, ?)")
               ->execute([$id, $name, $email]);
            $formSuccess = true;
        }
    }
}

$pageTitle = $programme['ProgrammeName'];
include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="/student_course_hub/">Home</a>
            <span>›</span>
            <a href="/student_course_hub/programmes.php">Programmes</a>
            <span>›</span>
            <span aria-current="page"><?= h($programme['ProgrammeName']) ?></span>
        </nav>
    </div>
</div>

<!-- Programme Hero -->
<div class="programme-hero">
    <?php if (!empty($programme['Image'])): ?>
    <div class="programme-hero-bg" style="background-image:url('<?= h($programme['Image']) ?>')"></div>
    <?php endif; ?>
    <div class="container">
        <div class="programme-hero-inner">
            <div>
                <p class="prog-kicker"><?= h($programme['LevelName']) ?></p>
                <h1><?= h($programme['ProgrammeName']) ?></h1>
                <p class="lead"><?= h($programme['Description'] ?? '') ?></p>
                <div class="meta-chips">
                    <span class="meta-chip"><i class="bi bi-calendar3"></i> <?= $programme['LevelID'] == 1 ? '3 Years Full-time' : '1 Year Full-time' ?></span>
                    <span class="meta-chip"><i class="bi bi-journal-text"></i> <?= count($allModules) ?> Modules</span>
                    <span class="meta-chip"><i class="bi bi-geo-alt"></i> Copenhagen</span>
                </div>
                <div class="programme-leader-row">
                    <?php
                    $initials = '';
                    if (!empty($programme['LeaderName'])) {
                        foreach (explode(' ', $programme['LeaderName']) as $p) $initials .= strtoupper(substr($p,0,1));
                        $initials = substr($initials, 0, 2);
                    }
                    ?>
                    <div class="leader-avatar"><?= h($initials ?: '?') ?></div>
                    <div class="leader-info">
                        <small>Programme Leader</small>
                        <strong><?= h($programme['LeaderName'] ?? 'TBC') ?></strong>
                    </div>
                </div>
            </div>
            <div class="programme-hero-img">
                <?php if (!empty($programme['Image'])): ?>
                    <img src="<?= h($programme['Image']) ?>" alt="">
                <?php else: ?>
                    <div class="card-image-placeholder" style="height:220px"><i class="bi bi-mortarboard" style="font-size:3rem; color:var(--grey-400)"></i></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modules -->
<section class="section">
    <div class="container">
        <div class="reveal" style="margin-bottom:32px">
            <div class="ku-divider"></div>
            <h2 class="section-heading">Programme Modules</h2>
            <p class="section-sub">Browse the modules you'll study across each year of the programme.</p>
        </div>

        <?php if (!empty($years)): ?>
        <div class="year-tabs" role="tablist">
            <?php foreach ($years as $i => $year): ?>
            <button class="year-tab <?= $i === 0 ? 'active' : '' ?>"
                    role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                    aria-controls="year-<?= $year ?>" data-year="<?= $year ?>"
                    id="tab-year-<?= $year ?>">
                <i class="bi bi-<?= $year ?>-circle<?= $i === 0 ? '-fill' : '' ?>"></i> Year <?= $year ?>
            </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($years as $i => $year): ?>
        <div class="year-panel <?= $i === 0 ? 'active' : '' ?>"
             id="year-<?= $year ?>" role="tabpanel" aria-labelledby="tab-year-<?= $year ?>">
            <div class="modules-grid">
                <?php foreach ($byYear[$year] as $mod): ?>
                <div class="module-card">
                    <h4>
                        <?= h($mod['ModuleName']) ?>
                        <?php if ($mod['SharedCount'] > 1): ?>
                        <span class="shared-badge">Shared</span>
                        <?php endif; ?>
                    </h4>
                    <p><?= h($mod['Description'] ?? 'No description available.') ?></p>
                    <div class="module-leader">
                        <div class="module-leader-dot">
                            <?php
                            $mi = '';
                            if (!empty($mod['LeaderName'])) {
                                foreach (explode(' ', $mod['LeaderName']) as $pt) $mi .= strtoupper(substr($pt,0,1));
                                $mi = substr($mi, 0, 2);
                            }
                            echo h($mi ?: '?');
                            ?>
                        </div>
                        <div>
                            <span style="font-weight:600; font-size:0.82rem; display:block">
                                <?= h($mod['LeaderName'] ?? 'TBC') ?>
                            </span>
                            <?php if (!empty($mod['LeaderTitle']) || !empty($mod['LeaderDepartment'])): ?>
                            <span style="font-size:0.75rem; color:var(--grey-400); display:block">
                                <?= h($mod['LeaderTitle'] ?? '') ?>
                                <?php if (!empty($mod['LeaderTitle']) && !empty($mod['LeaderDepartment'])): ?> &mdash; <?php endif; ?>
                                <?= h($mod['LeaderDepartment'] ?? '') ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Interest Form -->
<div class="interest-section" id="interest">
    <div class="container">
        <div class="interest-inner">
            <div class="interest-text reveal">
                <div class="ku-divider"></div>
                <h2>Register Your Interest</h2>
                <p>
                    Sign up to receive updates about open days, application deadlines, and programme news
                    for <strong><?= h($programme['ProgrammeName']) ?></strong> directly to your inbox.
                </p>
                <div style="margin-top:24px; display:flex; flex-direction:column; gap:12px">
                    <div style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.75); font-size:0.88rem">
                        <i class="bi bi-check-circle" style="color:rgba(255,255,255,0.5)"></i>
                        Open day invitations
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.75); font-size:0.88rem">
                        <i class="bi bi-check-circle" style="color:rgba(255,255,255,0.5)"></i>
                        Application deadline reminders
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.75); font-size:0.88rem">
                        <i class="bi bi-check-circle" style="color:rgba(255,255,255,0.5)"></i>
                        Programme updates & news
                    </div>
                </div>
            </div>
            <div class="interest-form reveal reveal-delay-1">
                <?php if ($formSuccess): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    You're registered! We'll be in touch with updates about <?= h($programme['ProgrammeName']) ?>.
                </div>
                <?php else: ?>
                <h3>Express Your Interest</h3>
                <form method="POST" action="/student_course_hub/programme.php?id=<?= $id ?>#interest" novalidate>
                    <input type="hidden" name="register_interest" value="1">
                    <div class="form-group">
                        <label for="student_name">Full Name</label>
                        <input type="text" id="student_name" name="student_name"
                               value="<?= isset($_POST['student_name']) ? h($_POST['student_name']) : '' ?>"
                               placeholder="e.g. Jane Smith" autocomplete="name" required>
                        <?php if (isset($formErrors['name'])): ?>
                        <span class="form-error"><i class="bi bi-exclamation-circle"></i> <?= h($formErrors['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email"
                               value="<?= isset($_POST['email']) ? h($_POST['email']) : '' ?>"
                               placeholder="e.g. jane@example.com" autocomplete="email" required>
                        <?php if (isset($formErrors['email'])): ?>
                        <span class="form-error"><i class="bi bi-exclamation-circle"></i> <?= h($formErrors['email']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="form-submit">
                        <i class="bi bi-envelope-arrow-up"></i> Register Interest
                    </button>
                    <p style="font-size:0.75rem; color:var(--grey-400); margin-top:10px; text-align:center">
                        <i class="bi bi-shield-check"></i> We'll only use your email for programme updates.
                    </p>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>