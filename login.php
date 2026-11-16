<?php
require 'config.php';
require 'helpers.php';

if (currentUser()) { header('Location: index.php'); exit; }

$error = ''; $username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Production note: add a CSRF token and rate-limit repeated attempts.
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);   // fresh session ID defeats session fixation
        $_SESSION['user_id']   = (int)$u['user_id'];
        $_SESSION['username']  = $u['username'];
        $_SESSION['full_name'] = $u['full_name'];
        $_SESSION['role']      = $u['role'];
        $target = (string)($_SESSION['redirect_to'] ?? 'index.php');
        unset($_SESSION['redirect_to']);
        if (!str_starts_with($target, '/') || str_starts_with($target, '//')) $target = 'index.php'; // local paths only
        setFlash('ok', 'Welcome back, ' . $u['full_name'] . '!');
        header('Location: ' . $target); exit;
    }
    $error = 'Invalid username or password.';   // same message either way: no account enumeration
}
?>
<!doctype html><html lang="en" data-theme="night"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0A171D">
<?= themeScript() ?>
<link rel="stylesheet" href="style.css"><title>Sign in · ParkEase</title></head>
<body>
<div class="theme-float no-print"><?= themeToggle() ?></div>
<main class="auth-wrap">
<div class="card auth-card">
  <div class="auth-head"><div class="logo">🅿️</div><h1>ParkEase</h1><p>Sign in to manage the parking lot</p></div>
  <?= takeFlash() ?>
  <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <label>Username <input name="username" maxlength="30" required autofocus value="<?= e($username) ?>"></label>
    <label>Password
      <span class="pw-row">
        <input type="password" name="password" id="pw" required>
        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">👁</button>
      </span>
    </label>
    <button class="btn auth-btn">Sign in</button>
  </form>
  <div class="demo-box">
    <strong>Demo accounts</strong><br>
    Admin: <code>admin</code> / <code>admin123</code> — full control<br>
    Staff: <code>staff</code> / <code>user123</code> — parking operations<br>
    <em>Remove this box in a real deployment.</em>
  </div>
</div>
</main>
<script>
const pw = document.getElementById('pw'), t = document.getElementById('pwToggle');
t.addEventListener('click', () => {
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    t.textContent = show ? '🙈' : '👁';
});
</script>
<?= themeToggleJs() ?>
</body></html>