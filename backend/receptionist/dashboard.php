<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/queue.php';
require_role('receptionist');

// All doctors for the dropdown
$doctors = $pdo->query('SELECT d.doctor_id, u.name, d.room_no
                          FROM doctors d JOIN users u ON u.user_id = d.user_id
                         ORDER BY u.name')->fetchAll();

$doctor_id = (int)($_GET['doctor_id'] ?? $_POST['doctor_id'] ?? 0);

// ACTIONS: mark no-show or cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doctor_id > 0) {
    $action   = $_POST['action'] ?? '';
    $token_id = (int)($_POST['token_id'] ?? 0);

    if ($action === 'no_show') {
        if (mark_no_show($pdo, $token_id, $doctor_id)) {
            log_action($pdo, "Marked token id $token_id as no-show (doctor $doctor_id)");
            set_flash('success', 'Marked as no-show.');
        } else {
            set_flash('error', 'Could not change this token.');
        }
    } elseif ($action === 'cancel') {
        $stmt = $pdo->prepare("UPDATE tokens SET status = 'cancelled'
                                WHERE token_id = ? AND doctor_id = ? AND status = 'waiting'");
        $stmt->execute([$token_id, $doctor_id]);
        if ($stmt->rowCount() === 1) {
            log_action($pdo, "Cancelled token id $token_id (doctor $doctor_id)");
            set_flash('success', 'Token cancelled.');
        } else {
            set_flash('error', 'Only waiting tokens can be cancelled.');
        }
    }
    redirect('receptionist/dashboard.php?doctor_id=' . $doctor_id);
}

$queue = $doctor_id > 0 ? get_queue($pdo, $doctor_id) : [];

if ($doctor_id > 0) {
    $extra_head = '<meta http-equiv="refresh" content="15">';
}

$page_title = 'Receptionist Dashboard';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Receptionist - Today's queues</h2>

<form method="get" style="display:flex; gap:8px; align-items:end;">
    <label>Doctor
        <select name="doctor_id" onchange="this.form.submit()">
            <option value="0">-- select a doctor --</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= e($d['doctor_id']) ?>" <?= $doctor_id == $d['doctor_id'] ? 'selected' : '' ?>>
                    <?= e($d['name']) ?> (Room <?= e($d['room_no']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Show</button>
</form>

<?php if ($doctor_id > 0): ?>
    <p><a class="btn" href="walkin.php?doctor_id=<?= e($doctor_id) ?>">+ Walk-in / emergency for this doctor</a></p>
    <table>
        <tr><th>Token</th><th>Patient</th><th>Phone</th><th>Priority</th><th>Type</th><th>Status</th><th>Actions</th></tr>
        <?php foreach ($queue as $t): ?>
            <tr>
                <td>#<?= e($t['token_no']) ?></td>
                <td><?= e($t['patient_name']) ?></td>
                <td><?= e($t['patient_phone']) ?></td>
                <td><?= e($t['priority']) ?></td>
                <td><?= e($t['booking_type']) ?></td>
                <td><?= e($t['status']) ?></td>
                <td>
                    <?php if ($t['status'] === 'waiting' || $t['status'] === 'in_consultation'): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="doctor_id" value="<?= e($doctor_id) ?>">
                            <input type="hidden" name="token_id" value="<?= e($t['token_id']) ?>">
                            <button name="action" value="no_show" style="margin:0;padding:4px 8px">No-show</button>
                            <?php if ($t['status'] === 'waiting'): ?>
                                <button name="action" value="cancel" style="margin:0;padding:4px 8px">Cancel</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$queue): ?><tr><td colspan="7">No tokens today for this doctor.</td></tr><?php endif; ?>
    </table>
    <p><small>This page refreshes every 15 seconds.</small></p>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
