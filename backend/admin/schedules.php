<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare('DELETE FROM doctor_schedules WHERE schedule_id = ?');
    $stmt->execute([$_POST['delete_id']]);
    log_action($pdo, 'Deleted schedule id ' . (int)$_POST['delete_id']);
    set_flash('success', 'Schedule deleted.');
    redirect('admin/schedules.php');
}

// READ
$schedules = $pdo->query(
    "SELECT s.schedule_id, u.name AS doctor_name, s.day_of_week, s.start_time, s.end_time, s.max_tokens
       FROM doctor_schedules s
       JOIN doctors d ON d.doctor_id = s.doctor_id
       JOIN users u ON u.user_id = d.user_id
      ORDER BY u.name, FIELD(s.day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun'), s.start_time"
)->fetchAll();

$page_title = 'Schedules';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Doctor Schedules</h2>
<a class="btn" href="schedule_form.php">+ Add schedule</a>

<table>
    <tr><th>Doctor</th><th>Day</th><th>From</th><th>To</th><th>Max tokens</th><th>Actions</th></tr>
    <?php foreach ($schedules as $s): ?>
        <tr>
            <td><?= e($s['doctor_name']) ?></td>
            <td><?= e($s['day_of_week']) ?></td>
            <td><?= e(substr($s['start_time'], 0, 5)) ?></td>
            <td><?= e(substr($s['end_time'], 0, 5)) ?></td>
            <td><?= e($s['max_tokens']) ?></td>
            <td>
                <a href="schedule_form.php?id=<?= e($s['schedule_id']) ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this schedule?');">
                    <input type="hidden" name="delete_id" value="<?= e($s['schedule_id']) ?>">
                    <button type="submit" class="btn-danger" style="margin:0;padding:4px 8px">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>
