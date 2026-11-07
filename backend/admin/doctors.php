<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM doctors WHERE doctor_id = ?');
        $stmt->execute([$_POST['delete_id']]);
        log_action($pdo, 'Deleted doctor id ' . (int)$_POST['delete_id']);
        set_flash('success', 'Doctor profile deleted.');
    } catch (PDOException $e) {
        set_flash('error', 'Cannot delete: this doctor already has tokens.');
    }
    redirect('admin/doctors.php');
}

// READ: JOIN gives us the doctor name (users) and department name (departments)
$doctors = $pdo->query(
    'SELECT d.doctor_id, u.name, u.email, dep.dept_name, d.specialization, d.room_no, d.avg_consult_mins
       FROM doctors d
       JOIN users u ON u.user_id = d.user_id
       JOIN departments dep ON dep.dept_id = d.dept_id
      ORDER BY u.name'
)->fetchAll();

$page_title = 'Doctors';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Doctors</h2>
<a class="btn" href="doctor_form.php">+ Add doctor</a>

<table>
    <tr><th>Name</th><th>Department</th><th>Specialization</th><th>Room</th><th>Avg mins</th><th>Actions</th></tr>
    <?php foreach ($doctors as $d): ?>
        <tr>
            <td><?= e($d['name']) ?></td>
            <td><?= e($d['dept_name']) ?></td>
            <td><?= e($d['specialization']) ?></td>
            <td><?= e($d['room_no']) ?></td>
            <td><?= e($d['avg_consult_mins']) ?></td>
            <td>
                <a href="doctor_form.php?id=<?= e($d['doctor_id']) ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this doctor?');">
                    <input type="hidden" name="delete_id" value="<?= e($d['doctor_id']) ?>">
                    <button type="submit" style="margin:0;padding:4px 8px">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>
