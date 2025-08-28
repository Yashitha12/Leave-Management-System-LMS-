<?php
// public/admin/_nav.php
if (!isset($u) && function_exists('current_user')) { $u = current_user(); }
$active = basename($_SERVER['PHP_SELF']); // e.g., approvals.php
?>
<style>
  .adminbar{display:flex;gap:12px;align-items:center;justify-content:space-between;background:#121a2b;padding:10px 16px;margin-bottom:12px}
  .adminbar a{color:#e9eef7;text-decoration:none;padding:8px 10px;border-radius:10px;border:1px solid #203053}
  .adminbar a:hover{background:#0f1626}
  .adminbar .active{background:#0f1626;border-color:#2a3550}
  .adminbar .left, .adminbar .right{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  .adminbar .brand{font-weight:600;margin-right:10px;opacity:.9}
</style>
<div class="adminbar">
  <div class="left">
    <span class="brand">⚙️ Admin</span>
    <a href="../dashboard.php" class="<?= $active==='dashboard.php'?'active':'' ?>">Dashboard</a>
    <a href="approvals.php" class="<?= $active==='approvals.php'?'active':'' ?>">Approvals</a>
    <a href="users.php" class="<?= $active==='users.php'?'active':'' ?>">Users</a>
    <a href="leave_types.php" class="<?= $active==='leave_types.php'?'active':'' ?>">Leave Types</a>
    <a href="holidays.php" class="<?= $active==='holidays.php'?'active':'' ?>">Holidays</a>
    <?php if (!empty($u) && $u['role']==='ADMIN'): ?>
      <a href="balances_init.php" class="<?= $active==='balances_init.php'?'active':'' ?>">Initialize Balances</a>
    <?php endif; ?>
  </div>
  <div class="right">
    <span style="color:#9db0c9">Signed in: <?= htmlspecialchars($u['name'] ?? '') ?> (<?= htmlspecialchars($u['role'] ?? '') ?>)</span>
    <a href="../logout.php">Logout</a>
  </div>
</div>
