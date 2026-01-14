<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    if ($items === false) return;
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $it;
        if (is_dir($path)) rrmdir($path);
        else @unlink($path);
    }
    @rmdir($dir);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_fail('Method Not Allowed', 405);
    }

    // accept id from JSON, POST, or GET
    $json = json_in();
    $id = null;

    if (isset($json['id']))           $id = (int)$json['id'];
    elseif (isset($_POST['id']))      $id = (int)$_POST['id'];
    elseif (isset($_GET['id']))       $id = (int)$_GET['id'];

    if (!$id || $id <= 0) {
        json_fail('Invalid or missing id', 400);
    }

    $pdo = pdo();
    $pdo->beginTransaction();

    // (Optional) check existence first
    $chk = $pdo->prepare('SELECT book_id, cover_image, cover_thumb FROM Books WHERE book_id = ?');
    $chk->execute([$id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        // treat as idempotent delete
        $pdo->commit();
        json_out([
            'ok' => true,
            'data' => [
                'id' => $id,
                'affected_rows' => 0,
            ],
            'message' => 'Not found (already deleted)',
        ]);
    }

    // If you do NOT have FK ON DELETE CASCADE, manually clear links:
    $pdo->prepare('DELETE FROM Books_Authors  WHERE book_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM Books_Subjects WHERE book_id=?')->execute([$id]);

    // Delete the book row
    $del = $pdo->prepare('DELETE FROM Books WHERE book_id=?');
    $del->execute([$id]);
    $deleted = $del->rowCount();

    $pdo->commit();

    // Delete uploads/<id> directory (covers)
    $uploads_base = realpath(__DIR__ . '/uploads') ?: (__DIR__ . '/uploads');
    $book_dir = $uploads_base . DIRECTORY_SEPARATOR . $id;
    if (strpos(realpath($book_dir) ?: $book_dir, $uploads_base) === 0) {
        rrmdir($book_dir);
    }

    json_out([
        'ok' => true,
        'data' => [
            'id' => $id,
            'affected_rows' => $deleted,
        ],
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    json_fail($e->getMessage(), 500);
}
