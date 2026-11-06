<?php
require 'config.php'; require 'helpers.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$assignments = $pdo->query('SELECT a.id, a.driver_name, a.vehicle_plate, a.entry_time,
                                   p.name AS lot_name, p.price_per_hour
                            FROM assignments a
                            JOIN parking_lots p ON p.id = a.parking_lot_id
                            ORDER BY a.entry_time DESC')->fetchAll();

if (empty($assignments)) {
    ob_start(); ?>
    <p>No assigned drivers found.</p>
    <?php
    page('Billing', ob_get_clean());
    exit;
}

$assignmentId = positiveId($_GET['assignment_id'] ?? 0);

$valid = in_array(
    $assignmentId,
    array_map(fn($a) => (int)$a['id'], $assignments),
    true
);

if ($assignmentId === 0 || !$valid) {
    $assignmentId = (int)$assignments[0]['id'];
}

$selected = null;

foreach ($assignments as $a) {
    if ((int)$a['id'] === $assignmentId) {
        $selected = $a;
        break;
    }
}

$exitInput = trim((string)($_GET['exit_time'] ?? ''));
$exitValue = $exitInput !== '' ? $exitInput : date('Y-m-d\TH:i');

$error = '';
$hours = null;
$total = null;

$entry = strtotime($selected['entry_time']);
$exit = $exitInput === '' ? time() : strtotime(str_replace('T', ' ', $exitInput));

if ($exit === false || $exit < $entry) {
    $error = 'Exit time must be a valid date/time after entry time.';
} else {
    $seconds = $exit - $entry;
    $hours = (int)ceil($seconds / 3600);

    if ($hours < 1) {
        $hours = 1;
    }

    $total = $hours * (float)$selected['price_per_hour'];
}

ob_start();
?>

<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

<form method="get">
<select name="assignment_id" onchange="this.form.submit()">
<?php foreach ($assignments as $a): ?>
<option value="<?= (int)$a['id'] ?>" <?= (int)$a['id'] === $assignmentId ? 'selected' : '' ?>>
<?= e($a['driver_name']) ?> — <?= e($a['vehicle_plate']) ?> — <?= e($a['lot_name']) ?> — <?= e($a['entry_time']) ?>
</option>
<?php endforeach; ?>
</select>

<label>Exit time
<input type="datetime-local" name="exit_time" value="<?= e($exitValue) ?>">
</label>

<button>Calculate bill</button>
</form>

<?php if (!$error): ?>
<table>
<thead>
<tr>
<th>Driver</th>
<th>Vehicle</th>
<th>Parking lot</th>
<th>Entry time</th>
<th>Exit time</th>
<th>Hours</th>
<th>Rate/Hour</th>
<th>Total</th>
</tr>
</thead>
<tbody>
<tr>
<td><?= e($selected['driver_name']) ?></td>
<td><?= e($selected['vehicle_plate']) ?></td>
<td><?= e($selected['lot_name']) ?></td>
<td><?= e($selected['entry_time']) ?></td>
<td><?= e(date('Y-m-d H:i', $exit)) ?></td>
<td><?= (int)$hours ?></td>
<td>$<?= e(number_format((float)$selected['price_per_hour'], 2)) ?></td>
<td><strong>$<?= e(number_format((float)$total, 2)) ?></strong></td>
</tr>
</tbody>
</table>
<?php endif; ?>

<?php page('Billing', ob_get_clean());?>