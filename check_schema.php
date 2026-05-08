<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$stmt = $pdo->query('SHOW COLUMNS FROM equipements');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo $col['Field'] . ' : ' . $col['Type'] . "\n";
}

echo "--------------\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    try {
        $stmt2 = $pdo->query("SHOW COLUMNS FROM `$table`");
        $cols = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            if ($col['Field'] === 'equipement_id' || $col['Field'] === 'id') {
                if ($col['Field'] === 'equipement_id' || $table === 'equipements') {
                    echo "$table." . $col['Field'] . " = " . $col['Type'] . "\n";
                }
            }
        }
    } catch (Exception $e) {}
}
