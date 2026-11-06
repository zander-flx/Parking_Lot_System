<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function positiveId(mixed $value): int {
    return filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]) ?: 0;
}

function page(string $title, string $body): void {
    $authLink = empty($_SESSION['user_id'])
        ? '<a href="login.php">Login</a>'
        : '<a href="logout.php">Logout (' . e(($_SESSION['username'] ?? 'user') . ' - ' . ($_SESSION['role'] ?? 'user')) . ')</a>';

    $adminLink = '';

    if (($_SESSION['role'] ?? '') === 'admin') {
        $adminLink = ' · <a href="users.php">Users</a>';
    }

    echo '<!doctype html><html lang="en"><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<link rel="stylesheet" href="style.css"><title>'.e($title).'</title>';
    echo '<main><nav>';
    echo '<a href="index.php">Parking Lots</a> · ';
    echo '<a href="create.php">Add parking lot</a> · ';
    echo '<a href="assign.php">Assign Driver</a> · ';
    echo '<a href="drivers.php">Assigned Drivers</a> · ';
    echo '<a href="bill.php">Billing</a>';
    echo $adminLink;
    echo ' · ' . $authLink;
    echo '</nav>';
    echo '<h1>'.e($title).'</h1>'.$body.'</main></html>';
}
?>