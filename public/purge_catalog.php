<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$me = require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

/**
 * Remove a path recursively and track removed files/dirs.
 *
 * @return array{files:int,dirs:int}
 */
function remove_path_recursive(string $path): array {
    $files = 0;
    $dirs = 0;

    if (is_link($path) || is_file($path)) {
        if (@unlink($path)) {
            $files++;
        }
        return ['files' => $files, 'dirs' => $dirs];
    }

    if (!is_dir($path)) {
        return ['files' => $files, 'dirs' => $dirs];
    }

    $items = @scandir($path);
    if ($items !== false) {
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $child = $path . DIRECTORY_SEPARATOR . $item;
            $removed = remove_path_recursive($child);
            $files += $removed['files'];
            $dirs += $removed['dirs'];
        }
    }

    if (@rmdir($path)) {
        $dirs++;
    }

    return ['files' => $files, 'dirs' => $dirs];
}

/**
 * Remove all cover/thumbnail files under public/uploads.
 *
 * @return array{files:int,dirs:int}
 */
function wipe_uploads_content(string $uploads_root): array {
    $removed_files = 0;
    $removed_dirs = 0;

    $items = @scandir($uploads_root);
    if ($items === false) {
        throw new RuntimeException('Unable to scan uploads directory');
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $uploads_root . DIRECTORY_SEPARATOR . $item;
        $removed = remove_path_recursive($path);
        $removed_files += $removed['files'];
        $removed_dirs += $removed['dirs'];
    }

    return ['files' => $removed_files, 'dirs' => $removed_dirs];
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_fail('Method Not Allowed', 405);
    }

    $payload = json_in();
    $confirm = trim((string)($payload['confirm'] ?? ''));
    if ($confirm !== 'DELETE') {
        json_fail('Confirmation text mismatch', 400);
    }

    $pdo = pdo();
    $tables = [
        'Books_Authors',
        'Books_Subjects',
        'duplicate_review',
        'Books',
        'Authors',
        'Subjects',
        'Publishers',
        'Placement',
    ];

    $deleted_rows = [];

    $pdo->beginTransaction();
    foreach ($tables as $table) {
        $affected = $pdo->exec("DELETE FROM `{$table}`");
        if ($affected === false) {
            throw new RuntimeException("Failed to clear table {$table}");
        }
        $deleted_rows[$table] = (int)$affected;
    }
    $pdo->commit();

    foreach (['Books', 'Authors', 'Subjects', 'Publishers', 'Placement'] as $table) {
        $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
    }

    $uploads_root = realpath(__DIR__ . '/uploads') ?: (__DIR__ . '/uploads');
    if (!is_dir($uploads_root)) {
        throw new RuntimeException('Uploads directory not found');
    }
    $removed_uploads = wipe_uploads_content($uploads_root);

    log_auth_event('catalog_purge', (int)$me['uid'], (string)$me['username'], [
        'actor_username' => (string)$me['username'],
        'deleted_rows' => $deleted_rows,
        'deleted_upload_files' => $removed_uploads['files'],
        'deleted_upload_dirs' => $removed_uploads['dirs'],
    ]);

    json_out([
        'ok' => true,
        'data' => [
            'deleted_rows' => $deleted_rows,
            'deleted_upload_files' => $removed_uploads['files'],
            'deleted_upload_dirs' => $removed_uploads['dirs'],
        ],
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_auth_event('catalog_purge_failed', (int)$me['uid'], (string)$me['username'], [
        'actor_username' => (string)$me['username'],
        'error' => $e->getMessage(),
    ]);
    json_fail($e->getMessage(), 500);
}
