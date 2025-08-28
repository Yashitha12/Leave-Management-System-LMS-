<?php
// public/admin/leave_types.php
require_once __DIR__ . '/../../lib/auth.php'; require_role(['ADMIN']);
require_once __DIR__ . '/../../config/db.php';

$err = $msg = '';

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $name = trim($_POST['name'] ?? '');
  $code = strtoupper(trim($_POST['code'] ?? ''));
  $max  = (int)($_POST['max_days_per_year'] ?? 0);
  $reqd = isset($_POST['requires_document']) ? 1 : 0;

  if ($name === '' || $code === '' || $max <= 0) {
    $err = "All fields are required and max days must be > 0.";
  } else {
    try {
      if ($id > 0) {
        $st = $pdo->prepare("UPDATE leave_types SET name=?, code=?, max_days_per_year=?, requires_document=? WHERE id=?");
        $st->execute([$name, $code, $max, $reqd, $id]);
        $msg = "Leave type updated.";
      } else {
        $st = $pdo->prepare("INSERT INTO leave_types (name, code, max_days_per_year, requires_document) VALUES (?,?,?,?)");
        $st->execute([$name, $code, $max, $reqd]);
        $msg = "Leave type created.";
      }
    } catch (PDOException $e) {
      $err = "Error: " . $e->getMessage();
    }
  }
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
  $delId = (int)$_GET['delete'];
  try {
    $st = $pdo->prepare("DELETE FROM leave_types WHERE id=?");
    $st->execute([$delId]);
    $msg = "Leave type deleted.";
  } catch (PDOException $e) {
    $err = "Cannot delete: It may be referenced by applications/balances.";
  }
}

// LOAD FOR EDIT
$edit = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $st = $pdo->prepare("SELECT * FROM leave_types WHERE id=?");
  $st->execute([$eid]);
  $edit = $st->fetch();
}

// LIST
$rows = $pdo->query("SELECT * FROM leave_types ORDER BY name")->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Leave Types</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>.actions a{margin-right:6px}</style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<h2>Leave Types</h2>
<?php if ($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?=htmlspecialchars($err)?></div><?php endif; ?>

<form method="post" class="form" style="max-width:600px;">
  <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
  <label>Name</label>
  <input type="text" name="name" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>">

  <div class="grid" style="grid-template-columns:1fr 1fr 1fr;">
    <div>
      <label>Code</label>
      <input type="text" name="code" maxlength="10" required value="<?= htmlspecialchars($edit['code'] ?? '') ?>">
    </div>
    <div>
      <label>Max Days / Year</label>
      <input type="number" name="max_days_per_year" min="1" required value="<?= htmlspecialchars($edit['max_days_per_year'] ?? 14) ?>">
    </div>
    <div style="display:flex;align-items:end;">
      <label style="display:flex;gap:8px;align-items:center;margin-bottom:0;">
        <input type="checkbox" name="requires_document" <?= !empty($edit) && $edit['requires_document'] ? 'checked' : '' ?>>
        Requires Document
      </label>
    </div>
  </div>

  <button type="submit"><?= $edit ? 'Update' : 'Create' ?></button>
  <?php if ($edit): ?><a class="login-btn" href="leave_types.php" style="display:inline-block;padding:.6rem 1rem;margin-left:8px;">Cancel</a><?php endif; ?>
</form>

<h3 style="margin-top:24px;">Existing Types</h3>
<table class="table">
  <thead><tr><th>Name</th><th>Code</th><th>Max/Year</th><th>Needs Doc?</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['code']) ?></td>
        <td><?= htmlspecialchars($r['max_days_per_year']) ?></td>
        <td><?= $r['requires_document'] ? 'Yes' : 'No' ?></td>
        <td class="actions">
          <a href="leave_types.php?edit=<?= $r['id'] ?>">Edit</a>
          <a href="leave_types.php?delete=<?= $r['id'] ?>" onclick="return confirm('Delete this leave type?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
