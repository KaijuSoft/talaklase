<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
$pdo = authBootstrap();
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
$errors = [];
$prefill = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { $errors[] = 'Invalid session. Please refresh and try again.'; }
    else {
        $username = trim($_POST['username'] ?? ''); $password = $_POST['password'] ?? '';
        $prefill = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        if ($username === '') $errors[] = 'Username is required.';
        if ($password === '') $errors[] = 'Password is required.';
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id, name, username, email, password_hash, role, inst_id, is_active FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $username]); $user = $stmt->fetch();
            if (!$user) $errors[] = 'No account found with that username.';
            elseif (!(bool)$user['is_active']) $errors[] = auth_archived_message();
            elseif (!password_verify($password, $user['password_hash'])) $errors[] = 'Incorrect password. Please try again.';
            else {
                session_regenerate_id(true); $_SESSION['user_id'] = $user['id'];
                $_SESSION['auth_user'] = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role'],'inst_id'=>$user['inst_id'],'is_active'=>$user['is_active']];
                header('Location: index.php'); exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>TalaKlase — Sign in</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<style>
*{box-sizing:border-box}html,body{margin:0;min-height:100%;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}body{min-height:100vh;background:#0b111b;color:#fff}.login-shell{min-height:100vh;display:grid;grid-template-columns:44% 56%}.login-panel{display:flex;align-items:center;justify-content:center;padding:48px 7%;background:linear-gradient(145deg,#0b111b,#101823)}.login-inner{width:100%;max-width:500px}.brand{text-align:center;margin-bottom:34px}.brand-mark{width:68px;height:68px;margin:0 auto 18px;border-radius:18px;background:linear-gradient(135deg,#1677ff,#0b4ecb);display:grid;place-items:center;box-shadow:0 14px 34px rgba(15,99,230,.3);font-size:32px}.wordmark{font-size:38px;font-weight:800;letter-spacing:-1.8px}.wordmark .tala{color:#fff}.wordmark .klase{color:#237cff}.brand-sub{margin-top:8px;color:#a7b1c2;letter-spacing:3px;text-transform:uppercase;font-size:11px;font-weight:600}.welcome{text-align:center;margin-bottom:28px}.welcome h1{margin:0 0 8px;font-size:25px}.welcome p{margin:0;color:#9da8b8;font-size:15px}.field{margin-bottom:17px}.field-wrap{position:relative}.field-wrap i{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:#929dad;font-size:20px}.field input{width:100%;height:58px;border:1px solid #354150;border-radius:10px;background:#111923;color:#fff;padding:0 58px 0 50px;font-size:15px;outline:none;transition:.18s}.field input::placeholder{color:#7e8998}.field input:focus{border-color:#247cff;box-shadow:0 0 0 3px rgba(36,124,255,.15)}.pw-toggle{position:absolute;right:8px;top:50%;width:42px;height:42px;margin:0;padding:0;transform:translateY(-50%);display:grid;place-items:center;border:0;border-radius:8px;background:transparent;color:#8995a6;cursor:pointer;font-size:20px;line-height:1;z-index:2}.pw-toggle:hover{color:#fff;background:rgba(255,255,255,.06)}.pw-toggle:focus-visible{outline:2px solid #247cff;outline-offset:1px}.login-row{display:flex;align-items:center;justify-content:flex-end;margin:4px 0 22px}.forgot{color:#2990ff;text-decoration:none;font-size:14px;font-weight:600}.forgot:hover{text-decoration:underline}.btn-login{width:100%;height:58px;border:0;border-radius:10px;background:linear-gradient(90deg,#1459d4,#247cff);color:#fff;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 12px 25px rgba(22,101,224,.22);transition:.18s}.btn-login:hover{filter:brightness(1.08);transform:translateY(-1px)}.btn-login:active{transform:translateY(0)}.alert{display:flex;gap:10px;background:#32191c;border:1px solid #633035;color:#ffb7bc;border-radius:10px;padding:12px 14px;margin-bottom:18px;font-size:13px;line-height:1.45}.login-footer{text-align:center;margin-top:38px;color:#778293;font-size:12px}.info-panel{position:relative;overflow:hidden;padding:8vh 9%;display:flex;align-items:center;background:linear-gradient(135deg,#1559c9 0%,#0c4ab7 55%,#0a3d99 100%)}.info-panel:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 85% 18%,rgba(255,255,255,.12),transparent 28%),linear-gradient(115deg,transparent 45%,rgba(255,255,255,.04) 45%,transparent 70%);opacity:.9}.info-content{position:relative;z-index:1;max-width:720px}.eyebrow{font-size:17px;color:#dbe8ff;margin-bottom:8px}.info-title{font-size:clamp(48px,5vw,76px);line-height:.98;letter-spacing:-3px;margin:0 0 14px;font-weight:800}.info-sub{font-size:25px;font-weight:650;margin:0 0 28px}.accent-line{width:52px;height:4px;background:#72b1ff;border-radius:4px;margin-bottom:26px}.info-copy{max-width:650px;font-size:17px;line-height:1.65;color:#e4efff;margin-bottom:42px}.features{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.feature{background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.12);border-radius:15px;padding:22px 20px;backdrop-filter:blur(4px)}.feature i{font-size:27px;color:#fff}.feature h3{font-size:15px;margin:14px 0 7px}.feature p{margin:0;color:#d8e7ff;font-size:13px;line-height:1.5}.alert+form{margin-top:0}@media(max-width:900px){.login-shell{grid-template-columns:1fr}.info-panel{display:none}.login-panel{min-height:100vh;padding:35px 24px}.login-inner{max-width:500px}.brand{margin-bottom:28px}}@media(max-width:480px){.login-panel{padding:28px 18px}.wordmark{font-size:32px}.brand-mark{width:60px;height:60px}.field input,.btn-login{height:54px}.login-footer{margin-top:28px}}
@media(prefers-reduced-motion:reduce){*,*:before,*:after{transition:none!important}}
</style>
</head>
<body>
<main class="login-shell">
<section class="login-panel"><div class="login-inner">
<div class="brand"><div class="brand-mark" aria-hidden="true"><i class="bi bi-mortarboard-fill"></i></div>
<div class="welcome"><h1>Welcome back!</h1><p>Please sign in to continue to your account.</p></div>
<?php if (!empty($errors)): ?><div class="alert" role="alert"><i class="bi bi-exclamation-circle-fill"></i><div><?= implode('<br>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $errors)) ?></div></div><?php endif; ?>
<form method="POST" action="login.php" novalidate><?= csrf_field() ?>
<div class="field"><div class="field-wrap"><i class="bi bi-person-fill"></i><input type="text" id="username" name="username" value="<?= $prefill ?>" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="Username" required autofocus></div></div>
<div class="field"><div class="field-wrap"><i class="bi bi-lock-fill"></i><input type="password" id="password" name="password" autocomplete="current-password" placeholder="Password" required><button type="button" class="pw-toggle" aria-label="Show password" onclick="togglePw(this)"><i class="bi bi-eye-fill"></i></button></div></div>
<div class="login-row"><span class="forgot" title="Contact your system administrator to reset your password">Forgot password?</span></div>
<button type="submit" class="btn-login">Log in <i class="bi bi-arrow-right"></i></button>
</form>
<div class="login-footer">TalaKlase School Information System</div>
</div></section>
<section class="info-panel"><div class="info-content"><div class="eyebrow">Welcome to</div><h2 class="info-title">TalaKlase</h2><p class="info-sub">School Information System</p><div class="accent-line"></div><p class="info-copy">Empowering educators, students, and administrators with a seamless and efficient platform for academic excellence.</p><div class="features"><article class="feature"><i class="bi bi-people-fill"></i><h3>For Educators</h3><p>Manage classes, grades, and attendance with ease.</p></article><article class="feature"><i class="bi bi-mortarboard-fill"></i><h3>For Students</h3><p>Access classes, grades, and important academic information.</p></article><article class="feature"><i class="bi bi-bar-chart-fill"></i><h3>For Administrators</h3><p>Oversee school operations and generate insightful reports.</p></article></div></div></section>
</main>
<script>function togglePw(btn){const input=document.getElementById('password');const icon=btn.querySelector('i');const show=input.type==='password';input.type=show?'text':'password';icon.className=show?'bi bi-eye-slash-fill':'bi bi-eye-fill';btn.setAttribute('aria-label',show?'Hide password':'Show password');}</script>
</body>
</html>
