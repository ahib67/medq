<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

$id     = (int)($_GET['id'] ?? 0);    // 0 = add new, otherwise edit
$errors = [];
$days   = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$s      = ['doctor_id' => '', 'day_of_week' => 'Mon', 'start_time' => '09:00', 'end_time' => '13:00', 'max_tokens' => 30];

// EDIT mode
if ($id) {
    $stmt = $pdo->prepare('SELECT doctor_id, day_of_week, start_time, end_time, max_tokens FROM doctor_schedules WHERE schedule_id = ?');
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if (!$s) {
        set_flash('error', 'Schedule not found.');
        redirect('admin/schedules.php');
    }
    $s['start_time'] = substr($s['start_time'], 0, 5);   // 09:00:00 -> 09:00
    $s['end_time']   = substr($s['end_time'], 0, 5);
}

$doctors = $pdo->query('SELECT d.doctor_id, u.name FROM doctors d JOIN users u ON u.user_id = d.user_id ORDER BY u.name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s['doctor_id']   = (int)($_POST['doctor_id'] ?? 0);
    $s['day_of_week'] = $_POST['day_of_week'] ?? '';
    $s['start_time']  = $_POST['start_time'] ?? '';
    $s['end_time']    = $_POST['end_time'] ?? '';
    $s['max_tokens']  = (int)($_POST['max_tokens'] ?? 0);

    // Validation
    if ($s['doctor_id'] < 1) $errors[] = 'Choose a doctor.';
    if (!in_array($s['day_of_week'], $days)) $errors[] = 'Choose a valid day.';
    if ($s['start_time'] === '' || $s['end_time'] === '') $errors[] = 'Start and end time are required.';
    elseif ($s['end_time'] <= $s['start_time']) $errors[] = 'End time must be after start time.';
    if ($s['max_tokens'] < 1) $errors[] = 'Max tokens must be at least 1.';

    if (empty($errors)) {
        try {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE doctor_schedules SET doctor_id=?, day_of_week=?, start_time=?, end_time=?, max_tokens=? WHERE schedule_id=?');
                $stmt->execute([$s['doctor_id'], $s['day_of_week'], $s['start_time'], $s['end_time'], $s['max_tokens'], $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, max_tokens) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$s['doctor_id'], $s['day_of_week'], $s['start_time'], $s['end_time'], $s['max_tokens']]);
            }
            log_action($pdo, ($id ? 'Updated' : 'Created') . ' schedule: doctor id ' . $s['doctor_id'] . ', ' . $s['day_of_week'] . ' ' . $s['start_time'] . '-' . $s['end_time']);
            set_flash('success', 'Schedule saved.');
            redirect('admin/schedules.php');
        } catch (PDOException $e) {
            if ($e->errorInfo[0] === '45000') {
                $errors[] = $e->errorInfo[2];      // message from our overlap trigger
            } else {
                $errors[] = 'Could not save. This schedule may already exist.';
            }
        }
    }
}

$page_title = $id ? 'Edit schedule' : 'Add schedule';
include '../includes/header.php';
include 'menu.php';
?>
<div class="box">
    <h2><?= $id ? 'Edit' : 'Add' ?> schedule</h2>
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="post">
        <label>Doctor
            <select name="doctor_id">
                <option value="">-- select --</option>
                <?php foreach ($doctors as $d): ?>
                    <option value="<?= e($d['doctor_id']) ?>" <?= $s['doctor_id'] == $d['doctor_id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Day
            <select name="day_of_week">
                <?php foreach ($days as $day): ?>
                    <option value="<?= e($day) ?>" <?= $s['day_of_week'] === $day ? 'selected' : '' ?>><?= e($day) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Start time <input type="time" name="start_time" value="<?= e($s['start_time']) ?>" required></label>
        <label>End time <input type="time" name="end_time" value="<?= e($s['end_time']) ?>" required></label>
        <label>Max tokens per day <input type="number" name="max_tokens" value="<?= e($s['max_tokens']) ?>" min="1" required></label>
        <button type="submit">Save</button>
        <a href="schedules.php">Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
