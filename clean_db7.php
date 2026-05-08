<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Checking for orphans in ticket...\n";

try {
    $count = $pdo->exec("DELETE t FROM `ticket` t LEFT JOIN `participation` p ON t.`participation_id` = p.`id` WHERE p.`id` IS NULL AND t.`participation_id` IS NOT NULL");
    echo "Deleted $count orphans from ticket (participation_id).\n";
} catch (Exception $e) {
    echo "Error ticket (participation_id): " . $e->getMessage() . "\n";
}

try {
    $count = $pdo->exec("DELETE pr FROM `programme_recommande` pr LEFT JOIN `participation` p ON pr.`participation_id` = p.`id` WHERE p.`id` IS NULL AND pr.`participation_id` IS NOT NULL");
    echo "Deleted $count orphans from programme_recommande (participation_id).\n";
} catch (Exception $e) {
    echo "Error programme_recommande (participation_id): " . $e->getMessage() . "\n";
}

echo "Done.\n";
