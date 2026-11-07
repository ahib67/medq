<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

$id     = (int)($_GET['id'] ?? 0);    // 0 = add new, otherwise edit
$errors = [];
$doc    = ['user_id' => '', 'dept_id' => '', 'specialization' => '', 'room_no' => '', 'avg_consult_mins' => 10];
$doctor_name = '';

// EDIT mode: load existing doctor
if ($id) {
    $stmt = $pdo->prepare('SELECT d.user_id, d.dept_id, d.specialization, d.room_no, d.avg_consult_mins, u.name
                             FROM doctors d JOIN users u ON u.user_id = d.user_id WHERE d.doctor_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('error', 'Doctor not found.');
        redirect('admin/doctors.php');
    }
    $doctor_name = $row['name'];
    $doc = $row;
}

// Dropdown data
$departments = $pdo->query('SELECT dept_id, dept_name FROM departments ORDER BY dept_name')->fetchAll();
// Users with role doctor who do not have a doctor profile yet
$available_users = $pdo->query(
    "SELECT user_id, name, email FROM users
      WHERE role = 'doctor' AND user_id NOT IN (SELECT user_id FROM doctors)
      ORDER BY name"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc['dept_id']          = (int)($_POST['dept_id'] ?? 0);
    $doc['specialization']   = trim($_POST['specialization'] ?? '');
    $doc['room_no']          = trim($_POST['room_no'] ?? '');
    $doc['avg_consult_mins'] = (int)($_POST['avg_consult_mins'] ?? 0);
    if (!$id) $doc['user_id'] = (int)($_POST['user_id'] ?? 0);

    // Validation
    if (!$id && !in_array($doc['user_id'], array_column($available_users, 'user_id'))) $errors[] = 'Choose a doctor account.';
    if ($doc['dept_id'] < 1) $errors[] = 'Choose a department.';
    if ($doc['avg_consult_mins'] < 1 || $doc['avg_consult_mins'] > 120) $errors[] = 'Average consultation time must be 1-120 minutes.';

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE doctors SET dept_id=?, specialization=?, room_no=?, avg_consult_mins=? WHERE doctor_id=?');
            $stmt->execute([$doc['dept_id'], $doc['specialization'], $doc['room_no'], $doc['avg_consult_mins'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO doctors (user_id, dept_id, specialization, room_no, avg_consult_mins) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$doc['user_id'], $doc['dept_id'], $doc['specialization'], $doc['room_no'], $doc['avg_consult_mins']]);
        }
        set_flash('success', 'Doctor saved.');
        redirect('admin/doctors.php');
    }
}

$page_title = $id ? 'Edit doctor' : 'Add doctor';
include '../includes/header.php';
include 'menu.php';
?>
<div class="box">
    <h2><?= $id ? 'Edit' : 'Add' ?> doctor</h2>
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <?php if (!$id && empty($available_users)): ?>
        <p>No free doctor accounts. First <a href="user_form.php">create a user with role "doctor"</a>.</p>
    <?php else: ?>
    <form method="post">
        <?php if ($id): ?>
            <p>Doctor: <b><?= e($doctor_name) ?></b></p>
        <?php else: ?>
            <label>Doctor account
                <select name="user_id">
                    <option value="">-- select --</option>
                    <?php foreach ($available_users as $u): ?>
                        <option value="<?= e($u['user_id']) ?>" <?= $doc['user_id'] == $u['user_id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?> (<?= e($u['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <label>Department
            <select name="dept_id">
                <option value="">-- select --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= e($d['dept_id']) ?>" <?= $doc['dept_id'] == $d['dept_id'] ? 'selected' : '' ?>><?= e($d['dept_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Specialization <input type="text" name="specialization" value="<?= e($doc['specialization']) ?>"></label>
        <label>Room number <input type="text" name="room_no" value="<?= e($doc['room_no']) ?>"></label>
        <label>Average consultation (minutes) <input type="number" name="avg_consult_mins" value="<?= e($doc['avg_consult_mins']) ?>" min="1" max="120"></label>
        <button type="submit">Save</button>
        <a href="doctors.php">Cancel</a>
    </form>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
