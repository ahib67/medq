<?php
require_once 'includes/auth.php';
require_login();

// Folder names match the role names: admin/, doctor/, receptionist/, patient/
redirect($_SESSION['role'] . '/dashboard.php');
