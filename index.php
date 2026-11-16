<?php
require 'config.php';
require 'helpers.php';

$search = trim((string)($_GET['q'] ?? ''));
$filter = in_array($_GET['f'] ?? '', ['available','occupied'], true) ? $_GET['f'] : 'all';

$sql = 'SELECT s.space_id, s.space_code, t.type_name, t.hourly_rate,
               ps.session_id, ps.time_in, d.full_name, d.plate_number
        FROM spaces s
        JOIN space_types t ON t.type_id = s.type_id
        LEFT JOIN sessions ps ON ps.space_id = s.space_id AND ps.time_out IS NULL
        LEFT JOIN drivers d ON d.driver_id = ps.driver_id';
$where = []; $params = [];
if ($search !== '') {
    $where[] = '(s.space_code LIKE :q1 OR t.type_name LIKE :q2 OR d.plate_number LIKE :q3)';
    $params = ['q1'=>"%$search%", 'q2'=>"%$search%", 'q3'=>"%$search%"];
}
if ($filter === 'available') $where[] = 'ps.session_id IS NULL';
if ($filter === 'occupied')  $where[] = 'ps.session_id IS NOT NULL';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY s.space_code';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$stats = $pdo->query('SELECT COUNT(*) AS total, SUM(ps.session_id IS NOT NULL) AS occupied
                      FROM spaces s LEFT JOIN sessions ps ON ps.space_id = s.space_id AND ps.time_out IS NULL')->fetch();
$revenue = (float)$pdo->query('SELECT COALESCE(SUM(amount_paid),0) FROM sessions WHERE paid_at >= CURDATE()')->fetchColumn();
$now = $pdo->query('SELECT NOW()')->fetchColumn();
$total = (int)$stats['total']; $occupied = (int)$stats['occupied']; $available = $total - $occupied;

$chip = function (string $key, string $label) use ($filter, $search) {
    $url = '?' . http_build_query(['f' => $key, 'q' => $search]);
    return '<a class="chip' . ($filter === $key ? ' active' : '') . '" href="' . e($url) . '">' . e($label) . '</a>';
};
ob_start();
?>
<div class="stats">
  <div class="stat"><div class="stat-icon">🅿️</div><div><div class="stat-num"><?= $total ?></div><div class="stat-label">Spaces</div></div></div>
  <div class="stat"><div class="stat-icon">✅</div><div><div class="stat-num"><?= $available ?></div><div class="stat-label">Available</div></div></div>
  <div class="stat"><div class="stat-icon">🚗</div><div><div class="stat-num"><?= $occupied ?></div><div class="stat-label">Occupied</div></div></div>
  <div class="stat"><div class="stat-icon">💰</div><div><div class="stat-num"><?= money($revenue) ?></div><div class="stat-label">Collected today</div></div></div>
</div>
<div class="toolbar">
  <div class="chips"><?= $chip('all','All') . $chip('available','Available') . $chip('occupied','Occupied') ?></div>
  <form method="get" class="searchform">
    <input type="hidden" name="f" value="<?= e($filter) ?>">
    <input name="q" placeholder="Search space, type or plate" value="<?= e($search) ?>">
    <button class="btn">Search</button>
  </form>
</div>
<div class="table-wrap"><table>
<thead><tr><th>Space</th><th>Type</th><th>Rate</th><th>Status</th><th>Current vehicle</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): $busy = $r['session_id'] !== null; ?>
<tr>
  <td class="mono strong"><?= e($r['space_code']) ?></td>
  <td><?= e($r['type_name']) ?></td>
  <td><?= money($r['hourly_rate']) ?>/hr</td>
  <td><?= badge($busy ? 'Occupied' : 'Available') ?></td>
  <td><?php if ($busy): ?>
    <strong><?= e($r['plate_number']) ?></strong><br>
    <small><?= e($r['full_name']) ?> · since <?= fmtDt($r['time_in'], 'g:i A') ?> (<?= durationText(minutesBetween($r['time_in'], $now)) ?>)</small>
  <?php else: ?><span class="muted">—</span><?php endif; ?></td>
    <td class="actions">
    <?php if ($busy): ?><a class="btn btn-xs" href="checkout.php?id=<?= (int)$r['session_id'] ?>">Checkout</a>
    <?php else: ?><a class="btn btn-xs" href="assign.php?space_id=<?= (int)$r['space_id'] ?>">Park</a><?php endif; ?>
    <?php if (isAdmin()): ?>
      <a class="btn btn-xs ghost" href="edit.php?id=<?= (int)$r['space_id'] ?>">Edit</a>
      <?php if (!$busy): ?><a class="btn btn-xs ghost danger-text" href="delete.php?id=<?= (int)$r['space_id'] ?>">Delete</a><?php endif; ?>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?>
<tr><td colspan="6"><div class="empty"><span class="big">🅿️</span>No spaces found. <a href="create.php">Add your first space</a>.</div></td></tr>
<?php endif; ?>
</tbody></table></div>
<p class="count"><?= count($rows) ?> space(s) shown · availability is computed live from open tickets</p>
<?php
page('Parking Spaces', ob_get_clean());