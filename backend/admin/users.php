<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];

    if ($delete_id === (int)$_SESSION['user_id']) {
        set_flash('error', 'You cannot delete your own account.');
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->execute([$delete_id]);
            set_flash('success', 'User deleted.');
        } catch (PDOException $e) {
            set_flash('error', 'Cannot delete: this user has related records (for example doctor tokens).');
        }
    }
    redirect('admin/users.php');
}

// READ
$users = $pdo->query('SELECT user_id, name, email, phone, role, created_at FROM users ORDER BY user_id')->fetchAll();

$page_title = 'Users';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Users</h2>
<a class="btn" href="user_form.php">+ Add user</a>

<table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Actions</th></tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['user_id']) ?></td>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['phone']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td>
                <a href="user_form.php?id=<?= e($u['user_id']) ?>">Edit</a>
                <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?');">
                        <input type="hidden" name="delete_id" value="<?= e($u['user_id']) ?>">
                        <button type="submit" style="margin:0;padding:4px 8px">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>
