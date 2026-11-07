<?php
require_once __DIR__ . '/functions.php';

// Page needs a logged-in user
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        set_flash('error', 'Please log in first.');
        redirect('login.php');
    }
}

// Page needs a logged-in user with one of the allowed roles
// Example: require_role('admin')  or  require_role(['doctor', 'receptionist'])
function require_role($allowed_roles) {
    require_login();
    if (!in_array($_SESSION['role'], (array)$allowed_roles)) {
        http_response_code(403);
        $page_title = 'Access denied';
        include __DIR__ . '/header.php';
        echo '<h2>403 - Access denied</h2>';
        echo '<p>You do not have permission to open this page.</p>';
        include __DIR__ . '/footer.php';
        exit;
    }
}
