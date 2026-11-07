<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/patient.php';
require_once '../includes/queue.php';
require_role('receptionist');

$errors = [];
$f = [
    'doctor_id' => (int)($_GET['doctor_id'] ?? 0),
    'name' => '', 'phone' => '', 'gender' => '', 'date_of_birth' => '', 'priority' => 'normal',
];

$doctors = $pdo->query('SELECT d.doctor_id, u.name FROM doctors d JOIN users u ON u.user_id = d.user_id ORDER BY u.name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['doctor_id']     = (int)($_POST['doctor_id'] ?? 0);
    $f['name']          = trim($_POST['name'] ?? '');
    $f['phone']         = trim($_POST['phone'] ?? '');
    $f['gender']        = $_POST['gender'] ?? '';
    $f['date_of_birth'] = $_POST['date_of_birth'] ?? '';
    $f['priority']      = $_POST['priority'] ?? 'normal';

    $emergency = ($f['priority'] === 'emergency');

    // In an emergency we may not know the name yet
    if ($f['name'] === '' && $emergency) $f['name'] = 'Unknown (emergency)';

    // Validation
    if ($f['doctor_id'] < 1) $errors[] = 'Choose a doctor.';
    if ($f['name'] === '') $errors[] = 'Patient name is required.';
    if ($f['phone'] === '' && !$emergency) $errors[] = 'Phone is required.';
    if (!in_array($f['priority'], ['normal', 'senior', 'emergency'])) $errors[] = 'Choose a valid priority.';

    if (empty($errors)) {
        // Returning patient? Reuse the record with the same phone number.
        $patient_id = null;
        if ($f['phone'] !== '') {
            $stmt = $pdo->prepare('SELECT patient_id FROM patients WHERE phone = ? LIMIT 1');
            $stmt->execute([$f['phone']]);
            $patient_id = $stmt->fetchColumn();
        }
        // New walk-in patient: no user account, so user_id is NULL
        if (!$patient_id) {
            $stmt = $pdo->prepare('INSERT INTO patients (user_id, name, phone, date_of_birth, gender) VALUES (NULL, ?, ?, ?, ?)');
            $stmt->execute([$f['name'], $f['phone'] ?: null, $f['date_of_birth'] ?: null, $f['gender'] ?: null]);
            $patient_id = $pdo->lastInsertId();
        }

        // Issue the token for TODAY as a walk-in
        list($token_id, $error) = issue_token($pdo, $f['doctor_id'], $patient_id, date('Y-m-d'), 'walk_in', $f['priority']);

        if ($token_id) {
            $stmt = $pdo->prepare('SELECT token_no FROM tokens WHERE token_id = ?');
            $stmt->execute([$token_id]);
            $no = $stmt->fetchColumn();

            log_action($pdo, "Issued " . $f['priority'] . " walk-in token #$no for doctor " . $f['doctor_id']);
            set_flash('success', "Token #$no issued to " . $f['name'] . ' (' . $f['priority'] . ').');
            redirect('receptionist/dashboard.php?doctor_id=' . $f['doctor_id']);
        } else {
            $errors[] = $error;
        }
    }
}

$page_title = 'Walk-in / emergency';
include '../includes/header.php';
include 'menu.php';
?>
<div class="box">
    <h2>Walk-in / emergency patient</h2>
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <form method="post">
        <label>Doctor
            <select name="doctor_id">
                <option value="">-- select --</option>
                <?php foreach ($doctors as $d): ?>
                    <option value="<?= e($d['doctor_id']) ?>" <?= $f['doctor_id'] == $d['doctor_id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Priority
            <select name="priority">
                <option value="normal"    <?= $f['priority'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                <option value="senior"    <?= $f['priority'] === 'senior' ? 'selected' : '' ?>>Senior citizen</option>
                <option value="emergency" <?= $f['priority'] === 'emergency' ? 'selected' : '' ?>>EMERGENCY (goes first)</option>
            </select>
        </label>
        <label>Patient name <input type="text" name="name" value="<?= e($f['name']) ?>"></label>
        <label>Phone <input type="text" name="phone" value="<?= e($f['phone']) ?>"></label>
        <label>Gender
            <select name="gender">
                <option value="">-- select --</option>
                <option value="male"   <?= $f['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $f['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other"  <?= $f['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
        </label>
        <label>Date of birth <input type="date" name="date_of_birth" value="<?= e($f['date_of_birth']) ?>"></label>
        <button type="submit">Issue token</button>
        <a href="dashboard.php">Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
