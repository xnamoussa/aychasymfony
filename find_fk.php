<?php
$content = file_get_contents('update.sql');
// convert to utf8 if it's utf16le
if (substr($content, 0, 2) === "\xFF\xFE") {
    $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
}
$lines = explode("\n", $content);
foreach ($lines as $line) {
    if (strpos($line, 'FK_4A26590571F7E88B') !== false) {
        echo trim($line) . "\n";
    }
}
