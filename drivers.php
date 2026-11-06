<?php
require 'config.php'; require 'helpers.php';

$lots = $pdo->query('SELECT id, name FROM parking_lots ORDER BY name')->fetchAll();

if (empty($lots)) {
    ob_start(); ?>
    <p>No parking lots found.</p>
    <?php
    page('Assigned Drivers', ob_get_clean());
    exit;
}

$lotId = positiveId($_GET['lot_id'] ?? 0);

$validLot = in_array(
    $lotId,
    array_map(fn($l) => (int)$l['id'], $lots),
    true
);

if ($lotId === 0 || !$validLot) {
    $lotId = (int)$lots[0]['id'];
}

$search = trim((string)($_GET['q'] ?? ''));

$sql = 'SELECT p.name, p.location, a.driver_name, a.vehicle_plate, a.entry_time
        FROM assignments a
        JOIN parking_lots p ON p.id = a.parking_lot_id
        WHERE a.parking_lot_id = :lot';

$params = ['lot' => $lotId];

if ($search !== '') {
    $sql .= ' AND (a.driver_name LIKE :q1 OR a.vehicle_plate LIKE :q2)';
    $params['q1'] = "%$search%";
    $params['q2'] = "%$search%";
}

$sql .= ' ORDER BY a.entry_time DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$selectedLotName = '';
foreach ($lots as $l) {
    if ((int)$l['id'] === $lotId) {
        $selectedLotName = $l['name'];
        break;
    }
}

ob_start();
?>
<form method="get">
<select name="lot_id" onchange="this.form.submit()">
<?php foreach ($lots as $l): ?>
<option value="<?= (int)$l['id'] ?>" <?= $lotId === (int)$l['id'] ? 'selected' : '' ?>>
<?= e($l['name']) ?>
</option>
<?php endforeach; ?>
</select>

<input name="q" placeholder="Search driver or plate" value="<?= e($search) ?>">
<button>Search</button>
</form>

<h2><?= e($selectedLotName) ?></h2>

<table>
<thead>
<tr>
<th>Driver</th>
<th>Vehicle plate</th>
<th>Entry time</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= e($r['driver_name']) ?></td>
<td><?= e($r['vehicle_plate']) ?></td>
<td><?= e($r['entry_time']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p><?= count($rows) ?> driver(s) found</p>

<?php page('Assigned Drivers', ob_get_clean());?>