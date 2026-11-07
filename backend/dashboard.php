<?php
require_once 'includes/functions.php';
if (!isset($_SESSION['user_id'])) redirect('login.php');

$page_title = 'Dashboard';
include 'includes/header.php';
?>
<h2>Welcome, <?= e($_SESSION['name']) ?></h2>
<p>You are logged in as <b><?= e($_SESSION['role']) ?></b>.</p>
<?php include 'includes/footer.php'; ?>
