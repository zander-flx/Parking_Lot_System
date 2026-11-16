<?php
require 'config.php'; require 'helpers.php';
$free = $pdo->query('SELECT s.space_id, s.space_code, t.type_name, t.hourly_rate
                     FROM spaces s JOIN space_types t ON t.type_id = s.type_id
                     LEFT JOIN sessions ps ON ps.space_id = s.space_id AND ps.time_out IS NULL
                     WHERE ps.session_id IS NULL ORDER BY s.space_code')->fetchAll();
$error = ''; $name = ''; $plate = ''; $phone = '';
$spaceId = positiveId($_GET['space_id'] ?? null);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim((string)($_POST['full_name'] ?? ''));
    $plate = strtoupper(trim((string)($_POST['plate_number'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $spaceId = positiveId($_POST['space_id'] ?? null);
    $validSpace = in_array($spaceId, array_map(fn($s) => (int)$s['space_id'], $free), true);
    if (!preg_match('/^[A-Z0-9]{6}$/', $plate)) {
        $error = 'Plate number must be exactly 6 characters — uppercase letters and numbers only, no spaces or special characters (e.g. ABC123).';
    } elseif ($name === '' || strlen($name) > 120 || strlen($phone) > 20 || !$validSpace) {
        $error = 'Please fill in the driver name and an available space.';
    } else {
        try {
            $pdo->beginTransaction();
            // Same plate already on the lot?
            $chk = $pdo->prepare('SELECT sp.space_code FROM sessions ps
                                  JOIN drivers d ON d.driver_id = ps.driver_id
                                  JOIN spaces sp ON sp.space_id = ps.space_id
                                  WHERE d.plate_number = ? AND ps.time_out IS NULL');
            $chk->execute([$plate]);
            if ($at = $chk->fetch()) throw new RuntimeException("Plate $plate is already parked at {$at['space_code']}.");
            // Lock the space row so two clerks can't grab it at the same instant.
            $lock = $pdo->prepare('SELECT s.space_code, t.hourly_rate FROM spaces s
                                   JOIN space_types t ON t.type_id = s.type_id WHERE s.space_id = ? FOR UPDATE');
            $lock->execute([$spaceId]); $space = $lock->fetch();
            $busy = $pdo->prepare('SELECT session_id FROM sessions WHERE space_id = ? AND time_out IS NULL FOR UPDATE');
            $busy->execute([$spaceId]);
            if (!$space || $busy->fetch()) throw new RuntimeException('That space was just taken. Please pick another one.');
            // Find-or-create the driver by plate (returning customers reuse their record).
            $find = $pdo->prepare('SELECT driver_id FROM drivers WHERE plate_number = ?');
            $find->execute([$plate]);
            if ($driverId = $find->fetchColumn()) {
                $pdo->prepare('UPDATE drivers SET full_name = ?, phone = ? WHERE driver_id = ?')->execute([$name, $phone, (int)$driverId]);
            } else {
                $pdo->prepare('INSERT INTO drivers (full_name, plate_number, phone) VALUES (?,?,?)')->execute([$name, $plate, $phone]);
                $driverId = (int)$pdo->lastInsertId();
            }
            $pdo->prepare('INSERT INTO sessions (space_id, driver_id, time_in, rate) VALUES (?,?,NOW(),?)')
                ->execute([$spaceId, (int)$driverId, $space['hourly_rate']]);
            $pdo->commit();
            setFlash('ok', "$plate parked at {$space['space_code']}. Timer started.");
            header('Location: index.php'); exit;
        } catch (RuntimeException|PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $ex instanceof RuntimeException ? $ex->getMessage() : 'Could not park the vehicle right now. Please try again.';
        }
    }
}
ob_start(); ?>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<?php if (!$free): ?>
<p class="alert alert-error">All spaces are occupied right now. Checkout a vehicle to free one up.</p>
<div class="btn-row"><a class="btn" href="billing.php?f=active">See parked vehicles</a><a class="btn ghost" href="create.php">Add another space</a></div>
<?php else: ?>
<form method="post" class="card form">
<label>Parking space <select name="space_id" required>
<option value="">Choose an available space…</option>
<?php foreach ($free as $s): ?>
<option value="<?= (int)$s['space_id'] ?>" data-rate="<?= e((string)$s['hourly_rate']) ?>" <?= $spaceId === (int)$s['space_id'] ? 'selected' : '' ?>><?= e($s['space_code']) ?> — <?= e($s['type_name']) ?> (<?= money($s['hourly_rate']) ?>/hr)</option>
<?php endforeach; ?>
</select><small class="hint" id="rateHint"></small></label>
<label>Driver name <input name="full_name" maxlength="120" required value="<?= e($name) ?>" placeholder="Juan Dela Cruz"></label>
<label>Plate number <input name="plate_number" class="upper" maxlength="20" required value="<?= e($plate) ?>" placeholder="ABC 1234"></label>
<label>Phone (optional) <input name="phone" maxlength="20" value="<?= e($phone) ?>" placeholder="0917 000 0000"></label>
<small class="hint">Only available spaces are listed. A returning plate reuses its driver record.</small>
<div class="btn-row"><button class="btn">Park &amp; start timer</button><a class="btn ghost" href="index.php">Cancel</a></div>
</form>
<script>
const sel = document.querySelector('select[name=space_id]'), hint = document.getElementById('rateHint'),
      cur = <?= json_encode(CURRENCY) ?>;
function showRate() {
    const o = sel.selectedOptions[0];
    hint.textContent = o && o.dataset.rate
        ? 'Rate: ' + cur + parseFloat(o.dataset.rate).toFixed(2) + ' per hour started (minimum <?= MIN_BILLED_HOURS ?> hr)' : '';
}
sel.addEventListener('change', showRate); showRate();
</script>
<?php endif; ?>
<?php page('Park a Vehicle', ob_get_clean());