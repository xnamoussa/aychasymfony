<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents('doctrine_fix_final.sql');
$queries = array_filter(array_map('trim', explode(';', $sql)));

foreach ($queries as $query) {
    if (empty($query)) continue;
    try {
        $pdo->exec($query);
        echo "Executed query successfully.\n";
    } catch (Exception $e) {
        echo "Error executing query: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
