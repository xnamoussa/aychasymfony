<?php
$pdo = new PDO('mysql:host=localhost;dbname=event_platform', 'root', '');
foreach (['equipements', 'delivery'] as $table) {
    $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
    echo "--- $table ---\n";
    echo $stmt->fetch(PDO::FETCH_ASSOC)['Create Table'] . "\n\n";
}
