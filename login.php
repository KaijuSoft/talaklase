<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pdo = authBootstrap();

// Already logged in → home
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// No-cache so browser-back after logout forces re-auth
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$errors  = [];
$prefill = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $prefill  = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

        if ($username === '') {
            $errors[] = 'Username is required.';
        }
        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
              'SELECT id, name, username, email, password_hash, role, inst_id, is_active
               FROM users
               WHERE username = ? OR email = ?
               LIMIT 1'
            );
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'No account found with that username.';
            } elseif (!(bool)$user['is_active']) {
                $errors[] = auth_archived_message();
            } elseif (!password_verify($password, $user['password_hash'])) {
                $errors[] = 'Incorrect password. Please try again.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['auth_user'] = [
                    'id'       => $user['id'],
                    'name'     => $user['name'],
                  'email'    => $user['email'],
                    'role'     => $user['role'],
                    'inst_id'  => $user['inst_id'],
                    'is_active'=> $user['is_active'],
                ];
                header('Location: index.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TalaKlase - Sign in</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:        #0F1F3D;
      --navy-mid:    #162B52;
      --teal:        #1D9E75;
      --teal-dark:   #157A5A;
      --teal-pale:   rgba(29,158,117,0.12);
      --off-white:   #F8F7F4;
      --text-main:   #1A1A2E;
      --text-muted:  #5C6070;
      --border:      rgba(0,0,0,0.10);
      --err-bg:      #FFF1F1;
      --err-border:  #FECACA;
      --err-text:    #991B1B;
    }

    html, body {
      min-height: 100%;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      -webkit-font-smoothing: antialiased;
      color: var(--text-main);
    }

    body {
      background-color: var(--navy);
      background-image: radial-gradient(rgba(255,255,255,0.045) 1px, transparent 1px);
      background-size: 22px 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 2rem 1rem;
    }

    /* ── Card ─────────────────────────────────────────────── */
    .card {
      background: #ffffff;
      border-radius: 16px;
      width: 100%;
      max-width: 420px;
      padding: 0;
      overflow: hidden;
    }

    /* Card top accent bar */
    .card-accent {
      height: 4px;
      background: var(--teal);
    }

    .card-body {
      padding: 2.25rem 2.5rem 2.5rem;
    }

    /* ── Brand ────────────────────────────────────────────── */
    .brand {
      text-align: center;
      margin-bottom: 1.75rem;
    }

    .login-logo-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 52px; height: 52px;
      background: linear-gradient(135deg, #0dc8a8, #3b82f6);
      border-radius: 10px;
      margin-bottom: 14px;
      color: #fff;
      font-size: 1.55rem;
      box-shadow: 0 6px 18px rgba(13,200,168,0.25);
    }

    .wordmark {
      font-size: 26px;
      font-weight: 700;
      letter-spacing: -0.03em;
      line-height: 1;
      margin-bottom: 6px;
    }
    .wordmark .tala  { color: var(--teal); }
    .wordmark .klase { color: var(--navy); }

    .dept-label {
      font-size: 12px;
      color: var(--text-muted);
      letter-spacing: 0.04em;
    }

    /* ── Section divider ──────────────────────────────────── */
    .divider {
      border: none;
      border-top: 1px solid var(--border);
      margin: 0 0 1.5rem;
    }

    .form-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    .form-sub {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 1.5rem;
    }

    /* ── Error alert ──────────────────────────────────────── */
    .alert {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      background: var(--err-bg);
      border: 1px solid var(--err-border);
      border-radius: 8px;
      padding: 11px 13px;
      margin-bottom: 1.25rem;
      font-size: 13px;
      color: var(--err-text);
      line-height: 1.5;
    }
    .alert svg { flex-shrink: 0; margin-top: 1px; }

    /* ── Fields ───────────────────────────────────────────── */
    .field { margin-bottom: 1rem; }

    label {
      display: block;
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 5px;
    }

    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon {
      position: absolute;
      left: 11px;
      color: var(--text-muted);
      pointer-events: none;
      line-height: 0;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      height: 40px;
      padding: 0 40px 0 36px;
      border: 1px solid rgba(0,0,0,0.15);
      border-radius: 8px;
      font-size: 14px;
      color: var(--text-main);
      background: var(--off-white);
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
    }
    input:focus {
      border-color: var(--teal);
      box-shadow: 0 0 0 3px rgba(29,158,117,0.15);
      background: #fff;
    }
    input.has-error {
      border-color: #F87171;
    }

    .pw-toggle {
      position: absolute;
      right: 10px;
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-muted);
      line-height: 0;
      padding: 4px;
    }
    .pw-toggle:hover { color: var(--navy); }

    /* ── Submit ───────────────────────────────────────────── */
    .btn-primary {
      width: 100%;
      height: 42px;
      background: var(--teal);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      letter-spacing: 0.01em;
      transition: background 0.15s, transform 0.1s;
      margin-top: 0.25rem;
    }
    .btn-primary:hover  { background: var(--teal-dark); }
    .btn-primary:active { transform: scale(0.98); }

    /* ── Footer ───────────────────────────────────────────── */
    .card-footer {
      border-top: 1px solid var(--border);
      padding: 1rem 2.5rem;
      text-align: center;
      font-size: 12px;
      color: var(--text-muted);
    }

    /* ── Role chips ───────────────────────────────────────── */
    .role-row {
      display: flex;
      gap: 6px;
      justify-content: center;
      margin-top: 0.85rem;
    }
    .role-chip {
      font-size: 11px;
      padding: 3px 10px;
      border-radius: 100px;
      border: 1px solid rgba(0,0,0,0.1);
      color: var(--text-muted);
    }

    @media (max-width: 460px) {
      .card-body { padding: 1.75rem 1.5rem 2rem; }
      .card-footer { padding: 0.85rem 1.5rem; }
    }

    @media (prefers-reduced-motion: reduce) {
      * { transition: none !important; }
    }
  </style>
