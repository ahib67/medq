<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/patient.php';
require_role('patient');

$patient_id = current_patient_id($pdo);
$token_id   = (int)($_GET['id'] ?? 0);

// Load the token. "patient_id = ?" makes sure patients can only see their OWN tokens.
$stmt = $pdo->prepare('SELECT t.token_id, t.doctor_id, t.token_date, t.token_no, t.priority, t.status,
                              u.name AS doctor_name, d.room_no, d.avg_consult_mins
                         FROM tokens t
                         JOIN doctors d ON d.doctor_id = t.doctor_id
                         JOIN users u ON u.user_id = d.user_id
                        WHERE t.token_id = ? AND t.patient_id = ?');
$stmt->execute([$token_id, $patient_id]);
$t = $stmt->fetch();
if (!$t) {
    set_flash('error', 'Token not found.');
    redirect('patient/my_tokens.php');
}

$is_today = ($t['token_date'] === date('Y-m-d'));
$current_token = null;
$ahead = null;
$eta = null;

if ($is_today) {
    // Current token = the one being consulted right now (derived with a query, Approach B)
    $stmt = $pdo->prepare("SELECT token_no FROM tokens
                            WHERE status = 'in_consultation' AND doctor_id = ? AND token_date = ? LIMIT 1");
    $stmt->execute([$t['doctor_id'], $t['token_date']]);
    $current_token = $stmt->fetchColumn();

    if ($t['status'] === 'waiting') {
        // Queue order: emergency first, then senior, then normal, then by token number (FR-15)
        // Patients ahead of me = waiting tokens with a better priority, or same priority and smaller number
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tokens
             WHERE doctor_id = ? AND token_date = ? AND status = 'waiting' AND token_id <> ?
               AND ( FIELD(priority,'emergency','senior','normal') < FIELD(?,'emergency','senior','normal')
                     OR ( FIELD(priority,'emergency','senior','normal') = FIELD(?,'emergency','senior','normal')
                          AND token_no < ? ) )");
        $stmt->execute([$t['doctor_id'], $t['token_date'], $t['token_id'], $t['priority'], $t['priority'], $t['token_no']]);
        $ahead = (int)$stmt->fetchColumn();

        // Estimated wait = patients ahead x average consultation time (FR-16)
        $eta = $ahead * (int)$t['avg_consult_mins'];
    }
}

// Reload this page every 10 seconds while the token is active
if ($is_today && in_array($t['status'], ['waiting', 'in_consultation'])) {
    $extra_head = '<meta http-equiv="refresh" content="10">';
}

$page_title = 'Token status';
include '../includes/header.php';
include 'menu.php';
?>
<div class="box">
    <h2>Token #<?= e($t['token_no']) ?></h2>
    <p><b><?= e($t['doctor_name']) ?></b> - Room <?= e($t['room_no']) ?><br>
       Date: <?= e($t['token_date']) ?><br>
       Status: <b><?= e($t['status']) ?></b></p>

    <?php if ($t['status'] === 'in_consultation'): ?>
        <p class="flash success">It is your turn. Please go to the doctor's room.</p>
    <?php elseif (!$is_today): ?>
        <p>Live queue information appears on the day of your visit.</p>
    <?php elseif ($t['status'] === 'waiting'): ?>
        <div class="cards">
            <div class="card"><b><?= $current_token ? '#' . e($current_token) : '-' ?></b>Now serving</div>
            <div class="card"><b><?= e($ahead) ?></b>Patients ahead</div>
            <div class="card"><b><?= e($eta) ?> min</b>Estimated wait</div>
        </div>
        <p><small>This page refreshes every 10 seconds.</small></p>
    <?php endif; ?>

    <a href="my_tokens.php">Back to my tokens</a>
</div>
<?php include '../includes/footer.php'; ?>
