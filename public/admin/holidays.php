<?php
// public/admin/holidays.php
require_once __DIR__ . '/../../lib/auth.php'; require_role(['ADMIN']);
require_once __DIR__ . '/../../config/db.php';

$err = $msg = '';

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id    = (int)($_POST['id'] ?? 0);
  $hdate = trim($_POST['hdate'] ?? '');
  $title = trim($_POST['title'] ?? '');

  if ($hdate === '' || $title === '') {
    $err = "Date and Title are required.";
  } else {
    try {
      if ($id > 0) {
        $st = $pdo->prepare("UPDATE holidays SET hdate=?, title=? WHERE id=?");
        $st->execute([$hdate, $title, $id]);
        $msg = "Holiday updated.";
      } else {
        $st = $pdo->prepare("INSERT INTO holidays (hdate, title) VALUES (?,?)");
        $st->execute([$hdate, $title]);
        $msg = "Holiday added.";
      }
    } catch (PDOException $e) {
      // Duplicate date constraint
      if ((int)$e->getCode() === 23000) {
        $err = "A holiday already exists for that date.";
      } else {
        $err = "Error: " . $e->getMessage();
      }
    }
  }
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
  $delId = (int)$_GET['delete'];
  try {
    $st = $pdo->prepare("DELETE FROM holidays WHERE id=?");
    $st->execute([$delId]);
    $msg = "Holiday deleted.";
  } catch (PDOException $e) {
    $err = "Error: " . $e->getMessage();
  }
}

// LOAD FOR EDIT
$edit = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $st = $pdo->prepare("SELECT * FROM holidays WHERE id=?");
  $st->execute([$eid]);
  $edit = $st->fetch();
}

$rows = $pdo->query("SELECT * FROM holidays ORDER BY hdate ASC")->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Holidays</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>.actions a{margin-right:6px}</style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<h2>Company Holidays</h2>
<?php if ($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?=htmlspecialchars($err)?></div><?php endif; ?>

<form method="post" class="form" style="max-width:600px;">
  <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
  <div class="grid" style="grid-template-columns:1fr 2fr;">
    <div>
      <label>Date</label>
      <input type="date" name="hdate" required value="<?= htmlspecialchars($edit['hdate'] ?? '') ?>">
    </div>
    <div>
      <label>Title</label>
      <input type="text" name="title" required placeholder="e.g., Sinhala & Tamil New Year" value="<?= htmlspecialchars($edit['title'] ?? '') ?>">
    </div>
  </div>
  <button type="submit"><?= $edit ? 'Update' : 'Add' ?></button>
  <?php if ($edit): ?><a class="login-btn" href="holidays.php" style="display:inline-block;padding:.6rem 1rem;margin-left:8px;">Cancel</a><?php endif; ?>
</form>

<h3 style="margin-top:24px;">Holiday Calendar</h3>
<table class="table">
  <thead><tr><th>Date</th><th>Day</th><th>Title</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['hdate']) ?></td>
        <td><?= date('l', strtotime($r['hdate'])) ?></td>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td class="actions">
          <a href="holidays.php?edit=<?= $r['id'] ?>">Edit</a>
          <a href="holidays.php?delete=<?= $r['id'] ?>" onclick="return confirm('Delete this holiday?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="4">No holidays added yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</body>
</html>
