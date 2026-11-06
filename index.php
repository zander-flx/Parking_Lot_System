<?php
require 'config.php';
require 'helpers.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$search = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT id, name, location, total_spaces, available_spaces, price_per_hour
        FROM parking_lots';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE name LIKE :q1 OR location LIKE :q2';
    $params = ['q1' => "%$search%", 'q2' => "%$search%"];
}
$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
ob_start();
?>
<form method="get"><input name="q" placeholder="Search name or location" value="<?= e($search) ?>"><button>Search</button></form>
<table><thead><tr><th>Name</th><th>Location</th><th>Total</th><th>Available</th><th>Price/Hr</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['name']) ?></td><td><?= e($r['location']) ?></td>
<td><?= (int)$r['total_spaces'] ?></td><td><?= (int)$r['available_spaces'] ?></td>
<td>$<?= e(number_format((float)$r['price_per_hour'], 2)) ?></td>
<td><a href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a> · <a href="delete.php?id=<?= (int)$r['id'] ?>">Delete</a></td></tr>
<?php endforeach; ?>
</tbody></table>
<p><?= count($rows) ?> record(s) found</p>
<?php
page('Parking Lot System', ob_get_clean());?>