<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

$id     = (int)($_GET['id'] ?? 0);    // 0 = add new, otherwise edit
$name   = '';
$errors = [];

// EDIT mode: load the existing row
if ($id) {
    $stmt = $pdo->prepare('SELECT dept_name FROM departments WHERE dept_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('error', 'Department not found.');
        redirect('admin/departments.php');
    }
    $name = $row['dept_name'];
}

// Form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['dept_name'] ?? '');
    if ($name === '') $errors[] = 'Department name is required.';

    if (empty($errors)) {
        try {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE departments SET dept_name = ? WHERE dept_id = ?');
                $stmt->execute([$name, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO departments (dept_name) VALUES (?)');
                $stmt->execute([$name]);
            }
            set_flash('success', 'Department saved.');
            redirect('admin/departments.php');
        } catch (PDOException $e) {
            $errors[] = 'A department with this name already exists.';   // dept_name is UNIQUE
        }
    }
}

$page_title = $id ? 'Edit department' : 'Add department';
include '../includes/header.php';
include 'menu.php';
?>
<div class="box">
    <h2><?= $id ? 'Edit' : 'Add' ?> department</h2>
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="post">
        <label>Department name <input type="text" name="dept_name" value="<?= e($name) ?>" required></label>
        <button type="submit">Save</button>
        <a href="departments.php">Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
