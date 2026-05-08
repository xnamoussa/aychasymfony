<?php
$file = 'templates/admin_evenement/show.html.twig';
$lines = file($file);
unset($lines[78]);
file_put_contents($file, implode('', $lines));
echo "Line 79 removed.";
