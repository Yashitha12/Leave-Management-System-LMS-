<?php
// public/balances.php
require_once __DIR__ . '/../lib/auth.php'; require_login();
require_once __DIR__ . '/../config/db.php';

$u = current_user();
$year = (int)($_GET['year'] ?? date('Y'));

// if ADMIN → allow choosing another user
$user_id = $u['id'];
if ($u['role'] === 'ADMIN' && isset($_GET['user_id'])) {
  $user_id = (int)$_GET['user_id'];
}

// Load balances
$sql = "SELECT lb.*, lt.name AS type_name, lt.code
        FROM leave_balances lb
        JOIN leave_types lt ON lt.id = lb.type_id
        WHERE lb.user_id=? AND lb.year=?
        ORDER BY lt.name";
$st = $pdo->prepare($sql); 
$st->execute([$user_id, $year]);
$rows = $st->fetchAll();

// If no rows, show message
$no_balance = !$rows;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Leave Balances</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .balances h2{margin-bottom:10px}
    .balances table td, .balances table th{text-align:center}
  </style>
</head>
<body>
<a href="dashboard.php">← Back</a>
<div class="balances container">
  <h2>Leave Balances (<?= htmlspecialchars($year) ?>)</h2>

  <?php if ($u['role']==='ADMIN'): ?>
    <form method="get" style="margin-bottom:12px;">
      <label>User ID:</label>
      <input type="number" name="user_id" value="<?= htmlspecialchars($user_id) ?>" required>
      <label>Year:</label>
      <input type="number" name="year" value="<?= htmlspecialchars($year) ?>" required>
      <button type="submit">View</button>
    </form>
  <?php else: ?>
    <form method="get" style="margin-bottom:12px;">
      <label>Year:</label>
      <input type="number" name="year" value="<?= htmlspecialchars($year) ?>" required>
      <button type="submit">View</button>
    </form>
  <?php endif; ?>

  <?php if ($no_balance): ?>
    <div class="alert">No leave balances found for this year.</div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Leave Type</th>
          <th>Allocated</th>
          <th>Carried Over</th>
          <th>Used</th>
          <th>Remaining</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['type_name']) ?> (<?= htmlspecialchars($r['code']) ?>)</td>
            <td><?= htmlspecialchars($r['allocated']) ?></td>
            <td><?= htmlspecialchars($r['carried_over']) ?></td>
            <td><?= htmlspecialchars($r['used']) ?></td>
            <td><strong><?= htmlspecialchars($r['remaining']) ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
</body>
</html>
