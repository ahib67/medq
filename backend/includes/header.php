<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title ?? 'MedQ') ?> - MedQ</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= BASE_URL ?>/index.php">MedQ</a>
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
            <span>Hi, <?= e($_SESSION['name']) ?> (<?= e($_SESSION['role']) ?>)</span>
            <a href="<?= BASE_URL ?>/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php">Login</a>
            <a href="<?= BASE_URL ?>/register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
<?php show_flash(); ?>
