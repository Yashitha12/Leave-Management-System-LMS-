<?php
// public/admin/users.php
require_once __DIR__ . '/../../lib/auth.php'; require_role(['ADMIN']);
require_once __DIR__ . '/../../config/db.php';

$err = $msg = '';
$show_temp_pw = null;

// Load departments for dropdown
$deps = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();

// --- Actions ---
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Create / Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','update'], true)) {
  $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $emp_no    = trim($_POST['emp_no'] ?? '');
  $full_name = trim($_POST['full_name'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $role      = $_POST['role'] ?? 'EMPLOYEE';
  $dept_id   = !empty($_POST['dept_id']) ? (int)$_POST['dept_id'] : null;
  $status    = $_POST['status'] ?? 'ACTIVE';
  $password  = $_POST['password'] ?? '';

  if ($emp_no === '' || $full_name === '' || $email === '' || !in_array($role, ['ADMIN','MANAGER','EMPLOYEE'], true) || !in_array($status, ['ACTIVE','INACTIVE'], true)) {
    $err = "Please fill all required fields correctly.";
  } else {
    try {
      if ($action === 'create') {
        if ($password === '') $password = $emp_no; // default to emp_no if not provided
        $st = $pdo->prepare("INSERT INTO users (emp_no, full_name, email, password_hash, role, dept_id, status) VALUES (?,?,?,?,?,?,?)");
        $st->execute([$emp_no, $full_name, $email, hash('sha256',$password), $role, $dept_id, $status]);
        $msg = "User created. Temporary password: {$password}";
      } else { // update
        // If password provided, update; else keep existing
        if ($password !== '') {
          $st = $pdo->prepare("UPDATE users SET emp_no=?, full_name=?, email=?, password_hash=?, role=?, dept_id=?, status=? WHERE id=?");
          $st->execute([$emp_no, $full_name, $email, hash('sha256',$password), $role, $dept_id, $status, $id]);
          $msg = "User updated. Password reset to: {$password}";
        } else {
          $st = $pdo->prepare("UPDATE users SET emp_no=?, full_name=?, email=?, role=?, dept_id=?, status=? WHERE id=?");
          $st->execute([$emp_no, $full_name, $email, $role, $dept_id, $status, $id]);
          $msg = "User updated.";
        }
      }
    } catch (PDOException $e) {
      // 23000 = integrity constraint (unique email/emp_no)
      if ((int)$e->getCode() === 23000) { $err = "emp_no or email already exists."; }
      else { $err = "DB Error: " . $e->getMessage(); }
    }
  }
}

// Soft delete (set INACTIVE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'deactivate') {
  $id = (int)($_POST['id'] ?? 0);
  $st = $pdo->prepare("UPDATE users SET status='INACTIVE' WHERE id=?");
  $st->execute([$id]);
  $msg = "User deactivated.";
}

// Reactivate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'activate') {
  $id = (int)($_POST['id'] ?? 0);
  $st = $pdo->prepare("UPDATE users SET status='ACTIVE' WHERE id=?");
  $st->execute([$id]);
  $msg = "User activated.";
}

// Reset password (generate temp)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'resetpw') {
  $id = (int)($_POST['id'] ?? 0);
  // Generate simple temp password: EMPNO-YYmmdd
  $st = $pdo->prepare("SELECT emp_no FROM users WHERE id=?");
  $st->execute([$id]);
  if ($u = $st->fetch()) {
    $temp = $u['emp_no'] . '-' . date('ymd');
    $up = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
    $up->execute([hash('sha256',$temp), $id]);
    $msg = "Temporary password generated.";
    $show_temp_pw = $temp;
  } else {
    $err = "User not found.";
  }
}

// --- Load for edit ---
$edit = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $st = $pdo->prepare("SELECT * FROM users WHERE id=?");
  $st->execute([$eid]);
  $edit = $st->fetch();
}

