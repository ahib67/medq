<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/patient.php';
require_role('patient');

// My alerts, newest first. The WHERE makes sure I only see MY alerts.
$stmt = $pdo->prepare('SELECT n.notif_id, n.type, n.status, n.sent_at, t.token_no, t.token_date,
                              du.name AS doctor_name, d.room_no
                         FROM notifications n
                         LEFT JOIN tokens t ON t.token_id = n.token_id
                         LEFT JOIN doctors d ON d.doctor_id = t.doctor_id
                         LEFT JOIN users du ON du.user_id = d.user_id
                        WHERE n.recipient_user_id = ?
                        ORDER BY n.notif_id DESC LIMIT 50');
$stmt->execute([$_SESSION['user_id']]);
$alerts = $stmt->fetchAll();

$extra_head = '<meta http-equiv="refresh" content="15">';
$page_title = 'My alerts';
include '../includes/header.php';
include 'menu.php';
?>
<h2>My alerts</h2>
<table>
    <tr><th>Time</th><th>Message</th><th>Delivery</th></tr>
    <?php foreach ($alerts as $a): ?>
        <tr>
            <td><?= e($a['sent_at'] ?: '-') ?></td>
            <td><?= e(notification_text($a['type'], $a['token_no'], $a['doctor_name'], $a['token_date'], $a['room_no'])) ?></td>
            <td><?= e($a['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$alerts): ?><tr><td colspan="3">No alerts yet.</td></tr><?php endif; ?>
</table>
<?php include '../includes/footer.php'; ?>
