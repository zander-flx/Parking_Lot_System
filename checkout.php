<?php
require 'config.php'; require 'helpers.php';
$id = positiveId($_GET['id'] ?? null);
$stmt = $pdo->prepare('SELECT ps.*, s.space_code, t.type_name, d.full_name, d.plate_number
                       FROM sessions ps JOIN spaces s ON s.space_id = ps.space_id
                       JOIN space_types t ON t.type_id = s.type_id
                       JOIN drivers d ON d.driver_id = ps.driver_id WHERE ps.session_id = ?');
$stmt->execute([$id]); $s = $stmt->fetch();
if (!$s) { http_response_code(404); exit('Ticket not found.'); }
if ($s['time_out'] !== null) { header('Location: ' . ($s['amount_paid'] === null ? 'pay.php' : 'receipt.php') . '?id=' . $id); exit; }
$now = $pdo->query('SELECT NOW()')->fetchColumn();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A CSRF token is required for an internet-facing production application.
    $pdo->beginTransaction();
    $lock = $pdo->prepare('SELECT * FROM sessions WHERE session_id = ? AND time_out IS NULL FOR UPDATE');
    $lock->execute([$id]);
    if ($cur = $lock->fetch()) {
        $now  = $pdo->query('SELECT NOW()')->fetchColumn();   // recompute at the exact click moment
        $mins = minutesBetween($cur['time_in'], $now);
        $due  = billableHours($mins) * (float)$cur['rate'];
        $pdo->prepare('UPDATE sessions SET time_out = ?, amount_due = ? WHERE session_id = ?')->execute([$now, $due, $id]);
        $pdo->commit();
        setFlash('ok', 'Vehicle released. Bill generated — collect payment below.');
        header('Location: pay.php?id=' . $id); exit;
    }
    $pdo->rollBack();
    setFlash('error', 'That ticket was already closed.');
    header('Location: billing.php'); exit;
}
$mins  = minutesBetween($s['time_in'], $now);
$hours = billableHours($mins);
$due   = $hours * (float)$s['rate'];
ob_start(); ?>
<div class="card bill">
<div class="bill-line"><span>Ticket</span><strong class="mono">#<?= str_pad((string)$id, 6, '0', STR_PAD_LEFT) ?></strong></div>
<div class="bill-line"><span>Vehicle</span><strong><?= e($s['plate_number']) ?> · <?= e($s['full_name']) ?></strong></div>
<div class="bill-line"><span>Space</span><strong><?= e($s['space_code']) ?> (<?= e($s['type_name']) ?>)</strong></div>
<div class="bill-line"><span>Time in</span><strong><?= fmtDt($s['time_in']) ?></strong></div>
<div class="bill-line"><span>Time out (now)</span><strong><?= fmtDt($now) ?></strong></div>
<div class="bill-line"><span>Parked for</span><strong><?= durationText($mins) ?></strong></div>
<hr class="dashed">
<div class="bill-line"><span>Rate</span><strong><?= money($s['rate']) ?>/hr</strong></div>
<div class="bill-line"><span>Billed hours</span><strong><?= $hours ?> hr <small>(per hour started, min <?= MIN_BILLED_HOURS ?>)</small></strong></div>
<div class="bill-total"><span>Amount due</span><span class="amount"><?= money($due) ?></span></div>
<form method="post" class="btn-row">
<button class="btn">Release vehicle &amp; bill <?= money($due) ?></button>
<a class="btn ghost" href="index.php">Cancel</a>
</form>
<small class="hint">The total is recomputed at the moment you confirm, so any extra minutes are included.</small>
</div>
<?php page('Checkout & Bill', ob_get_clean());