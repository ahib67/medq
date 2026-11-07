<?php
require_once __DIR__ . '/../config/config.php';
session_start();

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
