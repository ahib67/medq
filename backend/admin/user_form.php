<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

$id     = (int)($_GET['id'] ?? 0);    // 0 = add new, otherwise edit
$errors = [];
$user   = ['name' => '', 'email' => '', 'phone' => '', 'role' => 'patient'];
$roles  = ['patient', 'doctor', 'receptionist', 'admin'];

// EDIT mode: load the existing user
if ($id) {
    $stmt = $pdo->prepare('SELECT name, email, phone, role FROM users WHERE user_id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        set_flash('error', 'User not found.');
        redirect('admin/users.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user['name']  = trim($_POST['name'] ?? '');
    $user['email'] = trim($_POST['email'] ?? '');
    $user['phone'] = trim($_POST['phone'] ?? '');
    $user['role']  = $_POST['role'] ?? '';
    $password      = $_POST['password'] ?? '';

    // Validation
    if ($user['name'] === '') $errors[] = 'Name is required.';
    if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (!in_array($user['role'], $roles)) $errors[] = 'Choose a valid role.';
    if (!$id && $password === '') $errors[] = 'Password is required for a new user.';
    if ($password !== '' && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    // Email must be unique (ignore the user we are editing)
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ?');
    $stmt->execute([$user['email'], $id]);
    if ($stmt->fetch()) $errors[] = 'This email is already used by another user.';

    if ($id) {
        // Admin must not remove his own admin role
        if ($id === (int)$_SESSION['user_id'] && $user['role'] !== 'admin') {
            $errors[] = 'You cannot change your own role.';
        }
        // A user with a doctor profile must stay a doctor
        $stmt = $pdo->prepare('SELECT doctor_id FROM doctors WHERE user_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetch() && $user['role'] !== 'doctor') {
            $errors[] = 'This user has a doctor profile. Delete it under Doctors first.';
        }
    }

    // Save
    if (empty($errors)) {
        if ($id) {
            if ($password !== '') {
                $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, phone=?, role=?, password_hash=? WHERE user_id=?');
                $stmt->execute([$user['name'], $user['email'], $user['phone'], $user['role'], password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, phone=?, role=? WHERE user_id=?');
                $stmt->execute([$user['name'], $user['email'], $user['phone'], $user['role'], $id]);
            }
            $user_id = $id;
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$user['name'], $user['email'], $user['phone'], password_hash($password, PASSWORD_DEFAULT), $user['role']]);
            $user_id = $pdo->lastInsertId();
        }

        // Every patient user needs a row in the patients table too
        if ($user['role'] === 'patient') {
            $stmt = $pdo->prepare('SELECT patient_id FROM patients WHERE user_id = ?');
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare('INSERT INTO patients (user_id, name, phone) VALUES (?, ?, ?)');
                $stmt->execute([$user_id, $user['name'], $user['phone']]);
            }
        }

        // If admin edited himself, refresh the name shown in the header
        if ($id === (int)$_SESSION['user_id']) $_SESSION['name'] = $user['name'];

        set_flash('success', 'User saved.');
        redirect('admin/users.php');
    }
}

$page_title = $id ? 'Edit user' : 'Add user';
include '../includes/header.php';
include 'menu.php';
?>
<div class="box">
    <h2><?= $id ? 'Edit' : 'Add' ?> user</h2>
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="post">
        <label>Name <input type="text" name="name" value="<?= e($user['name']) ?>" required></label>
        <label>Email <input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
        <label>Phone <input type="text" name="phone" value="<?= e($user['phone']) ?>"></label>
        <label>Role
            <select name="role">
                <?php foreach ($roles as $r): ?>
                    <option value="<?= e($r) ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Password <?= $id ? '(leave empty to keep the old one)' : '' ?>
            <input type="password" name="password" <?= $id ? '' : 'required' ?>>
        </label>
        <button type="submit">Save</button>
        <a href="users.php">Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
