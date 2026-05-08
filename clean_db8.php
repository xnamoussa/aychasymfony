<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Checking for orphans in ticket (user_id)...\n";

try {
    $count = $pdo->exec("DELETE t FROM `ticket` t LEFT JOIN `users` u ON t.`user_id` = u.`id` WHERE u.`id` IS NULL AND t.`user_id` IS NOT NULL");
    echo "Deleted $count orphans from ticket (user_id).\n";
} catch (Exception $e) {
    echo "Error ticket (user_id): " . $e->getMessage() . "\n";
}

try {
    $count = $pdo->exec("DELETE t FROM `ticket` t LEFT JOIN `abonnement` a ON t.`abonnement_id` = a.`id` WHERE a.`id` IS NULL AND t.`abonnement_id` IS NOT NULL");
    echo "Deleted $count orphans from ticket (abonnement_id).\n";
} catch (Exception $e) {
    echo "Error ticket (abonnement_id): " . $e->getMessage() . "\n";
}

echo "Done.\n";
