<?php
require 'config.php'; require 'helpers.php';
$id=positiveId($_GET['id'] ?? null);
$stmt=$pdo->prepare('SELECT * FROM parking_lots WHERE id=?');
$stmt->execute([$id]); $lot=$stmt->fetch();
if (!$lot) { http_response_code(404); exit('Parking lot not found.'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim((string)($_POST['name'] ?? ''));
    $location=trim((string)($_POST['location'] ?? ''));
    $total=filter_var($_POST['total_spaces'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
    $available=filter_var($_POST['available_spaces'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
    $price=filter_var($_POST['price_per_hour'] ?? null,FILTER_VALIDATE_FLOAT,['options'=>['min_range'=>0]]);
    if ($name==='' || strlen($name)>100 || $location==='' || strlen($location)>150 || $total===false || $available===false || $price===false || $available > $total) {
        $error='Please correct the fields. Available spaces cannot exceed total.';
    } else {
        try {
            $update=$pdo->prepare('UPDATE parking_lots SET name=?,location=?,total_spaces=?,available_spaces=?,price_per_hour=? WHERE id=?');
            $update->execute([$name,$location,$total,$available,$price,$id]);
            header('Location: index.php'); exit;
        } catch(PDOException $ex) { $error='Could not save changes.'; }
    }
    $lot=['name'=>$name,'location'=>$location,'total_spaces'=>$total,'available_spaces'=>$available,'price_per_hour'=>$price];
}
ob_start(); ?>
<?php if($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<form method="post">
<label>Name <input name="name" maxlength="100" required value="<?= e($lot['name']) ?>"></label>
<label>Location <input name="location" maxlength="150" required value="<?= e($lot['location']) ?>"></label>
<label>Total Spaces <input type="number" name="total_spaces" min="0" required value="<?= (int)$lot['total_spaces'] ?>"></label>
<label>Available Spaces <input type="number" name="available_spaces" min="0" required value="<?= (int)$lot['available_spaces'] ?>"></label>
<label>Price per Hour <input type="number" name="price_per_hour" min="0" step="0.01" required value="<?= e(number_format((float)$lot['price_per_hour'], 2)) ?>"></label>
<button>Update</button>
</form>
<?php page('Edit Parking Lot',ob_get_clean());?>