<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

// Period to analyse: only 7, 30 or 90 days are allowed
$days = (int)($_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90])) $days = 30;

// 1. PEAK HOURS: how many patients were called in each hour of the day
$stmt = $pdo->prepare('SELECT HOUR(called_at) AS hr, COUNT(*) AS visits
                         FROM tokens
                        WHERE called_at IS NOT NULL AND token_date >= CURDATE() - INTERVAL ? DAY
                        GROUP BY HOUR(called_at)
                        ORDER BY hr');
$stmt->execute([$days]);
$peak = $stmt->fetchAll();

$max_visits = 1;
foreach ($peak as $p) {
    if ($p['visits'] > $max_visits) $max_visits = $p['visits'];
}

// 2. NO-SHOW RATE overall = no-shows / (completed + no-shows)
$stmt = $pdo->prepare("SELECT SUM(status = 'no_show') AS no_shows,
                              SUM(status IN ('completed','no_show')) AS finished
                         FROM tokens
                        WHERE token_date >= CURDATE() - INTERVAL ? DAY");
$stmt->execute([$days]);
$overall   = $stmt->fetch();
$no_shows  = (int)$overall['no_shows'];
$finished  = (int)$overall['finished'];
$rate      = $finished > 0 ? round($no_shows * 100 / $finished, 1) : 0;

// 2b. No-show rate per doctor
$stmt = $pdo->prepare("SELECT u.name AS doctor,
                              SUM(t.status = 'no_show') AS no_shows,
                              SUM(t.status IN ('completed','no_show')) AS finished
                         FROM tokens t
                         JOIN doctors d ON d.doctor_id = t.doctor_id
                         JOIN users u ON u.user_id = d.user_id
                        WHERE t.token_date >= CURDATE() - INTERVAL ? DAY
                        GROUP BY d.doctor_id, u.name
                        ORDER BY u.name");
$stmt->execute([$days]);
$by_doctor = $stmt->fetchAll();

// 3. AVERAGE WAIT per doctor (minutes between "ready to be seen" and "called")
// For tokens booked days earlier, waiting starts at the beginning of the visit day, not at booking time.
// GREATEST() picks the later of the two moments.
$stmt = $pdo->prepare("SELECT u.name AS doctor, COUNT(*) AS patients,
                              ROUND(AVG(TIMESTAMPDIFF(MINUTE,
                                    GREATEST(t.booked_at, CAST(t.token_date AS DATETIME)), t.called_at)), 1) AS avg_wait
                         FROM tokens t
                         JOIN doctors d ON d.doctor_id = t.doctor_id
                         JOIN users u ON u.user_id = d.user_id
                        WHERE t.called_at IS NOT NULL AND t.token_date >= CURDATE() - INTERVAL ? DAY
                        GROUP BY d.doctor_id, u.name
                        ORDER BY u.name");
$stmt->execute([$days]);
$waits = $stmt->fetchAll();

$page_title = 'Analytics';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Analytics</h2>

<form method="get">
    <label>Period
        <select name="days" onchange="this.form.submit()" style="width:auto">
            <option value="7"  <?= $days == 7 ? 'selected' : '' ?>>Last 7 days</option>
            <option value="30" <?= $days == 30 ? 'selected' : '' ?>>Last 30 days</option>
            <option value="90" <?= $days == 90 ? 'selected' : '' ?>>Last 90 days</option>
        </select>
    </label>
</form>

<div class="cards">
    <div class="card"><b><?= e($rate) ?>%</b>No-show rate</div>
    <div class="card"><b><?= e($no_shows) ?></b>No-shows</div>
    <div class="card"><b><?= e($finished) ?></b>Finished visits</div>
</div>

<h3>Peak hours</h3>
<table>
    <tr><th>Hour</th><th>Patients called</th><th></th></tr>
    <?php foreach ($peak as $p): ?>
        <tr>
            <td><?= e(sprintf('%02d:00', $p['hr'])) ?></td>
            <td><?= e($p['visits']) ?></td>
            <td><div style="background:#0b5c8f; height:14px; width:<?= round($p['visits'] * 100 / $max_visits) ?>%"></div></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$peak): ?><tr><td colspan="3">No data for this period.</td></tr><?php endif; ?>
</table>

<h3>No-show rate per doctor</h3>
<table>
    <tr><th>Doctor</th><th>No-shows</th><th>Finished visits</th><th>Rate</th></tr>
    <?php foreach ($by_doctor as $d): ?>
        <tr>
            <td><?= e($d['doctor']) ?></td>
            <td><?= e($d['no_shows']) ?></td>
            <td><?= e($d['finished']) ?></td>
            <td><?= $d['finished'] > 0 ? e(round($d['no_shows'] * 100 / $d['finished'], 1)) . '%' : '-' ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$by_doctor): ?><tr><td colspan="4">No data for this period.</td></tr><?php endif; ?>
</table>

<h3>Average waiting time per doctor</h3>
<table>
    <tr><th>Doctor</th><th>Patients seen</th><th>Average wait (minutes)</th></tr>
    <?php foreach ($waits as $w): ?>
        <tr>
            <td><?= e($w['doctor']) ?></td>
            <td><?= e($w['patients']) ?></td>
            <td><?= e($w['avg_wait']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$waits): ?><tr><td colspan="3">No data for this period.</td></tr><?php endif; ?>
</table>
<?php include '../includes/footer.php'; ?>
