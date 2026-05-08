<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Checking for orphans...\n";

// specific tables that failed
try {
    $count = $pdo->exec("DELETE p FROM `programme` p LEFT JOIN `evenement` e ON p.`event_id` = e.`id_event` WHERE e.`id_event` IS NULL AND p.`event_id` IS NOT NULL");
    echo "Deleted $count orphans from programme.\n";
} catch (Exception $e) {
    echo "Error programme: " . $e->getMessage() . "\n";
}

try {
    $count = $pdo->exec("DELETE es FROM `eventsponsor` es LEFT JOIN `evenement` e ON es.`event_id` = e.`id_event` WHERE e.`id_event` IS NULL AND es.`event_id` IS NOT NULL");
    echo "Deleted $count orphans from eventsponsor.\n";
} catch (Exception $e) {
    echo "Error eventsponsor: " . $e->getMessage() . "\n";
}

try {
    $count = $pdo->exec("DELETE p FROM `participation` p LEFT JOIN `evenement` e ON p.`evenement_id` = e.`id_event` WHERE e.`id_event` IS NULL AND p.`evenement_id` IS NOT NULL");
    echo "Deleted $count orphans from participation (evenement_id).\n";
} catch (Exception $e) {
    echo "Error participation: " . $e->getMessage() . "\n";
}

// Clean up created_by_id foreign keys just in case
$tablesToClean = [
    'menu' => ['created_by_id', 'updated_by_id'],
    'reservation_maquillage' => ['created_by_id', 'updated_by_id'],
    'sponsor_feedback' => ['created_by_id', 'updated_by_id'],
    'favori' => ['created_by_id', 'updated_by_id'],
    'payment_transaction' => ['created_by_id', 'updated_by_id'],
    'face_data' => ['created_by_id', 'updated_by_id'],
];

foreach ($tablesToClean as $table => $cols) {
    foreach ($cols as $col) {
        try {
            $count = $pdo->exec("DELETE t FROM `$table` t LEFT JOIN users u ON t.`$col` = u.id WHERE u.id IS NULL AND t.`$col` IS NOT NULL");
            if ($count > 0) echo "Deleted $count orphans from $table ($col).\n";
        } catch (Exception $e) {
        }
    }
}

echo "Done.\n";
