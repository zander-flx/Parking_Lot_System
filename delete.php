<?php
require 'config.php'; require 'helpers.php';
requireAdmin();
$id = positiveId($_GET['id'] ?? null);
$stmt = $pdo->prepare('SELECT space_code FROM spaces WHERE space_id = ?');
$stmt->execute([$id]); $space = $stmt->fetch();
if (!$space) { http_response_code(404); exit('Parking space not found.'); }
$stmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(time_out IS NULL) AS active FROM sessions WHERE space_id = ?');
$stmt->execute([$id]); $use = $stmt->fetch();
$block = '';
if ((int)$use['active'] > 0)     $block = 'A vehicle is still parked in this space. Checkout the vehicle first, then delete the space.';
elseif ((int)$use['total'] > 0)  $block = 'This space has parking/billing history. Keep it so old receipts stay valid — rename or retype it instead.';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $block === '') {
    // A CSRF token is required for an internet-facing production application.
    $pdo->prepare('DELETE FROM spaces WHERE space_id = ?')->execute([$id]);
    setFlash('ok', "Space {$space['space_code']} deleted.");
    header('Location: index.php'); exit;
}
ob_start(); ?>
<?php if ($block): ?>
<p class="alert alert-error"><?= e($block) ?></p>
<div class="btn-row"><a class="btn ghost" href="index.php">Back to spaces</a><a class="btn" href="edit.php?id=<?= $id ?>">Edit instead</a></div>
<?php else: ?>
<div class="card form">
<p>Delete space <strong><?= e($space['space_code']) ?></strong>? This cannot be undone.</p>
<form method="post" class="btn-row"><button class="btn danger">Yes, delete this space</button><a class="btn ghost" href="index.php">Cancel</a></form>
</div>
<?php endif; ?>
<?php page('Delete Parking Space', ob_get_clean());