// --- Search & list ---
$q = trim($_GET['q'] ?? '');
$params = [];
$where = "1=1";
if ($q !== '') {
  $where .= " AND (u.emp_no LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
  $like = "%{$q}%";
  $params = [$like,$like,$like];
}
$sql = "SELECT u.*, d.name AS dept_name
        FROM users u
        LEFT JOIN departments d ON d.id = u.dept_id
        WHERE $where
        ORDER BY FIELD(u.role,'ADMIN','MANAGER','EMPLOYEE'), u.full_name";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Users</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .actions form { display:inline; }
    .pill{display:inline-block;padding:2px 8px;border-radius:12px;background:#203053;}
    .role-ADMIN{background:#3a234a}.role-MANAGER{background:#1f3b2a}.role-EMPLOYEE{background:#203053}
    .status-ACTIVE{color:#9f9}.status-INACTIVE{color:#f99}
    .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .toolbar input[type="text"]{max-width:280px}
  </style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<h2>Users</h2>

<?php if ($msg): ?><div class="success"><?=htmlspecialchars($msg)?><?php if($show_temp_pw){ echo "<br><strong>Temporary password:</strong> ".htmlspecialchars($show_temp_pw);} ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?=htmlspecialchars($err)?></div><?php endif; ?>

<!-- Create / Edit Form -->
<form method="post" class="form" style="max-width:760px;">
  <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
  <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

  <div class="grid2">
    <div>
      <label>Employee No *</label>
      <input type="text" name="emp_no" required value="<?= htmlspecialchars($edit['emp_no'] ?? '') ?>">
    </div>
    <div>
      <label>Full Name *</label>
      <input type="text" name="full_name" required value="<?= htmlspecialchars($edit['full_name'] ?? '') ?>">
    </div>
    <div>
      <label>Email *</label>
      <input type="email" name="email" required value="<?= htmlspecialchars($edit['email'] ?? '') ?>">
    </div>
    <div>
      <label>Role *</label>
      <select name="role" required>
        <?php
          $roles = ['ADMIN'=>'ADMIN','MANAGER'=>'MANAGER','EMPLOYEE'=>'EMPLOYEE'];
          $cur = $edit['role'] ?? 'EMPLOYEE';
          foreach($roles as $k=>$v){
            $sel = ($cur===$k)?'selected':'';
            echo "<option value=\"$k\" $sel>$v</option>";
          }
        ?>
      </select>
    </div>
    <div>
      <label>Department</label>
      <select name="dept_id">
        <option value="">— None —</option>
        <?php
          $curd = $edit['dept_id'] ?? '';
          foreach($deps as $d){
            $sel = ($curd == $d['id'])?'selected':'';
            echo "<option value=\"{$d['id']}\" $sel>".htmlspecialchars($d['name'])."</option>";
          }
        ?>
      </select>
    </div>
    <div>
      <label>Status *</label>
      <select name="status" required>
        <?php
          $sts = ['ACTIVE'=>'ACTIVE','INACTIVE'=>'INACTIVE'];
          $cs = $edit['status'] ?? 'ACTIVE';
          foreach($sts as $k=>$v){
            $sel = ($cs===$k)?'selected':'';
            echo "<option value=\"$k\" $sel>$v</option>";
          }
        ?>
      </select>
    </div>
  </div>

  <label>Password <?= $edit ? '(leave blank to keep current)' : '(optional; default is emp_no)' ?></label>
  <input type="text" name="password" placeholder="<?= $edit ? 'Leave blank to keep' : 'If empty, will use emp_no' ?>">

  <button type="submit"><?= $edit ? 'Update User' : 'Create User' ?></button>
  <?php if ($edit): ?>
    <a class="login-btn" href="users.php" style="display:inline-block;padding:.6rem 1rem;margin-left:8px;">Cancel</a>
  <?php endif; ?>
</form>

<!-- Toolbar -->
<div class="toolbar" style="margin-top:24px;">
  <form method="get">
    <input type="text" name="q" placeholder="Search by emp no, name, email" value="<?= htmlspecialchars($q) ?>">
    <button type="submit">Search</button>
    <?php if ($q !== ''): ?><a href="users.php" class="login-btn" style="padding:.5rem 1rem;">Clear</a><?php endif; ?>
  </form>
</div>

<!-- List -->
<h3 style="margin-top:12px;">All Users</h3>
<table class="table">
  <thead>
    <tr>
      <th>#</th><th>Emp No</th><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['emp_no']) ?></td>
        <td><?= htmlspecialchars($r['full_name']) ?></td>
        <td><?= htmlspecialchars($r['email']) ?></td>
        <td><span class="pill role-<?= htmlspecialchars($r['role']) ?>"><?= htmlspecialchars($r['role']) ?></span></td>
        <td><?= htmlspecialchars($r['dept_name'] ?? '—') ?></td>
        <td class="status-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></td>
        <td class="actions">
          <a href="users.php?edit=<?= (int)$r['id'] ?>">Edit</a>
          <?php if ($r['status'] === 'ACTIVE'): ?>
            <form method="post" onsubmit="return confirm('Deactivate this user?')">
              <input type="hidden" name="action" value="deactivate">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="danger" type="submit">Deactivate</button>
            </form>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="action" value="activate">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button type="submit">Activate</button>
            </form>
          <?php endif; ?>
          <form method="post" onsubmit="return confirm('Generate a temporary password?')">
            <input type="hidden" name="action" value="resetpw">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button type="submit">Reset Password</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="8">No users found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</body>
</html>
