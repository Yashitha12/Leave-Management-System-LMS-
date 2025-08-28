<?php
// public/admin/approvals.php
require_once __DIR__ . '/../../lib/auth.php'; require_role(['ADMIN','MANAGER']);
require_once __DIR__ . '/../../config/db.php';

$err = $msg = '';
$u = current_user();

/**
 * Deduct leave balance inside the current transaction.
 * Locks the balance row and updates only if remaining is sufficient.
 */
function tx_deduct_balance(PDO $pdo, int $user_id, int $type_id, int $year, float $days): bool {
  // Lock row
  $st = $pdo->prepare("SELECT id, used, remaining FROM leave_balances
                       WHERE user_id=? AND type_id=? AND year=? FOR UPDATE");
  $st->execute([$user_id, $type_id, $year]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) return false;
  if ((float)$row['remaining'] < $days) return false;

  $used = (float)$row['used'] + $days;
  $remaining = (float)$row['remaining'] - $days;

  $up = $pdo->prepare("UPDATE leave_balances SET used=?, remaining=? WHERE id=?");
  $up->execute([$used, $remaining, $row['id']]);

  return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  $action = $_POST['action'] ?? '';

  if ($id && in_array($action, ['APPROVED','REJECTED'], true)) {
    try {
      // If a previous request left a dangling TX, clean it up
      if ($pdo->inTransaction()) { $pdo->rollBack(); }

      $pdo->beginTransaction();

      // Lock the leave application row
      $st = $pdo->prepare("SELECT * FROM leave_applications WHERE id=? FOR UPDATE");
      $st->execute([$id]);
      $la = $st->fetch(PDO::FETCH_ASSOC);

      if (!$la) {
        $err = "Not found.";
        $pdo->rollBack();
      } elseif ($la['status'] !== 'PENDING') {
        $err = "Already processed.";
        $pdo->rollBack();
      } else {
        // If manager, ensure the request belongs to their department
        if ($u['role'] === 'MANAGER') {
          $chk = $pdo->prepare("SELECT dept_id FROM users WHERE id=?");
          $chk->execute([$la['user_id']]);
          $dept = $chk->fetchColumn();
          if ((int)$dept !== (int)$u['dept_id']) {
            $err = "Forbidden: out of your department.";
            $pdo->rollBack();
          }
        }

        if (!$err && $action === 'APPROVED') {
          $year = (int)date('Y', strtotime($la['start_date']));
          $ok = tx_deduct_balance($pdo, (int)$la['user_id'], (int)$la['type_id'], $year, (float)$la['days']);
          if (!$ok) {
            $err = "Insufficient balance or balance not initialized.";
            $pdo->rollBack();
          }
        }

        if (!$err) {
          $up = $pdo->prepare("UPDATE leave_applications
                               SET status=?, approver_id=?, decided_at=NOW()
                               WHERE id=?");
          $up->execute([$action, $u['id'], $id]);
          $pdo->commit();
          $msg = "Request #$id $action.";
        }
      }
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) { $pdo->rollBack(); }
      $err = "Error: " . $e->getMessage();
    }
  }
}

// ---- List pending (managers see only their department) ----
$where = "la.status='PENDING'";
$params = [];

if ($u['role'] === 'MANAGER') {
  $where .= " AND u.dept_id = ?";
  $params[] = $u['dept_id'];
}

$sql = "SELECT la.*, u.full_name, u.emp_no, lt.name AS type_name
        FROM leave_applications la
        JOIN users u ON u.id = la.user_id
        JOIN leave_types lt ON lt.id = la.type_id
        WHERE $where
        ORDER BY la.applied_at ASC";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Approvals</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .actions button{width:auto;display:inline-block;margin-right:6px}
    .small{color:#9fb; font-size:.9rem}
  </style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<h2>Pending Approvals</h2>
<?php if ($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?=htmlspecialchars($err)?></div><?php endif; ?>

<table class="table">
  <thead>
    <tr>
      <th>#</th><th>Employee</th><th>Type</th><th>Period</th><th>Days</th><th>Reason</th><th>Attachment</th><th>Applied</th><th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td>
          <?= htmlspecialchars($r['full_name']) ?><br>
          <span class="small"><?= htmlspecialchars($r['emp_no']) ?></span>
        </td>
        <td><?= htmlspecialchars($r['type_name']) ?></td>
        <td><?= htmlspecialchars($r['start_date']) ?> → <?= htmlspecialchars($r['end_date']) ?></td>
        <td><?= htmlspecialchars($r['days']) ?></td>
        <td><?= nl2br(htmlspecialchars($r['reason'])) ?></td>
        <td>
          <?php if ($r['attachment_path']): ?>
            <a href="../<?= htmlspecialchars($r['attachment_path']) ?>" target="_blank">View</a>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($r['applied_at']) ?></td>
        <td class="actions">
          <form method="post">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button name="action" value="APPROVED">Approve</button>
            <button name="action" value="REJECTED" class="danger" onclick="return confirm('Reject this request?')">Reject</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="9">No pending requests.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</body>
</html>
