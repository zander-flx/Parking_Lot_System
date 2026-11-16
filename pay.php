<?php
require 'config.php'; require 'helpers.php';
$id = positiveId($_GET['id'] ?? null);
$stmt = $pdo->prepare('SELECT ps.*, s.space_code, d.full_name, d.plate_number
                       FROM sessions ps JOIN spaces s ON s.space_id = ps.space_id
                       JOIN drivers d ON d.driver_id = ps.driver_id WHERE ps.session_id = ?');
$stmt->execute([$id]); $s = $stmt->fetch();
if (!$s) { http_response_code(404); exit('Ticket not found.'); }
if ($s['time_out'] === null)    { header('Location: checkout.php?id=' . $id); exit; }
if ($s['amount_paid'] !== null) { header('Location: receipt.php?id=' . $id); exit; }
$due = (float)$s['amount_due'];
$error = ''; $method = 'Cash';
$tendered = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['tendered'] ?? '') : number_format($due, 2, '.', '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = (string)($_POST['method'] ?? '');
    if (!in_array($method, ['Cash','GCash','Card'], true)) {
        $error = 'Choose a valid payment method.';
    } elseif ($method === 'Cash') {
        $t = filter_var($tendered, FILTER_VALIDATE_FLOAT);
        if ($t === false || $t < 0)               $error = 'Enter the cash amount received.';
        elseif (round($t, 2) + 0.001 < $due)      $error = 'Cash received is less than the amount due.';
        else $tendered = round($t, 2);
    } else {
        $tendered = $due; // exact amount for e-wallet / card
    }
    if ($error === '') {
        // A CSRF token is required for an internet-facing production application.
        $upd = $pdo->prepare('UPDATE sessions SET amount_paid = ?, tendered = ?, payment_method = ?, paid_at = NOW()
                              WHERE session_id = ? AND amount_paid IS NULL AND time_out IS NOT NULL');
        $upd->execute([$due, $tendered, $method, $id]);
        if ($upd->rowCount()) {
            $change = (float)$tendered - $due;
            setFlash('ok', $method === 'Cash' && $change > 0 ? 'Payment saved. Change: ' . money($change) : 'Payment saved.');
        }
        header('Location: receipt.php?id=' . $id); exit;
    }
}
ob_start(); ?>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<div class="card bill">
<div class="bill-line"><span>Ticket</span><strong class="mono">#<?= str_pad((string)$id, 6, '0', STR_PAD_LEFT) ?></strong></div>
<div class="bill-line"><span>Vehicle</span><strong><?= e($s['plate_number']) ?> · <?= e($s['full_name']) ?></strong></div>
<div class="bill-line"><span>Space / duration</span><strong><?= e($s['space_code']) ?> · <?= durationText(minutesBetween($s['time_in'], $s['time_out'])) ?></strong></div>
<div class="bill-total"><span>Amount due</span><span class="amount"><?= money($due) ?></span></div>
<hr class="dashed">
<form method="post">
<label>Payment method <select name="method" id="method">
<?php foreach (['Cash','GCash','Card'] as $m): ?><option <?= $method === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?>
</select></label>
<label>Amount received <input type="number" step="0.01" min="0" name="tendered" id="tendered" value="<?= e($tendered) ?>" required>
<small class="hint">For GCash/Card the exact amount is charged automatically.</small></label>
<span id="changeOut"></span>
<div class="btn-row"><button class="btn">Confirm payment</button><a class="btn ghost" href="billing.php?f=unpaid">Back</a></div>
</form>
</div>
<script>
const due = <?= json_encode($due) ?>, cur = <?= json_encode(CURRENCY) ?>;
const m = document.getElementById('method'), t = document.getElementById('tendered'), out = document.getElementById('changeOut');
function upd() {
    const cash = m.value === 'Cash';
    t.readOnly = !cash;
    if (!cash) t.value = due.toFixed(2);
    const change = (parseFloat(t.value) || 0) - due;
    out.textContent = cash
        ? (change >= 0 ? 'Change: ' + cur + change.toFixed(2) : 'Short by ' + cur + (-change).toFixed(2))
        : 'Exact amount will be charged.';
    out.classList.toggle('short', cash && change < 0);
}
m.addEventListener('change', upd); t.addEventListener('input', upd); upd();
</script>
<?php page('Collect Payment', ob_get_clean());