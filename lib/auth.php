<?php
// lib/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();
$app = require __DIR__ . '/../config/app.php';
require __DIR__ . '/../config/db.php';

function login($email, $password) {
  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status='ACTIVE' LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if ($user && hash_equals($user['password_hash'], hash('sha256', $password))) {
    $_SESSION['user'] = [
      'id' => $user['id'],
      'name' => $user['full_name'],
      'role' => $user['role'],
      'dept_id' => $user['dept_id']
    ];
    return true;
  }
  return false;
}

function require_login() {
  if (empty($_SESSION['user'])) {
    header('Location: index.php?err=login'); exit;
  }
}

function require_role($roles = []) {
  require_login();
  if (!in_array($_SESSION['user']['role'], $roles)) {
    http_response_code(403);
    exit('Forbidden');
  }
}

function current_user() { return $_SESSION['user'] ?? null; }

function logout() { session_destroy(); }
