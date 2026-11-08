<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$errors = [];
$name = $email = $phone = $dob = $gender = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Read the form values
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $dob      = $_POST['date_of_birth'] ?? '';
    $gender   = $_POST['gender'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // 2. Validate
    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($phone === '') $errors[] = 'Phone is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    // 3. Is the email already used?
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'This email is already registered.';
    }

    // 4. Save: one row in users and one row in patients
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'patient')");
        $stmt->execute([$name, $email, $phone, $hash]);
        $user_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO patients (user_id, name, phone, date_of_birth, gender) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $name, $phone, $dob ?: null, $gender ?: null]);
        $pdo->commit();

        set_flash('success', 'Registration successful. Please log in.');
        redirect('login.php');
    }
}

$page_title = 'Register';
include 'includes/header.php';
?>
<div class="box">
    <h2>Patient Registration</h2>
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <form method="post">
        <label>Full name <input type="text" name="name" value="<?= e($name) ?>" required></label>
        <label>Email <input type="email" name="email" value="<?= e($email) ?>" required></label>
        <label>Phone <input type="text" name="phone" value="<?= e($phone) ?>" required></label>
        <label>Date of birth <input type="date" name="date_of_birth" value="<?= e($dob) ?>"></label>
        <label>Gender
            <select name="gender">
                <option value="">-- select --</option>
                <option value="male"   <?= $gender === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other"  <?= $gender === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
        </label>
        <label>Password <input type="password" name="password" required></label>
        <label>Confirm password <input type="password" name="confirm" required></label>
        <button type="submit">Register</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
