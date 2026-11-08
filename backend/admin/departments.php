<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

// DELETE: the Delete button on this page posts here
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM departments WHERE dept_id = ?');
        $stmt->execute([$_POST['delete_id']]);
        log_action($pdo, 'Deleted department id ' . (int)$_POST['delete_id']);
        set_flash('success', 'Department deleted.');
    } catch (PDOException $e) {
        // The database refuses because doctors still use this department (foreign key)
        set_flash('error', 'Cannot delete: doctors still belong to this department.');
    }
    redirect('admin/departments.php');
}

// READ: all departments with how many doctors each has
$departments = $pdo->query(
    'SELECT d.dept_id, d.dept_name, COUNT(x.doctor_id) AS doctor_count
       FROM departments d
       LEFT JOIN doctors x ON x.dept_id = d.dept_id
      GROUP BY d.dept_id, d.dept_name
      ORDER BY d.dept_name'
)->fetchAll();

$page_title = 'Departments';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Departments</h2>
<a class="btn" href="department_form.php">+ Add department</a>

<table>
    <tr><th>ID</th><th>Name</th><th>Doctors</th><th>Actions</th></tr>
    <?php foreach ($departments as $d): ?>
        <tr>
            <td><?= e($d['dept_id']) ?></td>
            <td><?= e($d['dept_name']) ?></td>
            <td><?= e($d['doctor_count']) ?></td>
            <td>
                <a href="department_form.php?id=<?= e($d['dept_id']) ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this department?');">
                    <input type="hidden" name="delete_id" value="<?= e($d['dept_id']) ?>">
                    <button type="submit" class="btn-danger" style="margin:0;padding:4px 8px">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>
