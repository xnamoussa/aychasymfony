<?php
$content = file_get_contents('update.sql');
if (substr($content, 0, 2) === "\xFF\xFE") {
    $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
}
$lines = explode("\n", $content);
foreach ($lines as $line) {
    if (strpos($line, 'FK_3781EC10806F0F5C') !== false) {
        echo trim($line) . "\n";
    }
}
