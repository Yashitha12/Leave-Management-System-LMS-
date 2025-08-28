<?php require_once __DIR__ . '/../lib/auth.php'; ?>
<?php
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
    header('Location: dashboard.php'); exit;
  } else { $err = 'Invalid credentials'; }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - LeaveMS</title>
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
      overflow: hidden;
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

    .login-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
      padding: 40px;
      width: 100%;
      max-width: 420px;
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

    .logo-section {
      text-align: center;
      margin-bottom: 30px;
    }

    .logo-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      color: white;
      font-size: 30px;
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .logo-title {
      font-size: 28px;
      font-weight: 700;
      color: #333;
      margin-bottom: 5px;
    }

    .logo-subtitle {
      color: #666;
      font-size: 14px;
      font-weight: 400;
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

    .input-wrapper {
      position: relative;
    }

    .form-group input {
      width: 100%;
      padding: 15px 20px 15px 50px;
      border: 2px solid #e1e5e9;
      border-radius: 12px;
      font-size: 16px;
      background: white;
      transition: all 0.3s ease;
      color: #333;
    }

    .form-group input:focus {
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

    .form-group input:focus + .input-icon {
      color: #667eea;
    }

    .btn-login {
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

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }

    .btn-login:active {
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
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .register-link {
      text-align: center;
      margin-top: 25px;
      padding-top: 20px;
      border-top: 1px solid #e1e5e9;
    }

    .register-link a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s ease;
      position: relative;
    }

    .register-link a:hover {
      color: #764ba2;
    }

    .register-link a::after {
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

    .register-link a:hover::after {
      width: 100%;
    }

    @media (max-width: 480px) {
      .login-container {
        padding: 30px 25px;
        margin: 10px;
      }

      .logo-title {
        font-size: 24px;
      }

      .form-group input {
        padding: 14px 18px 14px 45px;
        font-size: 15px;
      }

      .input-icon {
        left: 15px;
        font-size: 16px;
      }
    }

    /* Loading animation for button */
    .btn-login.loading {
      pointer-events: none;
    }

    .btn-login.loading::after {
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
  <div class="login-container">
    <div class="logo-section">
      <div class="logo-icon">
        <i class="fas fa-calendar-check"></i>
      </div>
      <div class="logo-title">LeaveMS</div>
      <div class="logo-subtitle">Employee Leave Management System</div>
    </div>

    <form method="post" id="loginForm">
      <?php if ($err): ?>
        <div class="alert">
          <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-wrapper">
          <input type="email" id="email" name="email" required autocomplete="email" 
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          <i class="fas fa-envelope input-icon"></i>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <input type="password" id="password" name="password" required autocomplete="current-password">
          <i class="fas fa-lock input-icon"></i>
        </div>
      </div>

      <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
        Sign In
      </button>
    </form>

    <div class="register-link">
      <a href="register.php">
        <i class="fas fa-user-plus" style="margin-right: 5px;"></i>
        Create an employee account
      </a>
    </div>
  </div>

  <script>
    // Add loading state to form submission
    document.getElementById('loginForm').addEventListener('submit', function() {
      const btn = document.querySelector('.btn-login');
      btn.classList.add('loading');
      btn.innerHTML = '<i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>Signing In...';
    });

    // Add input focus animations
    document.querySelectorAll('input').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
      });
    });

    // Auto-focus first input
    window.addEventListener('load', function() {
      const firstInput = document.querySelector('input[type="email"]');
      if (firstInput && !firstInput.value) {
        firstInput.focus();
      }
    });
  </script>
</body>
</html>