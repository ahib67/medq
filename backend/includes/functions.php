<?php
require_once __DIR__ . '/../config/config.php';
session_start();

session_start();
date_default_timezone_set('Asia/Karachi');
// Safely print text on a page (prevents XSS)
function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// Go to another page and stop
function redirect($path) {
    header('Location: ' . BASE_URL . '/' . $path);
    exit;
}

// One-time message shown on the next page
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        echo '<p class="flash ' . e($f['type']) . '">' . e($f['message']) . '</p>';
        unset($_SESSION['flash']);
    }
}
// Gives the class "active" to the menu link of the page we are on.
// Example: active('users.php', 'user_form.php')
function active(...$files) {
    return in_array(basename($_SERVER['PHP_SELF']), $files) ? 'active' : '';
}

// A small coloured label for a status or priority (the colours come from style.css)
function badge($text) {
    return '<span class="badge ' . e($text) . '">' . e(str_replace('_', ' ', $text)) . '</span>';
}
