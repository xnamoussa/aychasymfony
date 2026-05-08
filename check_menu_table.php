<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$stmt = $pdo->query("SHOW CREATE TABLE menu");
echo $stmt->fetch(PDO::FETCH_ASSOC)['Create Table'] . "\n";
