<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tablesWithUpdatedAt = [
    'menu',
    'reservation_maquillage',
    'favori',
    'payment_transaction',
    'face_data',
    'sponsor_feedback',
    'delivery' // delivery does not use updated_at apparently but we can check
];

$now = (new DateTime())->format('Y-m-d H:i:s');

foreach ($tablesWithUpdatedAt as $table) {
    try {
        // Check if updated_at exists
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'updated_at'");
        if ($stmt->fetch()) {
            echo "Fixing updated_at in $table...\n";
            $count = $pdo->exec("UPDATE `$table` SET `updated_at` = '$now' WHERE `updated_at` IS NULL OR `updated_at` = '0000-00-00 00:00:00'");
            echo "Updated $count rows in $table.\n";
        }
        
        // Also fix created_at if it's there
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'created_at'");
        if ($stmt->fetch()) {
            $count = $pdo->exec("UPDATE `$table` SET `created_at` = '$now' WHERE `created_at` IS NULL OR `created_at` = '0000-00-00 00:00:00'");
            echo "Updated $count rows in $table (created_at).\n";
        }

    } catch (Exception $e) {
        echo "Error on $table: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
