<?php
require_once '../includes/auth.php';
require_role('patient');

$page_title = 'Patient Dashboard';
include '../includes/header.php';
?>
<h2>Patient Dashboard</h2>
<p>Here you will search doctors, book tokens and track your queue.</p>
<?php include '../includes/footer.php'; ?>
