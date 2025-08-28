<?php
// public/register.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/db.php';

$app = require __DIR__ . '/../config/app.php';

// OPTIONAL toggle: add to config/app.php → 'allow_self_register' => true,
$allow = $app['allow_self_register'] ?? true;
if (!$allow) {
  http_response_code(403);
  exit('Self-registration is disabled. Please contact HR/Admin.');
}

$err = $msg = '';
$deps = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emp_no    = trim($_POST['emp_no'] ?? '');
  $full_name = trim($_POST['full_name'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $dept_id   = $_POST['dept_id'] !== '' ? (int)$_POST['dept_id'] : null;
  $pwd       = $_POST['password'] ?? '';
  $cpwd      = $_POST['confirm_password'] ?? '';

  // Basic validation
  if ($emp_no === '' || $full_name === '' || $email === '' || $pwd === '' || $cpwd === '') {
    $err = 'Please fill all required fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Please enter a valid email address.';
  } elseif ($pwd !== $cpwd) {
    $err = 'Passwords do not match.';
  } elseif (strlen($pwd) < 6) {
    $err = 'Password must be at least 6 characters.';
  } else {
    try {
      // Check duplicates
      $chk = $pdo->prepare("SELECT 1 FROM users WHERE emp_no=? OR email=?");
      $chk->execute([$emp_no, $email]);
      if ($chk->fetchColumn()) {
        $err = 'Employee number or email is already registered.';
      } else {
        $st = $pdo->prepare("
          INSERT INTO users (emp_no, full_name, email, password_hash, role, dept_id, status)
          VALUES (?,?,?,?, 'EMPLOYEE', ?, 'INACTIVE')
        ");
        $st->execute([$emp_no, $full_name, $email, hash('sha256', $pwd), $dept_id]);
        $msg = 'Account created! An admin must activate your account before you can sign in.';
      }
    } catch (PDOException $e) {
      $err = 'Database error: ' . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Registration - LeaveMS</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
      pointer-events: none;
    }

    .register-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
      padding: 40px;
      width: 100%;
      max-width: 600px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      position: relative;
      animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .back-btn {
      position: absolute;
      top: 20px;
      left: 20px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      text-decoration: none;
      padding: 10px 15px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .back-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }

    .logo-section {
      text-align: center;
      margin-bottom: 30px;
      margin-top: 20px;
    }

    .logo-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      color: white;
      font-size: 24px;
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .logo-title {
      font-size: 24px;
      font-weight: 700;
      color: #333;
      margin-bottom: 5px;
    }

    .logo-subtitle {
      color: #666;
      font-size: 13px;
      font-weight: 400;
      margin-bottom: 10px;
    }

    .info-text {
      color: #7c8db5;
      font-size: 14px;
      text-align: center;
      background: rgba(102, 126, 234, 0.1);
      padding: 12px;
      border-radius: 10px;
      margin-bottom: 25px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-group label {
      display: block;
      color: #555;
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 14px;
    }

    .required {
      color: #ff6b6b;
    }

    .input-wrapper {
      position: relative;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 15px 20px 15px 50px;
      border: 2px solid #e1e5e9;
      border-radius: 12px;
      font-size: 16px;
      background: white;
      transition: all 0.3s ease;
      color: #333;
    }

    .form-group select {
      cursor: pointer;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      transform: translateY(-2px);
    }

    .input-icon {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
      font-size: 18px;
      z-index: 2;
    }

    .form-group input:focus + .input-icon,
    .form-group select:focus + .input-icon {
      color: #667eea;
    }

    .password-note {
      color: #999;
      font-size: 12px;
      margin-top: 5px;
      font-style: italic;
    }

    .btn-register {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      border-radius: 12px;
      color: white;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
      position: relative;
      overflow: hidden;
    }

    .btn-register:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }

    .btn-register:active {
      transform: translateY(0);
    }

    .alert {
      background: linear-gradient(135deg, #ff6b6b, #ee5a52);
      color: white;
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 14px;
      font-weight: 500;
      border-left: 4px solid #ff5252;
      animation: shake 0.5s ease-in-out;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .success {
      background: linear-gradient(135deg, #51cf66, #40c057);
      color: white;
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 14px;
      font-weight: 500;
      border-left: 4px solid #37b24d;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .login-link {
      text-align: center;
      margin-top: 25px;
      padding-top: 20px;
      border-top: 1px solid #e1e5e9;
    }

    .login-link a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s ease;
      position: relative;
    }

    .login-link a:hover {
      color: #764ba2;
    }

    .login-link a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -2px;
      left: 50%;
      background: linear-gradient(135deg, #667eea, #764ba2);
      transition: all 0.3s ease;
      transform: translateX(-50%);
    }

    .login-link a:hover::after {
      width: 100%;
    }

    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .register-container {
        padding: 30px 25px;
        margin: 10px;
        max-width: 500px;
      }

      .back-btn {
        position: static;
        margin-bottom: 20px;
        align-self: flex-start;
        width: fit-content;
      }

      .logo-title {
        font-size: 22px;
      }

      .form-group input,
      .form-group select {
        padding: 14px 18px 14px 45px;
        font-size: 15px;
      }

      .input-icon {
        left: 15px;
        font-size: 16px;
      }
    }

    /* Loading animation for button */
    .btn-register.loading {
      pointer-events: none;
    }

    .btn-register.loading::after {
      content: '';
      position: absolute;
      width: 20px;
      height: 20px;
      border: 2px solid transparent;
      border-top: 2px solid white;
      border-radius: 50%;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: translateY(-50%) rotate(0deg); }
      100% { transform: translateY(-50%) rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="register-container">
    <a href="index.php" class="back-btn">
      <i class="fas fa-arrow-left"></i>
      Back to Login
    </a>

    <div class="logo-section">
      <div class="logo-icon">
        <i class="fas fa-user-plus"></i>
      </div>
      <div class="logo-title">Join LeaveMS</div>
      <div class="logo-subtitle">Employee Leave Management System</div>
    </div>

    <div class="info-text">
      <i class="fas fa-info-circle"></i>
      After registration, an Admin will review and activate your account.
    </div>

    <?php if ($msg): ?>
      <div class="success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($err): ?>
      <div class="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($err) ?>
      </div>
    <?php endif; ?>

    <form method="post" id="registerForm" autocomplete="off">
      <div class="form-grid">
        <div class="form-group">
          <label for="emp_no">Employee No <span class="required">*</span></label>
          <div class="input-wrapper">
            <input type="text" id="emp_no" name="emp_no" required 
                   value="<?= htmlspecialchars($_POST['emp_no'] ?? '') ?>">
            <i class="fas fa-id-badge input-icon"></i>
          </div>
        </div>
        
        <div class="form-group">
          <label for="full_name">Full Name <span class="required">*</span></label>
          <div class="input-wrapper">
            <input type="text" id="full_name" name="full_name" required 
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            <i class="fas fa-user input-icon"></i>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="email">Email Address <span class="required">*</span></label>
        <div class="input-wrapper">
          <input type="email" id="email" name="email" required 
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          <i class="fas fa-envelope input-icon"></i>
        </div>
      </div>

      <div class="form-group">
        <label for="dept_id">Department</label>
        <div class="input-wrapper">
          <select id="dept_id" name="dept_id">
            <option value="">— Select Department —</option>
            <?php
              $cur = $_POST['dept_id'] ?? '';
              foreach ($deps as $d) {
                $sel = ($cur !== '' && (int)$cur === (int)$d['id']) ? 'selected' : '';
                echo "<option value=\"{$d['id']}\" $sel>".htmlspecialchars($d['name'])."</option>";
              }
            ?>
          </select>
          <i class="fas fa-building input-icon"></i>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="password">Password <span class="required">*</span></label>
          <div class="input-wrapper">
            <input type="password" id="password" name="password" required>
            <i class="fas fa-lock input-icon"></i>
          </div>
          <div class="password-note">Minimum 6 characters</div>
        </div>
        
        <div class="form-group">
          <label for="confirm_password">Confirm Password <span class="required">*</span></label>
          <div class="input-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" required>
            <i class="fas fa-lock input-icon"></i>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-register">
        <i class="fas fa-user-plus" style="margin-right: 8px;"></i>
        Create Account
      </button>
    </form>

    <div class="login-link">
      Already have an account? 
      <a href="index.php">
        <i class="fas fa-sign-in-alt" style="margin-right: 5px;"></i>
        Sign in here
      </a>
    </div>
  </div>

  <script>
    // Add loading state to form submission
    document.getElementById('registerForm').addEventListener('submit', function() {
      const btn = document.querySelector('.btn-register');
      btn.classList.add('loading');
      btn.innerHTML = '<i class="fas fa-user-plus" style="margin-right: 8px;"></i>Creating Account...';
    });

    // Real-time password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    function validatePasswordMatch() {
      if (confirmPassword.value && password.value !== confirmPassword.value) {
        confirmPassword.style.borderColor = '#ff6b6b';
        confirmPassword.style.boxShadow = '0 0 0 3px rgba(255, 107, 107, 0.1)';
      } else if (confirmPassword.value) {
        confirmPassword.style.borderColor = '#51cf66';
        confirmPassword.style.boxShadow = '0 0 0 3px rgba(81, 207, 102, 0.1)';
      } else {
        confirmPassword.style.borderColor = '#e1e5e9';
        confirmPassword.style.boxShadow = 'none';
      }
    }

    password.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);

    // Add input focus animations
    document.querySelectorAll('input, select').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
      });
    });

    // Auto-focus first input
    window.addEventListener('load', function() {
      const firstInput = document.querySelector('input[name="emp_no"]');
      if (firstInput && !firstInput.value) {
        firstInput.focus();
      }
    });

    // Form validation enhancement
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      
      if (password !== confirmPassword) {
        e.preventDefault();
        const confirmField = document.getElementById('confirm_password');
        confirmField.style.borderColor = '#ff6b6b';
        confirmField.focus();
        
        // Show temporary error styling
        setTimeout(() => {
          if (confirmField.value === '') {
            confirmField.style.borderColor = '#e1e5e9';
          }
        }, 3000);
      }
    });
  </script>
</body>
</html>