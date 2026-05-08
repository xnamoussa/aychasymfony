<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'event_platform' AND DATA_TYPE = 'bigint'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
