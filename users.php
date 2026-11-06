<?php
require 'config.php'; require 'helpers.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Admin access required.');
}

$error = '';
$username = '';
$role = 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = (string)($_POST['role'] ?? 'user');

    if (
        $username === '' ||
        strlen($username) > 50 ||
        $password === '' ||
        strlen($password) < 6 ||
        !in_array($role, ['admin', 'user'], true)
    ) {
        $error = 'Username is required, password must be at least 6 characters, and role must be valid.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
            $stmt->execute([
                $username,
                hash('sha256', $password),
                $role
            ]);

            header('Location: users.php?added=1');
            exit;
        } catch (PDOException $ex) {
            $error = 'Unable to add user. Username may already exist.';
        }
    }
}

$rows = $pdo->query('SELECT user_id, username, role
                     FROM users
                     ORDER BY username ASC')->fetchAll();

ob_start();
?>

<?php if (isset($_GET['added'])): ?>
<p>User added.</p>
<?php endif; ?>

<?php if ($error): ?>
<p class="error"><?= e($error) ?></p>
<?php endif; ?>

<form method="post">
<label>Username
<input name="username" maxlength="50" required value="<?= e($username) ?>">
</label>

<label>Password
<input type="password" name="password" minlength="6" required>
</label>

<label>Role
<select name="role">
<option value="user" <?= $role === 'user' ? 'selected' : '' ?>>user</option>
<option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>admin</option>
</select>
</label>

<button>Add user</button>
</form>

<table>
<thead>
<tr>
<th>ID</th>
<th>Username</th>
<th>Role</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= (int)$r['user_id'] ?></td>
<td><?= e($r['username']) ?></td>
<td><?= e($r['role']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p><?= count($rows) ?> user(s) found</p>

<?php page('Users', ob_get_clean());?>