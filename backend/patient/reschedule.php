<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/patient.php';
require_role('patient');

$patient_id = current_patient_id($pdo);
$token_id   = (int)($_GET['id'] ?? 0);

// Load my waiting token
$stmt = $pdo->prepare("SELECT t.token_id, t.doctor_id, t.token_date, t.token_no, u.name AS doctor_name
                         FROM tokens t
                         JOIN doctors d ON d.doctor_id = t.doctor_id
                         JOIN users u ON u.user_id = d.user_id
                        WHERE t.token_id = ? AND t.patient_id = ? AND t.status = 'waiting'");
$stmt->execute([$token_id, $patient_id]);
$old = $stmt->fetch();
if (!$old) {
    set_flash('error', 'This token cannot be rescheduled.');
    redirect('patient/my_tokens.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['date'] ?? '';

    if ($new_date === $old['token_date']) {
        $error = 'Choose a different date.';
    } else {
        // 1. Book the new token first
        list($new_id, $error) = issue_token($pdo, $old['doctor_id'], $patient_id, $new_date);

        // 2. Only if that worked, cancel the old one
        if ($new_id) {
            $stmt = $pdo->prepare("UPDATE tokens SET status = 'cancelled' WHERE token_id = ? AND status = 'waiting'");
            $stmt->execute([$old['token_id']]);
            set_flash('success', 'Token rescheduled.');
            redirect('patient/token_status.php?id=' . $new_id);
        }
    }
}

$page_title = 'Reschedule';
include '../includes/header.php';
include 'menu.php';
?>
<div class="box">
    <h2>Reschedule token #<?= e($old['token_no']) ?></h2>
    <p>Doctor: <b><?= e($old['doctor_name']) ?></b><br>Current date: <?= e($old['token_date']) ?></p>

    <?php if ($error): ?><p class="flash error"><?= e($error) ?></p><?php endif; ?>

    <form method="post">
        <label>New date
            <input type="date" name="date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
        </label>
        <button type="submit">Reschedule</button>
        <a href="my_tokens.php">Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
