<?php
require 'config.php';
require 'helpers.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $total = filter_var($_POST['total_spaces'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
    $available = filter_var($_POST['available_spaces'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
    $price = filter_var($_POST['price_per_hour'] ?? null, FILTER_VALIDATE_FLOAT, ['options'=>['min_range'=>0]]);
    if ($name === '' || strlen($name)>100 || $location === '' || strlen($location)>150 || $total===false || $available===false || $price===false || $available > $total) {
        $error = 'Check required fields. Available spaces cannot exceed total spaces.';
    } else {
        try {
            $stmt=$pdo->prepare('INSERT INTO parking_lots (name,location,total_spaces,available_spaces,price_per_hour) VALUES (?,?,?,?,?)');
            $stmt->execute([$name,$location,$total,$available,$price]);
            header('Location: index.php'); exit;
        } catch(PDOException $ex) { $error='Unable to save parking lot.'; }
    }
}
ob_start(); ?>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<form method="post">
<label>Name <input name="name" maxlength="100" required></label>
<label>Location <input name="location" maxlength="150" required></label>
<label>Total Spaces <input type="number" name="total_spaces" min="0" required></label>
<label>Available Spaces <input type="number" name="available_spaces" min="0" required></label>
<label>Price per Hour <input type="number" name="price_per_hour" min="0" step="0.01" required></label>
<button>Save parking lot</button>
</form>
<?php page('Add Parking Lot',ob_get_clean());?>