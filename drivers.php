<?php
require 'config.php'; require 'helpers.php';
$rows = $pdo->query('SELECT p.name, p.location, a.driver_name, a.vehicle_plate, a.entry_time
                     FROM assignments a
                     JOIN parking_lots p ON p.id = a.parking_lot_id
                     ORDER BY p.name ASC, a.entry_time DESC')->fetchAll();
ob_start();
?>
<table><thead><tr><th>Parking lot</th><th>Location</th><th>Driver</th><th>Vehicle plate</th><th>Entry time</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['name']) ?></td><td><?= e($r['location']) ?></td>
<td><?= e($r['driver_name']) ?></td><td><?= e($r['vehicle_plate']) ?></td>
<td><?= e($r['entry_time']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<p><?= count($rows) ?> driver(s) assigned</p>
<?php page('Assigned Drivers', ob_get_clean());?>