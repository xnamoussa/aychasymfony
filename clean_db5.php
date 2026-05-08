<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Checking for orphans...\n";

try {
    $count = $pdo->exec("DELETE es FROM `eventsponsor` es LEFT JOIN `sponsor` s ON es.`sponsor_id` = s.`id` WHERE s.`id` IS NULL AND es.`sponsor_id` IS NOT NULL");
    echo "Deleted $count orphans from eventsponsor (sponsor_id).\n";
} catch (Exception $e) {
    echo "Error eventsponsor (sponsor): " . $e->getMessage() . "\n";
}

echo "Done.\n";
