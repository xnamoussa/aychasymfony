<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Disabling foreign key checks...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

$fixes = [
    // [Table, Column, NewType]
    ['payment_transaction', 'equipement_id', 'BIGINT'],
    ['delivery', 'equipement_id', 'BIGINT'],
    
    // User references (should be INT UNSIGNED)
    ['menu', 'created_by_id', 'INT UNSIGNED'],
    ['menu', 'updated_by_id', 'INT UNSIGNED'],
    ['reservation_maquillage', 'created_by_id', 'INT UNSIGNED'],
    ['reservation_maquillage', 'updated_by_id', 'INT UNSIGNED'],
    ['favori', 'user_id', 'INT UNSIGNED'],
    ['favori', 'created_by_id', 'INT UNSIGNED'],
    ['favori', 'updated_by_id', 'INT UNSIGNED'],
    ['payment_transaction', 'buyer_id', 'INT UNSIGNED'],
    ['payment_transaction', 'seller_id', 'INT UNSIGNED'],
    ['payment_transaction', 'created_by_id', 'INT UNSIGNED'],
    ['payment_transaction', 'updated_by_id', 'INT UNSIGNED'],
    ['face_data', 'created_by_id', 'INT UNSIGNED'],
    ['face_data', 'updated_by_id', 'INT UNSIGNED'],
    ['sponsor_feedback', 'created_by_id', 'INT UNSIGNED'],
    ['sponsor_feedback', 'updated_by_id', 'INT UNSIGNED'],
    ['delivery', 'acheteur_id', 'INT UNSIGNED'],
    ['delivery', 'created_by_id', 'INT UNSIGNED'],
    ['delivery', 'updated_by_id', 'INT UNSIGNED'],
    ['equipements', 'owner_id', 'INT UNSIGNED'],
    ['abonnement', 'user_id', 'INT UNSIGNED'],
    ['participation', 'user_id', 'INT UNSIGNED'],
    ['ticket', 'user_id', 'INT UNSIGNED'],
];

foreach ($fixes as $fix) {
    list($table, $col, $type) = $fix;
    try {
        echo "Fixing $table.$col -> $type...\n";
        // Check if table and column exist first
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($stmt->fetch()) {
            $pdo->exec("ALTER TABLE `$table` MODIFY `$col` $type NULL");
        } else {
            echo "Skipping $table.$col (not found)\n";
        }
    } catch (Exception $e) {
        echo "Error on $table.$col: " . $e->getMessage() . "\n";
    }
}

echo "Enabling foreign key checks...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
echo "Done.\n";
