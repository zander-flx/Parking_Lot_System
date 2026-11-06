<?php
require 'config.php'; require 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Enter username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, username, password_hash, role
                               FROM users
                               WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && hash_equals($user['password_hash'], hash('sha256', $password))) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header('Location: index.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}

ob_start();
?>

<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

<form method="post">
<label>Username
<input name="username" required>
</label>

<label>Password
<input type="password" name="password" required>
</label>

<button>Login</button>
</form>

<p>
Default admin: <strong>admin / admin123</strong><br>
Default user: <strong>user / user123</strong>
</p>

<?php page('Login', ob_get_clean());?>