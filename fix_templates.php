<?php
$dirs = [
    'templates/restaurant',
    'templates/abonnement',
    'templates/menu',
    'templates/ticket',
    'templates/repas_detaille',
    'templates/participation',
    'templates/participation_restaurant'
];

foreach ($dirs as $dir) {
    if (!is_dir(__DIR__ . '/' . $dir)) continue;
    $files = scandir(__DIR__ . '/' . $dir);
    foreach ($files as $file) {
        if (str_ends_with($file, '.html.twig')) {
            $path = __DIR__ . '/' . $dir . '/' . $file;
            $content = file_get_contents($path);
            
            $content = str_replace("{% extends 'admin/base_admin.html.twig' %}", "{% extends 'base.html.twig' %}", $content);
            $content = str_replace("{% block admin_content %}", "{% block body %}", $content);
            $content = str_replace("{% block admin_title %}", "{% block page_title %}", $content);
            
            // Just to be safe for new/edit templates as well
            file_put_contents($path, $content);
        }
    }
}
echo "Templates updated to base.html.twig successfully.\n";
