<?php
require 'config.php';
require 'helpers.php';
requireAdmin();
$types = $pdo->query('SELECT type_id, type_name, hourly_rate FROM space_types ORDER BY type_id')->fetchAll();
$error = ''; $code = ''; $type = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim((string)($_POST['space_code'] ?? '')));
    $type = positiveId($_POST['type_id'] ?? null);
    $validType = in_array($type, array_map(fn($t) => (int)$t['type_id'], $types), true);
    if ($code === '' || strlen($code) > 10 || !$validType) {
        $error = 'Give a space code (max 10 characters) and choose a type.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO spaces (space_code, type_id) VALUES (?,?)');
            $stmt->execute([$code, $type]);
            setFlash('ok', "Space $code added.");
            header('Location: index.php'); exit;
        } catch (PDOException $ex) { $error = "Unable to save: space code \"$code\" may already exist."; }
    }
}
ob_start(); ?>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="card form">
<label>Space code <input name="space_code" class="upper" maxlength="10" required placeholder="e.g. A-05" value="<?= e($code) ?>"></label>
<label>Type &amp; rate <select name="type_id" required>
<option value="">Choose a type…</option>
<?php foreach ($types as $t): ?>
<option value="<?= (int)$t['type_id'] ?>" <?= $type === (int)$t['type_id'] ? 'selected' : '' ?>><?= e($t['type_name']) ?> — <?= money($t['hourly_rate']) ?>/hr</option>
<?php endforeach; ?>
</select><small class="hint">The rate is locked into each ticket at check-in, so future price changes never affect open tickets.</small></label>
<div class="btn-row"><button class="btn">Save space</button><a class="btn ghost" href="index.php">Cancel</a></div>
</form>
<?php page('Add Parking Space', ob_get_clean());