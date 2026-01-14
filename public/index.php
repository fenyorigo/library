<?php

declare(strict_types=1);

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

// Strong no-cache so the browser doesn’t keep an old bundle
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Add cache-busting query params to built assets based on file mtime.
$html = @file_get_contents(__DIR__ . '/dist/index.html');
if ($html === false) {
    http_response_code(500);
    echo 'Missing dist/index.html';
    exit;
}

$html = preg_replace_callback(
    '#(/dist/(?:assets|js|css)/[^"\']+)#',
    function (array $m): string {
        $rel = $m[1];
        $path = __DIR__ . $rel;
        $ver = is_file($path) ? (string)filemtime($path) : '';
        if ($ver === '') return $rel;
        return $rel . '?v=' . $ver;
    },
    $html
);

echo $html;
exit;
