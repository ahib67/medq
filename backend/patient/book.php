<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/patient.php';
require_role('patient');

$doctor_id = (int)($_GET['doctor_id'] ?? 0);

// Load the doctor
$stmt = $pdo->prepare('SELECT d.doctor_id, u.name, dep.dept_name, d.specialization, d.room_no
                         FROM doctors d
                         JOIN users u ON u.user_id = d.user_id
                         JOIN departments dep ON dep.dept_id = d.dept_id
                        WHERE d.doctor_id = ?');
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();
if (!$doctor) {
    set_flash('error', 'Doctor not found.');
    redirect('patient/doctors.php');
}

// Load the doctor's schedule to show it
$stmt = $pdo->prepare("SELECT day_of_week, start_time, end_time, max_tokens FROM doctor_schedules
                        WHERE doctor_id = ? ORDER BY FIELD(day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun')");
$stmt->execute([$doctor_id]);
$schedules = $stmt->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    list($token_id, $error) = issue_token($pdo, $doctor_id, current_patient_id($pdo), $date);

    if ($token_id) {
        set_flash('success', 'Token booked successfully.');
        redirect('patient/token_status.php?id=' . $token_id);
    }
}

$page_title = 'Book token';
include '../includes/header.php';
include 'menu.php';
?>
<div class="box">
    <h2>Book a token</h2>
    <p><b><?= e($doctor['name']) ?></b><br>
       <?= e($doctor['dept_name']) ?> - Room <?= e($doctor['room_no']) ?></p>

    <p>Working days:</p>
    <ul>
        <?php foreach ($schedules as $s): ?>
            <li><?= e($s['day_of_week']) ?> <?= e(substr($s['start_time'], 0, 5)) ?>-<?= e(substr($s['end_time'], 0, 5)) ?>
                (max <?= e($s['max_tokens']) ?> tokens)</li>
        <?php endforeach; ?>
    </ul>

    <?php if ($error): ?><p class="flash error"><?= e($error) ?></p><?php endif; ?>

    <form method="post">
        <label>Choose a date
            <input type="date" name="date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
        </label>
        <button type="submit">Book token</button>
        <a href="doctors.php">Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
