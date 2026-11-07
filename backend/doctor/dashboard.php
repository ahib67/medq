<?php
require_once '../includes/auth.php';
require_role('doctor');

$page_title = 'Doctor Dashboard';
include '../includes/header.php';
?>
<h2>Doctor Dashboard</h2>
<p>Here you will see today's queue and call the next patient.</p>
<?php include '../includes/footer.php'; ?>
