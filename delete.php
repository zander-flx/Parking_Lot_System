<?php
require 'config.php'; require 'helpers.php';
$id=positiveId($_GET['id'] ?? null);
$stmt=$pdo->prepare('SELECT name FROM parking_lots WHERE id=?');
$stmt->execute([$id]); $row=$stmt->fetch();
if(!$row){http_response_code(404);exit('Record not found.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    // A CSRF token is required for an internet-facing production application.
    $stmt=$pdo->prepare('DELETE FROM parking_lots WHERE id=?');
    $stmt->execute([$id]);
    header('Location: index.php'); exit;
}
ob_start(); ?>
<p>Delete <strong><?= e($row['name']) ?></strong>? This cannot be undone.</p>
<form method="post"><button class="danger">Yes, delete this record</button></form>
<a href="index.php">Cancel</a>
<?php page('Confirm Delete',ob_get_clean());?>