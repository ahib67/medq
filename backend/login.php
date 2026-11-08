<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) redirect('dashboard.php');   // already logged in

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT user_id, name, role, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // password_verify compares the typed password with the stored hash
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);               // new session id after login (security)
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];
        redirect('dashboard.php');
    } else {
        $error = 'Invalid email or password.';     // same message for both cases on purpose
    }
}

$page_title = 'Login';
include 'includes/header.php';
?>
<div class="box">
    <h2>Login</h2>
    <?php if ($error): ?><p class="flash error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <label>Email <input type="email" name="email" required></label>
        <label>Password <input type="password" name="password" required></label>
        <button type="submit">Login</button>
    </form>
    <p>New patient? <a href="register.php">Register here</a></p>
</div>
<?php include 'includes/footer.php'; ?>
