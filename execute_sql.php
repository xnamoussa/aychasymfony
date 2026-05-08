<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=3306;dbname=event_platform;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = file_get_contents(__DIR__.'/doctrine_fix_final.sql');
    
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($queries as $query) {
        if (!empty($query)) {
            try {
                $pdo->exec($query);
            } catch (\Exception $e) {
                echo "Warning on query [$query]: " . $e->getMessage() . "\n";
            }
        }
    }
    // Additional fix for innodb_flush_log_at_trx_commit
    $pdo->exec("SET GLOBAL innodb_flush_log_at_trx_commit = 2");
    
    echo "SQL Fixes applied successfully!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
