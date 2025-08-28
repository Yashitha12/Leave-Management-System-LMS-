<?php
require_once __DIR__ . '/../lib/auth.php'; require_login();
require_once __DIR__ . '/../config/db.php';
$u = current_user();
$sql = "SELECT la.*, lt.name AS type_name
        FROM leave_applications la
        JOIN leave_types lt ON lt.id = la.type_id
        WHERE la.user_id = ?
        ORDER BY la.applied_at DESC";
$st = $pdo->prepare($sql); $st->execute([$u['id']]);
$rows = $st->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>My Leaves</title>
<link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<a href="dashboard.php">← Back</a>
<h2>My Leave Applications</h2>
<table class="table">
  <thead><tr><th>Type</th><th>Period</th><th>Days</th><th>Status</th><th>Applied</th></tr></thead>
  <tbody>
  <?php foreach($rows as $r): ?>
    <tr>
      <td><?=htmlspecialchars($r['type_name'])?></td>
      <td><?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?></td>
      <td><?=htmlspecialchars($r['days'])?></td>
      <td><span class="badge <?=$r['status']?>"><?=$r['status']?></span></td>
      <td><?=htmlspecialchars($r['applied_at'])?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body></html>
