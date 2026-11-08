<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/patient.php';
require_role('patient');

$patient_id = current_patient_id($pdo);
$errors = [];

// Load the current values (email is shown but cannot be changed here)
$stmt = $pdo->prepare('SELECT u.email, p.name, p.phone, p.date_of_birth, p.gender
                         FROM patients p JOIN users u ON u.user_id = p.user_id
                        WHERE p.patient_id = ?');
$stmt->execute([$patient_id]);
$me = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me['name']          = trim($_POST['name'] ?? '');
    $me['phone']         = trim($_POST['phone'] ?? '');
    $me['date_of_birth'] = $_POST['date_of_birth'] ?? '';
    $me['gender']        = $_POST['gender'] ?? '';

    if ($me['name'] === '') $errors[] = 'Name is required.';
    if ($me['phone'] === '') $errors[] = 'Phone is required.';
    if ($me['gender'] !== '' && !in_array($me['gender'], ['male', 'female', 'other'])) $errors[] = 'Choose a valid gender.';

    if (empty($errors)) {
        // The name and phone live in both tables, so we update both
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ? WHERE user_id = ?');
        $stmt->execute([$me['name'], $me['phone'], $_SESSION['user_id']]);

        $stmt = $pdo->prepare('UPDATE patients SET name = ?, phone = ?, date_of_birth = ?, gender = ? WHERE patient_id = ?');
        $stmt->execute([$me['name'], $me['phone'], $me['date_of_birth'] ?: null, $me['gender'] ?: null, $patient_id]);
        $pdo->commit();

        $_SESSION['name'] = $me['name'];   // refresh the name shown in the header
        set_flash('success', 'Profile updated.');
        redirect('patient/profile.php');
    }
}

$page_title = 'My profile';
include '../includes/header.php';
include 'menu.php';
?>
<div class="box">
    <h2>My profile</h2>
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <form method="post">
        <label>Email <input type="text" value="<?= e($me['email']) ?>" disabled></label>
        <label>Full name <input type="text" name="name" value="<?= e($me['name']) ?>" required></label>
        <label>Phone <input type="text" name="phone" value="<?= e($me['phone']) ?>" required></label>
        <label>Date of birth <input type="date" name="date_of_birth" value="<?= e($me['date_of_birth']) ?>"></label>
        <label>Gender
            <select name="gender">
                <option value="">-- select --</option>
                <option value="male"   <?= $me['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $me['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other"  <?= $me['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
        </label>
        <button type="submit">Save</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
