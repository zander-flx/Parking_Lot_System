<?php
require 'config.php'; require 'helpers.php';
$f = in_array($_GET['f'] ?? '', ['active','unpaid','paid'], true) ? $_GET['f'] : 'all';
$q = trim((string)($_GET['q'] ?? ''));
$where = []; $params = [];
if ($f === 'active') $where[] = 'ps.time_out IS NULL';
if ($f === 'unpaid') $where[] = 'ps.time_out IS NOT NULL AND ps.amount_paid IS NULL';
if ($f === 'paid')   $where[] = 'ps.amount_paid IS NOT NULL';
if ($q !== '') {
    $where[] = '(d.plate_number LIKE :q1 OR d.full_name LIKE :q2 OR s.space_code LIKE :q3)';
    $params = ['q1'=>"%$q%", 'q2'=>"%$q%", 'q3'=>"%$q%"];
}
$sql = 'SELECT ps.*, s.space_code, d.full_name, d.plate_number
        FROM sessions ps JOIN spaces s ON s.space_id = ps.space_id
        JOIN drivers d ON d.driver_id = ps.driver_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', array_map(fn($w) => "($w)", $where));
$sql .= ' ORDER BY (ps.time_out IS NULL) DESC, ps.session_id DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
$c = $pdo->query('SELECT COUNT(*) AS n_all, SUM(time_out IS NULL) AS n_active,
                  SUM(time_out IS NOT NULL AND amount_paid IS NULL) AS n_unpaid,
                  SUM(amount_paid IS NOT NULL) AS n_paid FROM sessions')->fetch();
$today = (float)$pdo->query('SELECT COALESCE(SUM(amount_paid),0) FROM sessions WHERE paid_at >= CURDATE()')->fetchColumn();
$unpaidTotal = (float)$pdo->query('SELECT COALESCE(SUM(amount_due),0) FROM sessions WHERE time_out IS NOT NULL AND amount_paid IS NULL')->fetchColumn();
$now = $pdo->query('SELECT NOW()')->fetchColumn();
$chip = function (string $key, string $label, int $n) use ($f, $q) {
    $url = '?' . http_build_query(['f' => $key, 'q' => $q]);
    return '<a class="chip' . ($f === $key ? ' active' : '') . '" href="' . e($url) . '">' . e($label) . ' (' . $n . ')</a>';
};
ob_start(); ?>
<div class="stats">
  <div class="stat"><div class="stat-icon">💰</div><div><div class="stat-num"><?= money($today) ?></div><div class="stat-label">Collected today</div></div></div>
  <div class="stat"><div class="stat-icon">⏳</div><div><div class="stat-num"><?= money($unpaidTotal) ?></div><div class="stat-label">Awaiting payment</div></div></div>
  <div class="stat"><div class="stat-icon">🚗</div><div><div class="stat-num"><?= (int)$c['n_active'] ?></div><div class="stat-label">On the lot</div></div></div>
</div>
<div class="toolbar">
  <div class="chips"><?= $chip('all','All',(int)$c['n_all']) . $chip('active','Parked',(int)$c['n_active']) . $chip('unpaid','Unpaid',(int)$c['n_unpaid']) . $chip('paid','Paid',(int)$c['n_paid']) ?></div>
  <form method="get" class="searchform">
    <input type="hidden" name="f" value="<?= e($f) ?>">
    <input name="q" placeholder="Search plate, driver or space" value="<?= e($q) ?>">
    <button class="btn">Search</button>
  </form>
</div>
<div class="table-wrap"><table>
<thead><tr><th>Ticket</th><th>Vehicle</th><th>Space</th><th>Time in</th><th>Time out</th><th>Duration</th><th>Bill</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($rows as $r):
    $status = sessionStatus($r);
    $mins = minutesBetween($r['time_in'], $r['time_out'] ?? $now);
    $bill = $r['amount_due'] !== null
        ? money($r['amount_due'])
        : '<span class="muted">~' . money(billableHours($mins) * (float)$r['rate']) . '</span>';
?>
<tr>
  <td class="mono">#<?= str_pad((string)$r['session_id'], 6, '0', STR_PAD_LEFT) ?></td>
  <td><strong><?= e($r['plate_number']) ?></strong><br><small><?= e($r['full_name']) ?></small></td>
  <td class="mono strong"><?= e($r['space_code']) ?></td>
  <td><?= fmtDt($r['time_in'], 'M j · g:i A') ?></td>
  <td><?= fmtDt($r['time_out'], 'M j · g:i A') ?></td>
  <td><?= durationText($mins) ?></td>
  <td><?= $bill ?></td>
  <td><?= badge($status) ?></td>
  <td class="actions">
    <?php if ($status === 'Parked'): ?><a class="btn btn-xs" href="checkout.php?id=<?= (int)$r['session_id'] ?>">Checkout</a>
    <?php elseif ($status === 'Unpaid'): ?><a class="btn btn-xs" href="pay.php?id=<?= (int)$r['session_id'] ?>">Collect</a>
    <?php else: ?><a class="btn btn-xs ghost" href="receipt.php?id=<?= (int)$r['session_id'] ?>">Receipt</a><?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?>
<tr><td colspan="9"><div class="empty"><span class="big">🧾</span>No tickets match this view.</div></td></tr>
<?php endif; ?>
</tbody></table></div>
<p class="count"><?= count($rows) ?> ticket(s) · “~” marks a running estimate for vehicles still parked</p>
<?php page('Billing & Payments', ob_get_clean());