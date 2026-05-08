<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
$stmt = $pdo->prepare('SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                       FROM information_schema.KEY_COLUMN_USAGE 
                       WHERE CONSTRAINT_NAME = ? AND TABLE_SCHEMA = ?');
$stmt->execute(['FK_3781EC10806F0F5C', 'event_platform']);
print_r($stmt->fetch(PDO::FETCH_ASSOC));
