<?php
// public/admin/balances_init.php
require_once __DIR__ . '/../../lib/auth.php'; require_role(['ADMIN']);
require_once __DIR__ . '/../../config/db.php';

$err = $msg = '';
$year = (int)($_GET['year'] ?? $_POST['year'] ?? date('Y'));
$user_id = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

// Load dropdown data
$users = $pdo->query("SELECT id, emp_no, full_name FROM users WHERE status='ACTIVE' ORDER BY full_name")->fetchAll();
$types = $pdo->query("SELECT id, code, name, max_days_per_year FROM leave_types ORDER BY name")->fetchAll();

function loadBalances(PDO $pdo, int $user_id, int $year) {
  $st = $pdo->prepare("SELECT lb.*, lt.name, lt.code FROM leave_balances lb JOIN leave_types lt ON lt.id = lb.type_id WHERE lb.user_id=? AND lb.year=? ORDER BY lt.name");
  $st->execute([$user_id, $year]);
  return $st->fetchAll();
}

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if (!$user_id) {
    $err = 'Please select a user.';
  } else {
    try {
      if ($action === 'init_defaults') {
        // Create a balance row for every leave type using defaults
        $ins = $pdo->prepare("INSERT INTO leave_balances (user_id, type_id, year, allocated, carried_over, used, remaining)
                              VALUES (?,?,?,?,0,0,?)
                              ON DUPLICATE KEY UPDATE allocated=VALUES(allocated), remaining=VALUES(remaining)");
        foreach ($types as $t) {
          $alloc = (float)$t['max_days_per_year'];
          $ins->execute([$user_id, (int)$t['id'], $year, $alloc, $alloc]);
        }
        $msg = "Initialized balances from defaults for year $year.";
      }

      if ($action === 'save_table') {
        // Update posted rows
        $ids = $_POST['row_id'] ?? [];
        $allocated = $_POST['allocated'] ?? [];
        $carried = $_POST['carried_over'] ?? [];
        $used = $_POST['used'] ?? [];
        $remaining = $_POST['remaining'] ?? [];

        $up = $pdo->prepare("UPDATE leave_balances SET allocated=?, carried_over=?, used=?, remaining=? WHERE id=? AND user_id=? AND year=?");
        for ($i=0; $i<count($ids); $i++) {
          $up->execute([
            (float)$allocated[$i],
            (float)$carried[$i],
            (float)$used[$i],
            max(0, (float)$remaining[$i]),
            (int)$ids[$i],
            $user_id,
            $year
          ]);
        }
        $msg = "Balances updated.";
      }

      if ($action === 'add_single') {
        $type_id = (int)($_POST['type_id'] ?? 0);
        $alloc   = (float)($_POST['alloc'] ?? 0);
        $carry   = (float)($_POST['carry'] ?? 0);
        if ($type_id <= 0 || $alloc < 0 || $carry < 0) {
          $err = 'Please fill the single-add form correctly.';
        } else {
          // Upsert one row
          $rem = $alloc + $carry; // used=0 on create
          $st = $pdo->prepare("INSERT INTO leave_balances (user_id, type_id, year, allocated, carried_over, used, remaining)
                               VALUES (?,?,?,?,0,?)
                               ON DUPLICATE KEY UPDATE allocated=VALUES(allocated), carried_over=VALUES(carried_over), remaining=GREATEST(0, VALUES(allocated)+VALUES(carried_over)-used)");
          $st->execute([$user_id, $type_id, $year, $alloc, $rem]);
          $msg = "Added/updated single balance.";
        }
      }

    } catch (Throwable $e) {
      $err = "Error: " . $e->getMessage();
    }
  }
}

$rows = ($user_id ? loadBalances($pdo, $user_id, $year) : []);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Initialize Leave Balances</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .muted{color:#9db0c9}
    .small{font-size:.9rem;color:#9db0c9}
    .table td, .table th { text-align:center; }
    input[type="number"]{ text-align:right; }
  </style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<h2>Initialize Leave Balances</h2>

<?php if ($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Picker -->
<form method="get" class="form" style="max-width:700px;">
  <div class="grid2">
    <div>
      <label>User</label>
      <select name="user_id" required>
        <option value="">— Select —</option>
        <?php foreach($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= $user_id===$u['id']?'selected':'' ?>>
            <?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['emp_no']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Year</label>
      <input type="number" name="year" value="<?= htmlspecialchars($year) ?>" required>
    </div>
  </div>
  <button type="submit">Load</button>
</form>

<?php if ($user_id): ?>
  <!-- Initialize defaults -->
  <form method="post" style="margin-top:16px;">
    <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
    <input type="hidden" name="year" value="<?= (int)$year ?>">
    <input type="hidden" name="action" value="init_defaults">
    <button type="submit">Initialize from Leave Type Defaults</button>
    <span class="small">Creates/updates rows using each type’s <em>max_days_per_year</em>.</span>
  </form>

  <!-- Current balances table -->
  <h3 style="margin-top:18px;">Current Balances</h3>
  <?php if (!$rows): ?>
    <div class="alert">No balances yet for this user/year. Click “Initialize from Leave Type Defaults”.</div>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
      <input type="hidden" name="year" value="<?= (int)$year ?>">
      <input type="hidden" name="action" value="save_table">

      <table class="table">
        <thead>
          <tr>
            <th>Type</th>
            <th>Allocated</th>
            <th>Carried Over</th>
            <th>Used</th>
            <th>Remaining</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['code']) ?>)
                <input type="hidden" name="row_id[]" value="<?= (int)$r['id'] ?>">
              </td>
              <td><input type="number" step="0.01" name="allocated[]" value="<?= htmlspecialchars($r['allocated']) ?>"></td>
              <td><input type="number" step="0.01" name="carried_over[]" value="<?= htmlspecialchars($r['carried_over']) ?>"></td>
              <td><input type="number" step="0.01" name="used[]" value="<?= htmlspecialchars($r['used']) ?>"></td>
              <td><input class="remain" type="number" step="0.01" name="remaining[]" value="<?= htmlspecialchars($r['remaining']) ?>"></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <button type="submit">Save Changes</button>
      <span class="small">Tip: Remaining is usually <em>allocated + carried_over − used</em>.</span>
    </form>
  <?php endif; ?>

  <!-- Add one balance -->
  <h3 style="margin-top:20px;">Add / Upsert a Single Balance</h3>
  <form method="post" class="form" style="max-width:700px;">
    <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
    <input type="hidden" name="year" value="<?= (int)$year ?>">
    <input type="hidden" name="action" value="add_single">
    <div class="grid2">
      <div>
        <label>Leave Type</label>
        <select name="type_id" required>
          <option value="">— Select —</option>
          <?php foreach($types as $t): ?>
            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['code']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Allocated</label>
        <input type="number" name="alloc" step="0.01" value="0">
      </div>
      <div>
        <label>Carried Over</label>
        <input type="number" name="carry" step="0.01" value="0">
      </div>
    </div>
    <button type="submit">Add / Update</button>
  </form>
<?php endif; ?>

<script>
// Auto-calc Remaining if user edits Alloc/Carry/Used
document.addEventListener('input', (e) => {
  const row = e.target.closest('tr');
  if (!row) return;
  const alloc = row.querySelector('input[name="allocated[]"]');
  const carry = row.querySelector('input[name="carried_over[]"]');
  const used  = row.querySelector('input[name="used[]"]');
  const rem   = row.querySelector('input[name="remaining[]"]');
  if (alloc && carry && used && rem) {
    const a = parseFloat(alloc.value || '0');
    const c = parseFloat(carry.value || '0');
    const u = parseFloat(used.value  || '0');
    rem.value = Math.max(0, (a + c - u)).toFixed(2);
  }
});
</script>
</body>
</html>
