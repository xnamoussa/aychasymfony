<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Checking for orphans...\n";

try {
    $count = $pdo->exec("DELETE a FROM `abonnement` a LEFT JOIN `users` u ON a.`user_id` = u.`id` WHERE u.`id` IS NULL AND a.`user_id` IS NOT NULL");
    echo "Deleted $count orphans from abonnement (user_id).\n";
} catch (Exception $e) {
    echo "Error abonnement (user_id): " . $e->getMessage() . "\n";
}

echo "Done.\n";
