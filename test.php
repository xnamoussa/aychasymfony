<?php
$pdo = new PDO('mysql:host=localhost;port=3306;dbname=event_platform', 'root', '');
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);