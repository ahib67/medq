<?php
require_once 'includes/functions.php';

session_unset();
session_destroy();
session_start();                       // new empty session just to show a message
set_flash('success', 'You have been logged out.');
redirect('login.php');
