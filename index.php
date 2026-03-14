<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

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