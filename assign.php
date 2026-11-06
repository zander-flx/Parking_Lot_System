<?php
require 'config.php'; require 'helpers.php';
$error = '';
$lots = $pdo->query('SELECT id, name, available_spaces FROM parking_lots WHERE available_spaces > 0 ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lotId = positiveId($_POST['parking_lot_id'] ?? null);
    $driver = trim((string)($_POST['driver_name'] ?? ''));
    $plate = trim((string)($_POST['vehicle_plate'] ?? ''));

    $stmt = $pdo->prepare('SELECT available_spaces FROM parking_lots WHERE id = ?');
    $stmt->execute([$lotId]);
    $lot = $stmt->fetch();

    if ($lotId === 0 || !$lot || $driver === '' || strlen($driver) > 100 || $plate === '' || strlen($plate) > 20) {
        $error = 'Please select a valid parking lot with available spaces and fill in all driver details.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt1 = $pdo->prepare('INSERT INTO assignments (parking_lot_id, driver_name, vehicle_plate) VALUES (?, ?, ?)');
            $stmt1->execute([$lotId, $driver, $plate]);
            
            $stmt2 = $pdo->prepare('UPDATE parking_lots SET available_spaces = available_spaces - 1 WHERE id = ? AND available_spaces > 0');
            $stmt2->execute([$lotId]);

            if ($stmt2->rowCount() === 0) {
                throw new Exception('No spaces left');
            }
            $pdo->commit();
            header('Location: index.php'); exit;
        } catch(Exception $ex) {
            $pdo->rollBack();
            $error = 'Unable to assign space. It may have just been taken by someone else.';
        }
    }
}
ob_start(); ?>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<?php if (empty($lots)): ?>
    <p>No parking lots currently have available spaces.</p>
<?php else: ?>
<form method="post">
<label>Parking Lot <select name="parking_lot_id" required>
<option value="">-- Select Lot --</option>
<?php foreach($lots as $l): ?><option value="<?= (int)$l['id'] ?>"><?= e($l['name']) ?> (<?= (int)$l['available_spaces'] ?> spaces left)</option><?php endforeach; ?>
</select></label>
<label>Driver Name <input name="driver_name" maxlength="100" required></label>
<label>Vehicle Plate <input name="vehicle_plate" maxlength="20" required></label>
<button>Assign Space</button>
</form>
<?php endif; ?>
<?php page('Assign Parking Space',ob_get_clean());?>