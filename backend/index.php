<?php
require_once 'includes/functions.php';
if (isset($_SESSION['user_id'])) redirect('dashboard.php');

$page_title = 'Home';
include 'includes/header.php';
?>
<h1>MedQ - Clinic Queue &amp; Live OPD Token System</h1>
<p>Book a token online, track the queue live and skip the waiting room.</p>
<a class="btn" href="login.php">Login</a>
<a class="btn" href="register.php">Register</a>
<?php include 'includes/footer.php'; ?>
