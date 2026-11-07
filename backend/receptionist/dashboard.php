<?php
require_once '../includes/auth.php';
require_role('receptionist');

$page_title = 'Receptionist Dashboard';
include '../includes/header.php';
?>
<h2>Receptionist Dashboard</h2>
<p>Here you will register walk-ins and manage no-shows.</p>
<?php include '../includes/footer.php'; ?>
