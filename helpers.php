<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

const CURRENCY = '₱';          // change your currency symbol here
const MIN_BILLED_HOURS = 1;    // minimum charge = 1 hour

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function positiveId(mixed $value): int {
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
}
function money(float|string|null $v): string {
    return CURRENCY . number_format((float)$v, 2);
}
function fmtDt(?string $dt, string $format = 'M j, Y · g:i A'): string {
    return $dt ? date($format, strtotime($dt)) : '—';
}
function minutesBetween(string $in, string $out): int {
    return max(0, (int)ceil((strtotime($out) - strtotime($in)) / 60));
}
function durationText(int $minutes): string {
    return $minutes >= 60
        ? intdiv($minutes, 60) . 'h ' . str_pad((string)($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm'
        : $minutes . 'm';
}
function billableHours(int $minutes): int {   // "per hour started": 2h05m bills as 3h
    return max(MIN_BILLED_HOURS, (int)ceil($minutes / 60));
}
function sessionStatus(array $s): string {
    if ($s['time_out'] === null) return 'Parked';
    return $s['amount_paid'] === null ? 'Unpaid' : 'Paid';
}
function badge(string $status): string {
    $class = ['Available'=>'ok','Occupied'=>'warn','Parked'=>'warn','Unpaid'=>'bad','Paid'=>'info'][$status] ?? 'info';
    return '<span class="badge b-' . $class . '">' . e($status) . '</span>';
}
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function takeFlash(): string {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (!$f) return '';
    $type = $f['type'] === 'ok' ? 'ok' : 'error';
    return '<div class="alert alert-' . $type . '">' . e($f['msg']) . '</div>';
}

/* ---------- Theme (Day Lot / Night Lot) ---------- */
function themeScript(): string {
    // Runs before the stylesheet paints, so the saved theme never flashes.
    return '<script>(function(){try{var t=localStorage.getItem("parkease-theme");'
        . 'if(t==="day"||t==="night"){document.documentElement.setAttribute("data-theme",t);'
        . 'var m=document.querySelector(\'meta[name="theme-color"]\');'
        . 'if(m)m.setAttribute("content",t==="day"?"#FFF6E9":"#0A171D");}}catch(e){}})();</script>';
}
function themeToggle(): string {
    return '<button type="button" class="theme-toggle" id="themeToggle" aria-pressed="false" aria-label="Switch to Day Lot theme">'
        . '<span class="tt-icon tt-sun" aria-hidden="true"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><circle cx="12" cy="12" r="4" fill="currentColor" stroke="none"/><path d="M12 2.8v2.4M12 18.8v2.4M2.8 12h2.4M18.8 12h2.4M5.2 5.2l1.7 1.7M17.1 17.1l1.7 1.7M18.8 5.2l-1.7 1.7M6.9 17.1l-1.7 1.7"/></svg></span>'
        . '<span class="tt-icon tt-moon" aria-hidden="true"><svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M20.4 14.9A8.5 8.5 0 0 1 9.1 3.6a8.5 8.5 0 1 0 11.3 11.3Z"/></svg></span>'
        . '<span class="tt-knob" aria-hidden="true"></span></button>'
        . '<span class="tt-label" aria-hidden="true"></span>';
}
function themeToggleJs(): string {
    return '<script>(function(){var b=document.getElementById("themeToggle");if(!b)return;'
        . 'function sync(){var d=document.documentElement.getAttribute("data-theme")==="day";'
        . 'b.setAttribute("aria-pressed",d?"true":"false");'
        . 'b.setAttribute("aria-label",d?"Switch to Night Lot theme":"Switch to Day Lot theme");'
        . 'b.title=d?"Switch to Night Lot":"Switch to Day Lot";'
        . 'var m=document.querySelector(\'meta[name="theme-color"]\');'
        . 'if(m)m.setAttribute("content",d?"#FFF6E9":"#0A171D");}'
        . 'b.addEventListener("click",function(){'
        . 'var n=document.documentElement.getAttribute("data-theme")==="day"?"night":"day";'
        . 'document.documentElement.setAttribute("data-theme",n);'
        . 'try{localStorage.setItem("parkease-theme",n);}catch(e){}sync();});'
        . 'sync();})();</script>';
}

/* ---------- Auth ---------- */
function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id'       => (int)$_SESSION['user_id'],
        'username' => (string)$_SESSION['username'],
        'name'     => (string)$_SESSION['full_name'],
        'role'     => (string)$_SESSION['role'],
    ];
}
function isAdmin(): bool {
    return (currentUser()['role'] ?? '') === 'admin';
}
function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        page('Access denied',
            '<div class="card form"><p class="alert alert-error">🔒 Administrators only. Sign in with an admin account to use this page.</p>' .
            '<div class="btn-row"><a class="btn" href="index.php">Back to dashboard</a></div></div>');
        exit;
    }
}
function page(string $title, string $body): void {
    $here = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $u = currentUser();
    $links = ['index.php'=>'Spaces','assign.php'=>'Park a vehicle','billing.php'=>'Billing'];
    if (isAdmin()) { $links['create.php'] = 'Add space'; $links['users.php'] = 'Users'; }
    echo '<!doctype html><html lang="en" data-theme="night"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="theme-color" content="#0A171D">';
    echo themeScript();
    echo '<link rel="stylesheet" href="style.css"><title>' . e($title) . ' · ParkEase</title></head><body><main>';
    echo '<nav><a class="brand" href="index.php">🅿️ ParkEase</a><div class="nav-links">';
    foreach ($links as $href => $label) {
        echo '<a href="' . $href . '"' . ($href === $here ? ' class="active"' : '') . '>' . $label . '</a>';
    }
    echo '</div><div class="nav-theme">' . themeToggle() . '</div>';
    if ($u) {
        $roleBadge = $u['role'] === 'admin' ? '<span class="badge b-info">Admin</span>' : '<span class="badge b-ok">Staff</span>';
        echo '<div class="nav-user"><span class="nav-name">' . e($u['name']) . '</span>' . $roleBadge
           . '<a class="btn btn-xs ghost" href="logout.php">Sign out</a></div>';
    }
    echo '</nav>' . takeFlash();
    echo '<h1>' . e($title) . '</h1>' . $body . '</main>' . themeToggleJs() . '</body></html>';
}

/* Secure by default: every page except login.php requires a signed-in user.
   Remember where the visitor was headed, so login can send them back there. */
$__page = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($__page !== 'login.php' && !currentUser()) {
    if ($__page !== 'logout.php' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
    }
    header('Location: login.php');
    exit;
}