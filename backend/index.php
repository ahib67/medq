<?php
require_once 'includes/functions.php';
if (isset($_SESSION['user_id'])) redirect('dashboard.php');

$page_title = 'Home';
include 'includes/header.php';
?>
<section class="hero">
    <h1>Skip the waiting room.</h1>
    <p>Book a token for your doctor, follow the queue live from your phone and arrive just in time for your turn.</p>
    <a class="btn" href="register.php">Create an account</a>
    <a class="btn light" href="login.php">Login</a>
</section>

<section class="steps">
    <div class="step"><span>1</span><h3>Book</h3><p>Choose a doctor and a day, and get your token number instantly.</p></div>
    <div class="step"><span>2</span><h3>Track</h3><p>See who is being served now and your estimated waiting time.</p></div>
    <div class="step"><span>3</span><h3>Arrive</h3><p>You get an alert when you are three tokens away. Wait at home until then.</p></div>
</section>
<?php include 'includes/footer.php'; ?>
