<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/patient.php';
require_role('patient');

$patient_id = current_patient_id($pdo);

// CANCEL (only my own token, and only while it is still waiting)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $stmt = $pdo->prepare("UPDATE tokens SET status = 'cancelled'
                            WHERE token_id = ? AND patient_id = ? AND status = 'waiting'");
    $stmt->execute([$_POST['cancel_id'], $patient_id]);

        if ($stmt->rowCount() === 1) {
        add_notification($pdo, (int)$_POST['cancel_id'], 'cancelled');   // NEW alert (FR-6)
        set_flash('success', 'Token cancelled.');
    } else {
        set_flash('error', 'This token cannot be cancelled.');
    }
    redirect('patient/my_tokens.php');
}

// READ: all my tokens, newest first
$stmt = $pdo->prepare('SELECT t.token_id, t.token_date, t.token_no, t.status, u.name AS doctor_name, d.room_no
                         FROM tokens t
                         JOIN doctors d ON d.doctor_id = t.doctor_id
                         JOIN users u ON u.user_id = d.user_id
                        WHERE t.patient_id = ?
                        ORDER BY t.token_date DESC, t.token_no DESC');
$stmt->execute([$patient_id]);
$tokens = $stmt->fetchAll();

$page_title = 'My tokens';
include '../includes/header.php';
include 'menu.php';
?>
<h2>My tokens</h2>
<table>
    <tr><th>Date</th><th>Doctor</th><th>Room</th><th>Token</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($tokens as $t): ?>
        <tr>
            <td><?= e($t['token_date']) ?></td>
            <td><?= e($t['doctor_name']) ?></td>
            <td><?= e($t['room_no']) ?></td>
            <td>#<?= e($t['token_no']) ?></td>
            <td><?= e($t['status']) ?></td>
            <td>
                <?php if ($t['status'] === 'waiting' || $t['status'] === 'in_consultation'): ?>
                    <a href="token_status.php?id=<?= e($t['token_id']) ?>">Live status</a>
                <?php endif; ?>
                <?php if ($t['status'] === 'waiting'): ?>
                    | <a href="reschedule.php?id=<?= e($t['token_id']) ?>">Reschedule</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Cancel this token?');">
                        <input type="hidden" name="cancel_id" value="<?= e($t['token_id']) ?>">
                        <button type="submit" style="margin:0;padding:4px 8px">Cancel</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$tokens): ?><tr><td colspan="6">You have no tokens yet.</td></tr><?php endif; ?>
</table>
<?php include '../includes/footer.php'; ?>
