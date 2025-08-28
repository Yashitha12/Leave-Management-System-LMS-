<?php
require_once __DIR__ . '/../lib/auth.php'; require_login();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

$u = current_user();
$msg = $err = '';

$types = $pdo->query("SELECT id, name FROM leave_types ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $type_id = (int)($_POST['type_id'] ?? 0);
  $start   = $_POST['start_date'] ?? '';
  $end     = $_POST['end_date'] ?? '';
  $reason  = $_POST['reason'] ?? '';

  $days = working_days_between($start, $end);
  if ($days <= 0) { $err = "Invalid date range."; }
  else {
    // file upload (optional)
    $path = null;
    if (!empty($_FILES['attachment']['name'])) {
      $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
      $safe = 'DOC_' . $u['id'] . '_' . time() . '.' . $ext;
      $dest = (require __DIR__ . '/../config/app.php')['upload_dir'] . '/' . $safe;
      if (is_uploaded_file($_FILES['attachment']['tmp_name'])) {
        move_uploaded_file($_FILES['attachment']['tmp_name'], $dest);
        $path = 'uploads/' . $safe;
      }
    }

    $st = $pdo->prepare("INSERT INTO leave_applications (user_id,type_id,start_date,end_date,days,reason,attachment_path)
                         VALUES (?,?,?,?,?,?,?)");
    $st->execute([$u['id'],$type_id,$start,$end,$days,$reason,$path]);
    $msg = "Leave submitted ($days day(s)).";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for Leave - HR Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --bg-primary: #0a0e1a;
      --bg-secondary: #1a1d29;
      --bg-tertiary: #242938;
      --bg-hover: #2d3348;
      --accent-primary: #6366f1;
      --accent-secondary: #8b5cf6;
      --accent-tertiary: #06b6d4;
      --text-primary: #f8fafc;
      --text-secondary: #cbd5e1;
      --text-muted: #64748b;
      --border-primary: #334155;
      --border-secondary: #475569;
      --success: #10b981;
      --warning: #f59e0b;
      --error: #ef4444;
      --glass-bg: rgba(255, 255, 255, 0.05);
      --glass-border: rgba(255, 255, 255, 0.1);
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg-primary);
      background-image: 
        radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
      min-height: 100vh;
      color: var(--text-primary);
      overflow-x: hidden;
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 1rem;
      position: relative;
    }

    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem;
      position: relative;
    }

    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      color: var(--text-secondary);
      text-decoration: none;
      font-weight: 500;
      padding: 0.75rem 1.5rem;
      border-radius: 12px;
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(20px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .back-btn::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(45deg, var(--accent-primary), var(--accent-secondary));
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: inherit;
    }

    .back-btn:hover::before {
      opacity: 0.1;
    }

    .back-btn:hover {
      color: var(--text-primary);
      transform: translateY(-2px);
      border-color: var(--accent-primary);
    }

    .page-title {
      font-size: 2.5rem;
      font-weight: 800;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin: 0;
      text-align: center;
      flex: 1;
    }

    .form-container {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      padding: 2.5rem;
      backdrop-filter: blur(20px);
      position: relative;
      overflow: hidden;
    }

    .form-container::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
      border-radius: inherit;
      pointer-events: none;
    }

    .alert {
      padding: 1rem 1.5rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 500;
      position: relative;
      backdrop-filter: blur(10px);
      animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .alert.success {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #6ee7b7;
    }

    .alert.error {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1) 100%);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #fca5a5;
    }

    .form-section {
      position: relative;
      z-index: 1;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .section-title::before {
      content: '';
      width: 4px;
      height: 20px;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      border-radius: 2px;
    }

    .form-group {
      margin-bottom: 1.5rem;
      position: relative;
    }

    .form-label {
      display: block;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.75rem;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .required-indicator {
      color: var(--accent-secondary);
      font-weight: 700;
    }

    .form-input,
    .form-select,
    .form-textarea {
      width: 100%;
      padding: 1rem 1.25rem;
      border: 2px solid var(--border-primary);
      border-radius: 12px;
      font-size: 0.875rem;
      background: var(--bg-secondary);
      color: var(--text-primary);
      font-family: inherit;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
      outline: none;
      border-color: var(--accent-primary);
      background: var(--bg-tertiary);
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
      transform: translateY(-1px);
    }

    .form-textarea {
      resize: vertical;
      min-height: 120px;
      font-family: 'JetBrains Mono', monospace;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr auto;
      gap: 1.5rem;
      align-items: end;
    }

    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
    }

    .file-upload-area {
      border: 2px dashed var(--border-primary);
      border-radius: 12px;
      padding: 2rem;
      text-align: center;
      background: var(--bg-secondary);
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .file-upload-area:hover {
      border-color: var(--accent-primary);
      background: var(--bg-tertiary);
    }

    .file-upload-area.dragover {
      border-color: var(--accent-secondary);
      background: rgba(139, 92, 246, 0.1);
      transform: scale(1.02);
    }

    .file-input {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
    }

    .upload-content {
      pointer-events: none;
    }

    .upload-icon {
      width: 3rem;
      height: 3rem;
      margin: 0 auto 1rem;
      color: var(--accent-primary);
    }

    .upload-text {
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.5rem;
    }

    .upload-hint {
      font-size: 0.875rem;
      color: var(--text-muted);
    }

    .submit-btn {
      width: 100%;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      color: var(--text-primary);
      border: none;
      padding: 1rem 2rem;
      border-radius: 12px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      margin-top: 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      position: relative;
      overflow: hidden;
    }

    .submit-btn::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, var(--accent-secondary), var(--accent-tertiary));
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .submit-btn:hover::before {
      opacity: 1;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    .readonly-field {
      background: var(--bg-tertiary) !important;
      color: var(--text-muted);
      cursor: not-allowed;
      border-style: dashed;
    }

    .days-counter {
      background: linear-gradient(135deg, var(--accent-tertiary), var(--accent-primary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 700;
      font-size: 1.125rem;
      text-align: center;
      padding: 1rem;
      border-radius: 12px;
      background-color: var(--bg-tertiary);
      border: 2px solid var(--border-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 60px;
      font-family: 'JetBrains Mono', monospace;
    }

    .form-hint {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-top: 0.5rem;
      font-style: italic;
    }

    .icon {
      width: 1.25rem;
      height: 1.25rem;
      flex-shrink: 0;
    }

    .progress-bar {
      position: fixed;
      top: 0;
      left: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
      transition: width 0.3s ease;
      z-index: 1000;
    }

    .floating-elements {
      position: fixed;
      inset: 0;
      pointer-events: none;
      overflow: hidden;
    }

    .floating-element {
      position: absolute;
      width: 4px;
      height: 4px;
      background: var(--accent-primary);
      border-radius: 50%;
      animation: float 15s infinite linear;
    }

    @keyframes float {
      from {
        transform: translateY(100vh) rotate(0deg);
        opacity: 0;
      }
      10% { opacity: 1; }
      90% { opacity: 1; }
      to {
        transform: translateY(-10vh) rotate(360deg);
        opacity: 0;
      }
    }

    .character-counter {
      position: absolute;
      bottom: 0.75rem;
      right: 0.75rem;
      font-size: 0.75rem;
      color: var(--text-muted);
      font-family: 'JetBrains Mono', monospace;
    }

    .smart-suggestions {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: var(--bg-secondary);
      border: 1px solid var(--border-primary);
      border-radius: 8px;
      margin-top: 0.25rem;
      z-index: 100;
      max-height: 200px;
      overflow-y: auto;
      display: none;
    }

    .suggestion-item {
      padding: 0.75rem 1rem;
      cursor: pointer;
      transition: background 0.2s ease;
      border-bottom: 1px solid var(--border-primary);
    }

    .suggestion-item:hover {
      background: var(--bg-hover);
    }

    .suggestion-item:last-child {
      border-bottom: none;
    }
  </style>
</head>
<body>
  <div class="floating-elements" id="floatingElements"></div>
  <div class="progress-bar" id="progressBar"></div>

  <div class="container">
    <div class="header">
      <a href="dashboard.php" class="back-btn">
        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Dashboard
      </a>
      <h1 class="page-title">Leave Application</h1>
      <div style="width: 120px;"></div>
    </div>

    <div class="form-container">
      <?php if ($msg): ?>
        <div class="alert success">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <?php if ($err): ?>
        <div class="alert error">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="form-section">
        <div class="section-title">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11m-6 0h8m-8 0V7a2 2 0 012-2m6 2a2 2 0 012 2v4"></path>
          </svg>
          Leave Details
        </div>

        <div class="form-group">
          <label class="form-label" for="type_id">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
            Leave Type <span class="required-indicator">*</span>
          </label>
          <select name="type_id" id="type_id" class="form-select" required>
            <option value="">Select leave type</option>
            <?php foreach ($types as $t): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label" for="start_date">
              <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11m-6 0h8m-8 0V7a2 2 0 012-2m6 2a2 2 0 012 2v4"></path>
              </svg>
              Start Date <span class="required-indicator">*</span>
            </label>
            <input type="date" name="start_date" id="start_date" class="form-input" required>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="end_date">
              <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11m-6 0h8m-8 0V7a2 2 0 012-2m6 2a2 2 0 012 2v4"></path>
              </svg>
              End Date <span class="required-indicator">*</span>
            </label>
            <input type="date" name="end_date" id="end_date" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">
              <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
              </svg>
              Working Days
            </label>
            <div class="days-counter" id="daysCounter">Select dates</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="reason">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Reason for Leave
          </label>
          <div style="position: relative;">
            <textarea 
              name="reason" 
              id="reason" 
              class="form-textarea" 
              rows="4" 
              placeholder="Please provide a detailed reason for your leave request..."
              maxlength="500"
            ></textarea>
            <div class="character-counter" id="charCounter">0/500</div>
            <div class="smart-suggestions" id="suggestions"></div>
          </div>
          <div class="form-hint">Be specific about your reason to help with approval process</div>
        </div>

        <div class="form-group">
          <label class="form-label">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a2 2 0 000-2.828z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Supporting Document
          </label>
          <div class="file-upload-area" id="fileUploadArea">
            <input type="file" name="attachment" id="attachment" class="file-input" accept=".pdf,.jpg,.png,.jpeg,.doc,.docx">
            <div class="upload-content">
              <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
              </svg>
              <div class="upload-text">Drop your file here or click to browse</div>
              <div class="upload-hint">PDF, Images, or Documents (Max 10MB)</div>
            </div>
          </div>
        </div>

        <button type="submit" class="submit-btn" id="submitBtn">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
          </svg>
          <span>Submit Leave Application</span>
        </button>
      </form>
    </div>
  </div>

  <script>
    // Modern features implementation
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const daysCounter = document.getElementById('daysCounter');
    const reasonTextarea = document.getElementById('reason');
    const charCounter = document.getElementById('charCounter');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('attachment');
    const submitBtn = document.getElementById('submitBtn');
    const progressBar = document.getElementById('progressBar');
    
    // Floating animation elements
    function createFloatingElements() {
      const container = document.getElementById('floatingElements');
      for (let i = 0; i < 20; i++) {
        const element = document.createElement('div');
        element.className = 'floating-element';
        element.style.left = Math.random() * 100 + '%';
        element.style.animationDelay = Math.random() * 15 + 's';
        element.style.animationDuration = (15 + Math.random() * 10) + 's';
        container.appendChild(element);
      }
    }
    createFloatingElements();

    // Smart date calculation with weekends and holidays
    function calculateWorkingDays() {
      if (startDate.value && endDate.value) {
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        
        if (end >= start) {
          let days = 0;
          const current = new Date(start);
          
          while (current <= end) {
            const dayOfWeek = current.getDay();
            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
              days++;
            }
            current.setDate(current.getDate() + 1);
          }
          
          daysCounter.textContent = days + (days === 1 ? ' day' : ' days');
          daysCounter.style.background = 'linear-gradient(135deg, #10b981, #06b6d4)';
          daysCounter.style.webkitBackgroundClip = 'text';
          daysCounter.style.webkitTextFillColor = 'transparent';
        } else {
          daysCounter.textContent = 'Invalid range';
          daysCounter.style.background = '#ef4444';
          daysCounter.style.webkitBackgroundClip = 'text';
          daysCounter.style.webkitTextFillColor = 'transparent';
        }
      } else {
        daysCounter.textContent = 'Select dates';
        daysCounter.style.background = 'linear-gradient(135deg, #6366f1, #8b5cf6)';
        daysCounter.style.webkitBackgroundClip = 'text';
        daysCounter.style.webkitTextFillColor = 'transparent';
      }
    }

    startDate.addEventListener('change', calculateWorkingDays);
    endDate.addEventListener('change', calculateWorkingDays);

    // Character counter with smart feedback
    reasonTextarea.addEventListener('input', function() {
      const length = this.value.length;
      const maxLength = 500;
      charCounter.textContent = `${length}/${maxLength}`;
      
      if (length > maxLength * 0.8) {
        charCounter.style.color = '#f59e0b';
      } else if (length > maxLength * 0.9) {
        charCounter.style.color = '#ef4444';
      } else {
        charCounter.style.color = '#64748b';
      }
    });

    // Smart suggestions for common leave reasons
    const commonReasons = [
      "Personal health appointment",
      "Family emergency",
      "Medical treatment",
      "Vacation with family",
      "Wedding ceremony",
      "Educational training",
      "Conference attendance",
      "Personal matters",
      "Mental health day",
      "Childcare responsibilities"
    ];

    reasonTextarea.addEventListener('input', function() {
      const value = this.value.toLowerCase();
      const suggestions = document.getElementById('suggestions');
      
      if (value.length > 2) {
        const matches = commonReasons.filter(reason => 
          reason.toLowerCase().includes(value) && reason.toLowerCase() !== value
        );
        
        if (matches.length > 0) {
          suggestions.innerHTML = matches.map(reason => 
            `<div class="suggestion-item" onclick="selectSuggestion('${reason}')">${reason}</div>`
          ).join('');
          suggestions.style.display = 'block';
        } else {
          suggestions.style.display = 'none';
        }
      } else {
        suggestions.style.display = 'none';
      }
    });

    function selectSuggestion(reason) {
      reasonTextarea.value = reason;
      document.getElementById('suggestions').style.display = 'none';
      reasonTextarea.focus();
    }

    // Advanced file upload with drag & drop
    fileUploadArea.addEventListener('dragover', function(e) {
      e.preventDefault();
      this.classList.add('dragover');
    });

    fileUploadArea.addEventListener('dragleave', function(e) {
      e.preventDefault();
      this.classList.remove('dragover');
    });

    fileUploadArea.addEventListener('drop', function(e) {
      e.preventDefault();
      this.classList.remove('dragover');
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        fileInput.files = files;
        updateFileDisplay(files[0]);
      }
    });

    fileInput.addEventListener('change', function() {
      if (this.files.length > 0) {
        updateFileDisplay(this.files[0]);
      }
    });

    function updateFileDisplay(file) {
      const uploadContent = fileUploadArea.querySelector('.upload-content');
      const fileSize = (file.size / 1024 / 1024).toFixed(2);
      
      uploadContent.innerHTML = `
        <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <div class="upload-text">📎 ${file.name}</div>
        <div class="upload-hint">${fileSize} MB • Click to change</div>
      `;
      
      fileUploadArea.style.borderColor = '#10b981';
      fileUploadArea.style.background = 'rgba(16, 185, 129, 0.1)';
    }

    // Form validation with visual feedback
    function validateForm() {
      const form = document.querySelector('form');
      const inputs = form.querySelectorAll('input[required], select[required]');
      let isValid = true;
      
      inputs.forEach(input => {
        if (!input.value.trim()) {
          input.style.borderColor = '#ef4444';
          input.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
          isValid = false;
        } else {
          input.style.borderColor = '#10b981';
          input.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.1)';
        }
      });
      
      return isValid;
    }

    // Progress bar animation
    let progress = 0;
    function updateProgress() {
      const form = document.querySelector('form');
      const inputs = form.querySelectorAll('input, select, textarea');
      const filled = Array.from(inputs).filter(input => input.value.trim() !== '').length;
      progress = (filled / inputs.length) * 100;
      progressBar.style.width = progress + '%';
    }

    document.querySelectorAll('input, select, textarea').forEach(element => {
      element.addEventListener('input', updateProgress);
      element.addEventListener('change', updateProgress);
    });

    // Animated submit button
    document.querySelector('form').addEventListener('submit', function(e) {
      if (!validateForm()) {
        e.preventDefault();
        
        // Shake animation for invalid form
        submitBtn.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
          submitBtn.style.animation = '';
        }, 500);
        
        return false;
      }
      
      // Loading animation - don't prevent default, let form submit
      submitBtn.innerHTML = `
        <svg class="icon animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-opacity="0.25"/>
          <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" stroke-opacity="1"/>
        </svg>
        <span>Submitting...</span>
      `;
      submitBtn.disabled = true;
      
      // Allow form to submit naturally
      return true;
    });

    // Set minimum dates
    const today = new Date().toISOString().split('T')[0];
    startDate.setAttribute('min', today);
    endDate.setAttribute('min', today);

    startDate.addEventListener('change', function() {
      endDate.setAttribute('min', this.value);
      if (endDate.value && endDate.value < this.value) {
        endDate.value = '';
      }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
      // Ctrl/Cmd + Enter to submit
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        submitBtn.click();
      }
      
      // Escape to close suggestions
      if (e.key === 'Escape') {
        document.getElementById('suggestions').style.display = 'none';
      }
    });

    // Auto-save to prevent data loss
    const formData = {};
    function autoSave() {
      const form = document.querySelector('form');
      const formElements = form.querySelectorAll('input, select, textarea');
      
      formElements.forEach(element => {
        if (element.name && element.type !== 'file') {
          formData[element.name] = element.value;
          localStorage.setItem('leave_form_' + element.name, element.value);
        }
      });
    }

    function loadSavedData() {
      const form = document.querySelector('form');
      const formElements = form.querySelectorAll('input, select, textarea');
      
      formElements.forEach(element => {
        if (element.name && element.type !== 'file') {
          const saved = localStorage.getItem('leave_form_' + element.name);
          if (saved && !element.value) {
            element.value = saved;
          }
        }
      });
    }

    // Load saved data on page load
    loadSavedData();
    updateProgress();
    calculateWorkingDays();

    // Auto-save every 30 seconds
    setInterval(autoSave, 30000);

    // Clear saved data on successful submit
    document.querySelector('form').addEventListener('submit', function() {
      setTimeout(() => {
        Object.keys(formData).forEach(key => {
          localStorage.removeItem('leave_form_' + key);
        });
      }, 1000);
    });

    // Add shake animation CSS
    const shakeCSS = `
      @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
      }
      
      .animate-spin {
        animation: spin 1s linear infinite;
      }
      
      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
    `;
    
    const style = document.createElement('style');
    style.textContent = shakeCSS;
    document.head.appendChild(style);

    // Theme-aware time-based greeting
    function updateGreeting() {
      const hour = new Date().getHours();
      const titleElement = document.querySelector('.page-title');
      
      if (hour < 12) {
        titleElement.textContent = '🌅 Morning Leave Request';
      } else if (hour < 17) {
        titleElement.textContent = '☀️ Afternoon Leave Request';
      } else {
        titleElement.textContent = '🌙 Evening Leave Request';
      }
    }

    // Smart form completion suggestions
    function showCompletionHints() {
      const form = document.querySelector('form');
      const incomplete = [];
      
      if (!document.getElementById('type_id').value) incomplete.push('Leave Type');
      if (!startDate.value) incomplete.push('Start Date');
      if (!endDate.value) incomplete.push('End Date');
      if (!reasonTextarea.value.trim()) incomplete.push('Reason');
      
      if (incomplete.length > 0 && incomplete.length < 4) {
        console.log(`💡 Complete these fields: ${incomplete.join(', ')}`);
      }
    }

    // Initialize features
    updateGreeting();
    setInterval(showCompletionHints, 10000); // Check every 10 seconds

    // Advanced accessibility features
    document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(element => {
      element.addEventListener('focus', function() {
        this.setAttribute('aria-expanded', 'true');
      });
      
      element.addEventListener('blur', function() {
        this.setAttribute('aria-expanded', 'false');
      });
    });

    // Real-time form validation
    document.querySelectorAll('input[required], select[required]').forEach(element => {
      element.addEventListener('blur', function() {
        if (this.value.trim()) {
          this.style.borderColor = '#10b981';
          this.setAttribute('aria-invalid', 'false');
        } else {
          this.style.borderColor = '#ef4444';
          this.setAttribute('aria-invalid', 'true');
        }
      });
    });
  </script>
</body>
</html>