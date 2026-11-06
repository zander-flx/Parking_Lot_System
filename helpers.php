<?php
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function positiveId(mixed $value): int {
    return filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]) ?: 0;
}
function page(string $title, string $body): void {
    echo '<!doctype html><html lang="en"><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<link rel="stylesheet" href="style.css"><title>'.e($title).'</title>';
    echo '<main><nav><a href="index.php">Parking Lots</a> · <a href="create.php">Add parking lot</a> · <a href="assign.php">Assign Driver</a> · <a href="drivers.php">Assigned Drivers</a></nav>';
    echo '<h1>'.e($title).'</h1>'.$body.'</main></html>';
}
?>