</head>
<body>

<div class="card" role="main">
  <div class="card-accent"></div>
  <div class="card-body">

    <!-- Brand -->
    <div class="brand">
      <div class="login-logo-icon" aria-hidden="true">
        <i class="bi bi-mortarboard-fill"></i>
      </div>
      <div class="wordmark"><span class="tala">Tala</span><span class="klase">Klase</span></div>
      <div class="dept-label">Your Records, Your Way</div>
      
    </div>

    <hr class="divider">

    <h1 class="form-title">Sign in to your account</h1>
    <p class="form-sub">Enter your credentials to continue.</p>

    <?php if (!empty($errors)): ?>
      <div class="alert" role="alert">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
        </svg>
        <div><?= implode('<br>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $errors)) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label for="username">Username</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z"/>
            </svg>
          </span>
          <input
            type="text"
            id="username"
            name="username"
            value="<?= $prefill ?>"
            autocomplete="username"
            autocapitalize="none"
            spellcheck="false"
            placeholder="your.username"
            class="<?= !empty($errors) ? 'has-error' : '' ?>"
            required
            autofocus
          >
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/>
            </svg>
          </span>
          <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            placeholder="••••••••"
            class="<?= !empty($errors) ? 'has-error' : '' ?>"
            required
          >
          <button type="button" class="pw-toggle" aria-label="Show or hide password" onclick="togglePw(this)">
            <svg id="eye-show" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path d="M10 3C5 3 1.73 7.11 1.05 8.45a1 1 0 000 .9C1.73 10.89 5 15 10 15s8.27-4.11 8.95-5.45a1 1 0 000-.9C18.27 7.11 15 3 10 3zm0 10a4 4 0 110-8 4 4 0 010 8zm0-6a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-primary">Sign in</button>
    </form>
  </div>

  <div class="card-footer">
    Forgot your password? Contact your <strong>system admin</strong> to reset it.
  </div>
</div>

<script>
function togglePw(btn) {
  var input = document.getElementById('password');
  var isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
  btn.querySelector('svg').innerHTML = isHidden
    ? '<path d="M2.22 2.22a.75.75 0 011.06 0l14.5 14.5a.75.75 0 01-1.06 1.06l-1.5-1.5A8.93 8.93 0 0110 17C5 17 1.73 12.89 1.05 11.55a1 1 0 010-.9 12.5 12.5 0 012.3-3.22L2.22 3.28a.75.75 0 010-1.06zM10 5a8.93 8.93 0 015.23 1.68l-1.5 1.5A6.46 6.46 0 0010 7c-2.76 0-5.16 1.57-6.95 3.55C3.63 11.27 4.7 12.6 6 13.5l-1.06 1.06A12.6 12.6 0 011.05 11.55a1 1 0 010-.9C1.73 9.11 5 5 10 5z"/>'
    : '<path d="M10 3C5 3 1.73 7.11 1.05 8.45a1 1 0 000 .9C1.73 10.89 5 15 10 15s8.27-4.11 8.95-5.45a1 1 0 000-.9C18.27 7.11 15 3 10 3zm0 10a4 4 0 110-8 4 4 0 010 8zm0-6a2 2 0 100 4 2 2 0 000-4z"/>';
}
</script>
</body>
</html>
