<?php
require 'config.php'; require 'helpers.php';
requireAdmin();
$me = currentUser();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A CSRF token is required for an internet-facing production application.
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'create') {
        $username = trim((string)($_POST['username'] ?? ''));
        $name = trim((string)($_POST['full_name'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username) || $name === '' || strlen($name) > 120
            || strlen($pw) < 6 || !in_array($role, ['admin','user'], true)) {
            $error = 'Username: 3–30 letters/digits/._ — name required — password min 6 characters — pick a role.';
        } else {
            try {
                $pdo->prepare('INSERT INTO users (username, full_name, password_hash, role) VALUES (?,?,?,?)')
                    ->execute([$username, $name, password_hash($pw, PASSWORD_DEFAULT), $role]);
                setFlash('ok', "Account \"$username\" created.");
                header('Location: users.php'); exit;
            } catch (PDOException $ex) { $error = "Username \"$username\" is already taken."; }
        }
    } elseif ($action === 'delete') {
        $uid = positiveId($_POST['user_id'] ?? null);
        if ($uid === $me['id']) {
            $error = 'You cannot delete your own signed-in account.';
        } else {
            $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$uid]);
            setFlash('ok', 'Account deleted.');
            header('Location: users.php'); exit;
        }
    } elseif ($action === 'reset') {
        $uid = positiveId($_POST['user_id'] ?? null);
        $pw = (string)($_POST['new_password'] ?? '');
        if ($uid === 0 || strlen($pw) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                ->execute([password_hash($pw, PASSWORD_DEFAULT), $uid]);
            setFlash('ok', 'Password updated.');
            header('Location: users.php'); exit;
        }
    }
}
$users = $pdo->query('SELECT user_id, username, full_name, role, created_at FROM users ORDER BY role, username')->fetchAll();
ob_start(); ?>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<div class="table-wrap"><table>
<thead><tr><th>Username</th><th>Name</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($users as $u): ?>
<tr>
  <td class="mono strong"><?= e($u['username']) ?> <?= (int)$u['user_id'] === $me['id'] ? '<small class="muted">(you)</small>' : '' ?></td>
  <td><?= e($u['full_name']) ?></td>
  <td><?= $u['role'] === 'admin' ? '<span class="badge b-info">Admin</span>' : '<span class="badge b-ok">Staff</span>' ?></td>
  <td><?= fmtDt($u['created_at'], 'M j, Y') ?></td>
  <td class="actions">
    <details class="reset"><summary>Reset password</summary>
      <form method="post" class="reset-form">
        <input type="hidden" name="action" value="reset"><input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
        <input type="password" name="new_password" minlength="6" required placeholder="New password (min 6)">
        <button class="btn btn-xs">Save</button>
      </form>
    </details>
    <?php if ((int)$u['user_id'] !== $me['id']): ?>
    <form method="post" onsubmit="return confirm('Delete account <?= e($u['username']) ?>?')">
      <input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
      <button class="btn btn-xs ghost danger-text">Delete</button>
    </form>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<h2>Add account</h2>
<form method="post" class="card form">
<input type="hidden" name="action" value="create">
<label>Username <input name="username" maxlength="30" required placeholder="e.g. attendant2"></label>
<label>Full name <input name="full_name" maxlength="120" required placeholder="Ana Reyes"></label>
<label>Password <input type="password" name="password" minlength="6" required placeholder="At least 6 characters"></label>
<label>Role <select name="role">
<option value="user">Staff — parking operations</option>
<option value="admin">Admin — full control</option>
</select></label>
<div class="btn-row"><button class="btn">Create account</button></div>
</form>
<?php page('User Accounts', ob_get_clean());