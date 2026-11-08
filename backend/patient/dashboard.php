<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/patient.php';
require_role('patient');

$patient_id = current_patient_id($pdo);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE patient_id = ? AND status IN ('waiting','in_consultation')");
$stmt->execute([$patient_id]);
$active = $stmt->fetchColumn();

$page_title = 'Patient Dashboard';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Patient Dashboard</h2>
<div class="cards">
    <div class="card"><b><?= e($active) ?></b>Active tokens</div>
</div>
<a class="btn" href="doctors.php">Find a doctor and book</a>
<a class="btn" href="my_tokens.php">My tokens</a>
<?php include '../includes/footer.php'; ?>
