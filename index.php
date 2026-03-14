<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = getDB();

// Featured programmes (3 random published)
$featured = $db->query("
    SELECT p.*, l.LevelName, s.Name AS LeaderName
    FROM Programmes p
    JOIN Levels l ON p.LevelID = l.LevelID
    LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
    WHERE p.Published = 1
    ORDER BY RAND()
    LIMIT 3
")->fetchAll();

// Stats
$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM Programmes WHERE Published = 1) AS total_programmes,
        (SELECT COUNT(*) FROM Modules) AS total_modules,
        (SELECT COUNT(*) FROM Staff) AS total_staff,
        (SELECT COUNT(*) FROM InterestedStudents) AS total_interest
")->fetch();

$pageTitle = 'Welcome';
include __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <span class="hero-tag">University Programmes</span>
            <h1>Find Your <em>Perfect</em><br>Programme</h1>
            <p>Explore undergraduate and postgraduate degrees taught by world-class faculty. Register your interest and take the first step toward your future.</p>
            <div class="hero-actions">
                <a href="/programmes.php" class="btn btn-primary">Browse All Programmes →</a>
                <a href="/programmes.php?level=2" class="btn btn-outline">Postgraduate</a>
            </div>
        </div>
    </div>
</section>

<div class="stats-bar">
    <div class="container">
        <div class="stats-inner">
            <div class="stat-item">
                <span class="stat-number"><?= $stats['total_programmes'] ?></span>
                <span class="stat-label">Programmes</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $stats['total_modules'] ?></span>
                <span class="stat-label">Modules</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $stats['total_staff'] ?></span>
                <span class="stat-label">Academic Staff</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $stats['total_interest'] ?>+</span>
                <span class="stat-label">Students Registered</span>
            </div>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="section-header-row">
            <div class="section-header" style="margin-bottom:0">
                <h2>Featured Programmes</h2>
                <p>A selection of our most popular degree programmes.</p>
            </div>
            <a href="/programmes.php" class="btn btn-outline btn-dark">View All →</a>
        </div>
        <div class="programme-grid" style="margin-top:32px">
            <?php foreach ($featured as $prog): ?>
            <a href="/programme.php?id=<?= $prog['ProgrammeID'] ?>" class="programme-card">
                <div class="card-image-wrap">
                    <?php if (!empty($prog['Image'])): ?>
                        <img src="<?= h($prog['Image']) ?>" alt="<?= h($prog['ProgrammeName']) ?>">
                    <?php else: ?>
                        <div class="card-image-placeholder" aria-hidden="true">🎓</div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <span class="card-level <?= $prog['LevelID'] == 1 ? 'level-ug' : 'level-pg' ?>">
                        <?= h($prog['LevelName']) ?>
                    </span>
                    <h3><?= h($prog['ProgrammeName']) ?></h3>
                    <p><?= h(substr($prog['Description'] ?? '', 0, 100)) ?>…</p>
                    <div class="card-meta">
                        <span>Led by <?= h($prog['LeaderName'] ?? 'TBC') ?></span>
                        <span class="card-arrow">→</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:60px; align-items:center">
            <div>
                <h2 style="font-family:var(--font-display); font-size:2rem; font-weight:900; margin-bottom:16px">Undergraduate<br>& Postgraduate</h2>
                <p style="color:var(--muted); margin-bottom:24px">Whether you're starting your academic journey or advancing your career, we have a programme for you. Filter by level, search by keyword, and register your interest in seconds.</p>
                <a href="/programmes.php?level=1" class="btn btn-primary" style="margin-right:12px">Undergraduate</a>
                <a href="/programmes.php?level=2" class="btn btn-outline btn-dark">Postgraduate</a>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                <div style="background:var(--white); border:1px solid var(--border); border-radius:12px; padding:24px; text-align:center">
                    <div style="font-size:2rem; margin-bottom:8px">🎓</div>
                    <strong>BSc Degrees</strong>
                    <p style="color:var(--muted); font-size:0.85rem; margin-top:4px">3-year undergraduate programmes</p>
                </div>
                <div style="background:var(--white); border:1px solid var(--border); border-radius:12px; padding:24px; text-align:center">
                    <div style="font-size:2rem; margin-bottom:8px">🔬</div>
                    <strong>MSc Degrees</strong>
                    <p style="color:var(--muted); font-size:0.85rem; margin-top:4px">1-year specialist programmes</p>
                </div>
                <div style="background:var(--white); border:1px solid var(--border); border-radius:12px; padding:24px; text-align:center">
                    <div style="font-size:2rem; margin-bottom:8px">💻</div>
                    <strong>Tech-Focused</strong>
                    <p style="color:var(--muted); font-size:0.85rem; margin-top:4px">Computing & AI disciplines</p>
                </div>
                <div style="background:var(--white); border:1px solid var(--border); border-radius:12px; padding:24px; text-align:center">
                    <div style="font-size:2rem; margin-bottom:8px">📧</div>
                    <strong>Stay Updated</strong>
                    <p style="color:var(--muted); font-size:0.85rem; margin-top:4px">Register interest & get emails</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
