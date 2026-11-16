<?php
require 'config.php'; require 'helpers.php';
requireAdmin();
$id = positiveId($_GET['id'] ?? null);
$stmt = $pdo->prepare('SELECT * FROM spaces WHERE space_id = ?');
$stmt->execute([$id]); $space = $stmt->fetch();
if (!$space) { http_response_code(404); exit('Parking space not found.'); }
$types = $pdo->query('SELECT type_id, type_name, hourly_rate FROM space_types ORDER BY type_id')->fetchAll();
$stmt = $pdo->prepare('SELECT ps.rate, d.plate_number FROM sessions ps JOIN drivers d ON d.driver_id = ps.driver_id
                       WHERE ps.space_id = ? AND ps.time_out IS NULL');
$stmt->execute([$id]); $occupied = $stmt->fetch();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim((string)($_POST['space_code'] ?? '')));
    $type = positiveId($_POST['type_id'] ?? null);
    $validType = in_array($type, array_map(fn($t) => (int)$t['type_id'], $types), true);
    if ($code === '' || strlen($code) > 10 || !$validType) {
        $error = 'Please correct the fields.';
    } else {
        try {
            $pdo->prepare('UPDATE spaces SET space_code = ?, type_id = ? WHERE space_id = ?')->execute([$code, $type, $id]);
            setFlash('ok', "Space $code updated.");
            header('Location: index.php'); exit;
        } catch (PDOException $ex) { $error = 'Could not save changes; the space code may already exist.'; }
    }
    $space = array_merge($space, ['space_code' => $code, 'type_id' => $type]);
}
ob_start(); ?>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<?php if ($occupied): ?><p class="alert alert-info">🚗 <?= e($occupied['plate_number']) ?> is parked here. Its open ticket keeps the original rate (<?= money($occupied['rate']) ?>/hr) even if you change this space's type.</p><?php endif; ?>
<form method="post" class="card form">
<label>Space code <input name="space_code" class="upper" maxlength="10" required value="<?= e($space['space_code']) ?>"></label>
<label>Type &amp; rate <select name="type_id" required>
<?php foreach ($types as $t): ?>
<option value="<?= (int)$t['type_id'] ?>" <?= (int)$space['type_id'] === (int)$t['type_id'] ? 'selected' : '' ?>><?= e($t['type_name']) ?> — <?= money($t['hourly_rate']) ?>/hr</option>
<?php endforeach; ?>
</select></label>
<div class="btn-row"><button class="btn">Update space</button><a class="btn ghost" href="index.php">Cancel</a></div>
</form>
<?php page('Edit Parking Space', ob_get_clean());