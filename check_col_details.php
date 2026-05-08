<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');

function getColInfo($pdo, $table, $col) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

print_r(getColInfo($pdo, 'delivery', 'equipement_id'));
print_r(getColInfo($pdo, 'equipements', 'id'));
