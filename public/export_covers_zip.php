<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

ini_set('memory_limit', '512M');
set_time_limit(600);
ignore_user_abort(true);

if (PHP_OS_FAMILY === 'Darwin') {
    $os_label = 'macos';
} elseif (PHP_OS_FAMILY === 'Linux') {
    $os_label = 'linux';
    $os_release = @file_get_contents('/etc/os-release');
    if ($os_release !== false) {
        if (preg_match('/^ID=([a-z0-9._-]+)$/mi', $os_release, $m)) {
            if (strtolower($m[1]) === 'fedora') {
                $os_label = 'fedora';
            }
        }
    }
} else {
    $os_label = strtolower(PHP_OS_FAMILY);
}

// Default to frontend package.json version when available.
$app_version = '';
$pkg_path = dirname(__DIR__) . '/frontend/package.json';
$pkg_raw = @file_get_contents($pkg_path);
if ($pkg_raw !== false) {
    $pkg = json_decode($pkg_raw, true);
    if (is_array($pkg) && !empty($pkg['version'])) {
        $app_version = 'v' . $pkg['version'];
    }
}

$uploads_dir = realpath(__DIR__ . '/uploads') ?: (__DIR__ . '/uploads');
$file_count = 0;
$errors = [];

if (!class_exists('ZipArchive')) {
    json_fail('ZipArchive not available in PHP runtime', 500);
}

$tmp_candidates = [sys_get_temp_dir(), '/tmp', $uploads_dir, __DIR__];
$tmp_root = '';
foreach ($tmp_candidates as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        $tmp_root = $dir;
        break;
    }
}
if ($tmp_root === '') {
    json_fail('No writable temp directory available for zip', 500);
}

$zip_path = rtrim($tmp_root, '/\\') . '/bookcatalog_covers_' . date('Ymd_His') . '.zip';
$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    error_log('export_covers_zip.php: Zip open failed for ' . $zip_path);
    json_fail('Zip open failed', 500);
}

if (is_dir($uploads_dir) && is_readable($uploads_dir)) {
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploads_dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $fileinfo) {
            if (!$fileinfo->isFile() || !$fileinfo->isReadable()) {
                continue;
            }
            $abs = $fileinfo->getPathname();
            $rel = substr($abs, strlen($uploads_dir));
            $rel = ltrim(str_replace('\\', '/', $rel), '/');
            if (!$zip->addFile($abs, 'uploads/' . $rel)) {
                $errors[] = 'zip add failed: ' . $rel;
            }
            $file_count++;
        }
    } catch (Throwable $e) {
        $errors[] = 'uploads scan failed: ' . $e->getMessage();
    }
} else {
    $errors[] = 'uploads directory is missing or not readable';
}

$generated_at = (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM);
$readme = <<<TXT
BookCatalog – Covers Backup
Generated: {$generated_at}

Includes:
- uploads/ (all files, preserving directory structure)
Notes:
- skipped files may indicate permission issues
TXT;
$zip->addFromString('README.txt', $readme);

if ($errors) {
    $zip->addFromString('errors.txt', implode("\n", $errors) . "\n");
}

$zip->close();

$timestamp = date('Ymd_His');
$suffix_parts = array_filter([$os_label, $app_version]);
$suffix = $suffix_parts ? '_' . implode('_', $suffix_parts) : '';
$export_name = "export_{$file_count}_covers_{$timestamp}{$suffix}.zip";
$export_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $export_name);

clearstatcache(true, $zip_path);
if (!is_file($zip_path) || !is_readable($zip_path)) {
    json_fail('Zip missing or unreadable', 500);
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $export_name . '"');
header('Content-Length: ' . filesize($zip_path));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
while (ob_get_level() > 0) {
    ob_end_clean();
}
$fh = fopen($zip_path, 'rb');
if ($fh === false) {
    json_fail('Failed to open zip for download', 500);
}
while (!feof($fh)) {
    $chunk = fread($fh, 1048576);
    if ($chunk === false) {
        break;
    }
    echo $chunk;
}
fclose($fh);

@unlink($zip_path);
