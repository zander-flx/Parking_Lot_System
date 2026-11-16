<?php
require 'config.php'; require 'helpers.php';
$id = positiveId($_GET['id'] ?? null);
$stmt = $pdo->prepare('SELECT ps.*, s.space_code, t.type_name, d.full_name, d.plate_number
                       FROM sessions ps JOIN spaces s ON s.space_id = ps.space_id
                       JOIN space_types t ON t.type_id = s.type_id
                       JOIN drivers d ON d.driver_id = ps.driver_id WHERE ps.session_id = ?');
$stmt->execute([$id]); $s = $stmt->fetch();
if (!$s) { http_response_code(404); exit('Ticket not found.'); }
if ($s['amount_paid'] === null) { header('Location: ' . ($s['time_out'] === null ? 'checkout.php' : 'pay.php') . '?id=' . $id); exit; }
$mins  = minutesBetween($s['time_in'], $s['time_out']);
$hours = billableHours($mins);
$change = max(0, (float)$s['tendered'] - (float)$s['amount_paid']);
ob_start(); ?>
<div class="card receipt">
<div class="rhead"><div class="logo">🅿️</div><h2>ParkEase</h2><p>Official parking receipt</p><p class="mono">Ticket #<?= str_pad((string)$id, 6, '0', STR_PAD_LEFT) ?></p></div>
<hr class="dashed">
<div class="rline"><span>Driver</span><strong><?= e($s['full_name']) ?></strong></div>
<div class="rline"><span>Plate</span><strong class="mono"><?= e($s['plate_number']) ?></strong></div>
<div class="rline"><span>Space</span><strong><?= e($s['space_code']) ?> · <?= e($s['type_name']) ?></strong></div>
<div class="rline"><span>Time in</span><strong><?= fmtDt($s['time_in']) ?></strong></div>
<div class="rline"><span>Time out</span><strong><?= fmtDt($s['time_out']) ?></strong></div>
<div class="rline"><span>Duration</span><strong><?= durationText($mins) ?></strong></div>
<div class="rline"><span>Billing</span><strong><?= $hours ?> hr × <?= money($s['rate']) ?></strong></div>
<hr class="dashed">
<div class="bill-total"><span>TOTAL PAID</span><span class="amount"><?= money($s['amount_paid']) ?></span></div>
<div class="rline"><span>Method</span><strong><?= e((string)$s['payment_method']) ?></strong></div>
<?php if ($s['payment_method'] === 'Cash'): ?>
<div class="rline"><span>Tendered</span><strong><?= money($s['tendered']) ?></strong></div>
<div class="rline"><span>Change</span><strong><?= money($change) ?></strong></div>
<?php endif; ?>
<div class="rline"><span>Paid at</span><strong><?= fmtDt($s['paid_at']) ?></strong></div>
<hr class="dashed">
<p class="thanks">Thank you and drive safely! 🚗</p>
</div>
<div class="btn-row center no-print">
<button class="btn" onclick="window.print()">🖨 Print</button>
<a class="btn ghost" href="billing.php">Back to billing</a>
<a class="btn ghost" href="assign.php">Park another vehicle</a>
</div>
<?php page('Payment Receipt', ob_get_clean());