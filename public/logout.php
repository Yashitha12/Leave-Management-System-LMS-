<?php
// public/logout.php
require_once __DIR__ . '/../lib/auth.php';

// Destroy the session and redirect to login
logout();

// Redirect to the login page in /public/
header('Location: index.php');
exit;
