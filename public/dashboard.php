<?php require_once __DIR__ . '/../lib/auth.php'; require_login(); $u = current_user(); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Dashboard - LeaveMS</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <div>Welcome, <?=htmlspecialchars($u['name'])?> (<?=htmlspecialchars($u['role'])?>)</div>
    <nav>
      <a href="apply_leave.php">Apply Leave</a>
      <a href="leave_history.php">My Leaves</a>
      <a href="balances.php">My Balance</a>
      <?php if ($u['role'] !== 'EMPLOYEE'): ?>
        <a href="admin/approvals.php">Approvals</a>
        <a href="admin/leave_types.php">Leave Types</a>
        <a href="admin/holidays.php">Holidays</a>
        <a href="admin/users.php">Users</a>
      <?php endif; ?>
      <a href="logout.php">Logout</a>
    </nav>
  </header>
  <main class="container">
    <h1>Dashboard</h1>
    <p>Use the navigation to manage leaves.</p>
  </main>
</body>
</html>
