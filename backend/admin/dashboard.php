<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');                       // only admins can open this page

// Simple counts for the cards
$total_users       = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$total_patients    = $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();
$total_doctors     = $pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn();
$total_departments = $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();
$tokens_today      = $pdo->query('SELECT COUNT(*) FROM tokens WHERE token_date = CURDATE()')->fetchColumn();
$waiting_today     = $pdo->query("SELECT COUNT(*) FROM tokens WHERE token_date = CURDATE() AND status = 'waiting'")->fetchColumn();

// Last 5 registered users
$recent = $pdo->query('SELECT name, email, role, created_at FROM users ORDER BY user_id DESC LIMIT 5')->fetchAll();

$page_title = 'Admin Dashboard';
include '../includes/header.php';
?>
<h2>Admin Dashboard</h2>

<div class="cards">
    <div class="card"><b><?= e($total_users) ?></b>Users</div>
    <div class="card"><b><?= e($total_patients) ?></b>Patients</div>
    <div class="card"><b><?= e($total_doctors) ?></b>Doctors</div>
    <div class="card"><b><?= e($total_departments) ?></b>Departments</div>
    <div class="card"><b><?= e($tokens_today) ?></b>Tokens today</div>
    <div class="card"><b><?= e($waiting_today) ?></b>Waiting now</div>
</div>

<h3>Manage</h3>
<p>
    <a class="btn" href="users.php">Users</a>
    <a class="btn" href="departments.php">Departments</a>
    <a class="btn" href="doctors.php">Doctors</a>
    <a class="btn" href="schedules.php">Schedules</a>
    <a class="btn" href="analytics.php">Analytics</a>
    <a class="btn" href="audit_log.php">Audit log</a>
</p>

<h3>Recently registered</h3>
<table>
    <tr><th>Name</th><th>Email</th><th>Role</th><th>Registered</th></tr>
    <?php foreach ($recent as $u): ?>
        <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td><?= e($u['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>
