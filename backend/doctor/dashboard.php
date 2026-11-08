<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/queue.php';
require_role('doctor');

$doctor_id = current_doctor_id($pdo);   // a doctor can only manage his OWN queue

// ACTIONS: call next, complete, skip
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $token_id = (int)($_POST['token_id'] ?? 0);

    if ($action === 'call_next') {
        list($token_no, $error) = call_next($pdo, $doctor_id);
        if ($error) {
            set_flash('error', $error);
        } else {
            log_action($pdo, "Called token #$token_no (doctor $doctor_id)");
            set_flash('success', "Token #$token_no called.");
        }

    } elseif ($action === 'complete') {
        if (complete_consultation($pdo, $token_id, $doctor_id)) {
            log_action($pdo, "Completed token id $token_id (doctor $doctor_id)");
            set_flash('success', 'Consultation completed.');
        } else {
            set_flash('error', 'Could not complete this token.');
        }

    } elseif ($action === 'skip') {
        if (mark_no_show($pdo, $token_id, $doctor_id)) {
            log_action($pdo, "Skipped token id $token_id (doctor $doctor_id)");
            set_flash('success', 'Patient skipped (marked as no-show).');
        } else {
            set_flash('error', 'Could not skip this token.');
        }
    }
    redirect('doctor/dashboard.php');
}

// READ
$queue = get_queue($pdo, $doctor_id);

$stmt = $pdo->prepare('SELECT avg_consult_mins, room_no FROM doctors WHERE doctor_id = ?');
$stmt->execute([$doctor_id]);
$info = $stmt->fetch();

// Count the waiting patients and find the current one
$waiting = 0;
$current = null;
foreach ($queue as $t) {
    if ($t['status'] === 'waiting') $waiting++;
    if ($t['status'] === 'in_consultation') $current = $t;
}

$extra_head = '<meta http-equiv="refresh" content="15">';   // reload every 15 seconds
$page_title = 'Doctor Dashboard';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Today's queue</h2>

<div class="cards">
    <div class="card"><b><?= $current ? '#' . e($current['token_no']) : '-' ?></b>Now serving</div>
    <div class="card"><b><?= e($waiting) ?></b>Waiting</div>
    <div class="card"><b><?= e($info['avg_consult_mins']) ?> min</b>Average per patient</div>
    <div class="card"><b><?= e($info['room_no']) ?></b>Room</div>
</div>

<form method="post">
    <button type="submit" name="action" value="call_next">Call next patient</button>
</form>

<table>
    <tr><th>Token</th><th>Patient</th><th>Priority</th><th>Type</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($queue as $t): ?>
        <tr>
            <td>#<?= e($t['token_no']) ?></td>
            <td><?= e($t['patient_name']) ?></td>
            <td><?= e($t['priority']) ?></td>
            <td><?= e($t['booking_type']) ?></td>
            <td><?= e($t['status']) ?></td>
            <td>
                <form method="post" style="display:inline">
                    <input type="hidden" name="token_id" value="<?= e($t['token_id']) ?>">
                    <?php if ($t['status'] === 'in_consultation'): ?>
                        <button name="action" value="complete" style="margin:0;padding:4px 8px">Complete</button>
                        <button name="action" value="skip" style="margin:0;padding:4px 8px">Skip</button>
                    <?php elseif ($t['status'] === 'waiting'): ?>
                        <button name="action" value="skip" style="margin:0;padding:4px 8px">Skip</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$queue): ?><tr><td colspan="6">No tokens today.</td></tr><?php endif; ?>
</table>
<p><small>This page refreshes every 15 seconds.</small></p>
<?php include '../includes/footer.php'; ?>
