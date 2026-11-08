<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('patient');

$q       = trim($_GET['q'] ?? '');
$dept_id = (int)($_GET['dept_id'] ?? 0);

// Build the WHERE part step by step
$where  = [];
$params = [];
if ($q !== '') {
    $where[]  = 'u.name LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($dept_id > 0) {
    $where[]  = 'd.dept_id = ?';
    $params[] = $dept_id;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// The inner SELECT joins all schedule rows of a doctor into one text like "Mon 09:00-13:00, Wed 09:00-13:00"
$sql = "SELECT d.doctor_id, u.name, dep.dept_name, d.specialization, d.room_no,
               (SELECT GROUP_CONCAT(CONCAT(s.day_of_week, ' ', TIME_FORMAT(s.start_time, '%H:%i'), '-', TIME_FORMAT(s.end_time, '%H:%i'))
                       ORDER BY FIELD(s.day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun') SEPARATOR ', ')
                  FROM doctor_schedules s WHERE s.doctor_id = d.doctor_id) AS schedule_text
          FROM doctors d
          JOIN users u ON u.user_id = d.user_id
          JOIN departments dep ON dep.dept_id = d.dept_id
          $where_sql
         ORDER BY u.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

$departments = $pdo->query('SELECT dept_id, dept_name FROM departments ORDER BY dept_name')->fetchAll();

$page_title = 'Find doctors';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Find a doctor</h2>

<form method="get" style="display:flex; gap:8px; align-items:end;">
    <label>Doctor name <input type="text" name="q" value="<?= e($q) ?>"></label>
    <label>Department
        <select name="dept_id">
            <option value="0">All departments</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= e($d['dept_id']) ?>" <?= $dept_id == $d['dept_id'] ? 'selected' : '' ?>><?= e($d['dept_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Search</button>
</form>

<table>
    <tr><th>Doctor</th><th>Department</th><th>Room</th><th>Schedule</th><th></th></tr>
    <?php foreach ($doctors as $d): ?>
        <tr>
            <td><?= e($d['name']) ?><br><small><?= e($d['specialization']) ?></small></td>
            <td><?= e($d['dept_name']) ?></td>
            <td><?= e($d['room_no']) ?></td>
            <td><?= e($d['schedule_text'] ?: 'No schedule yet') ?></td>
            <td><a class="btn" style="margin:0" href="book.php?doctor_id=<?= e($d['doctor_id']) ?>">Book</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$doctors): ?><tr><td colspan="5">No doctors found.</td></tr><?php endif; ?>
</table>
<?php include '../includes/footer.php'; ?>
