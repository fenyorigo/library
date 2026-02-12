<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_fail('Method Not Allowed', 405);
    }

    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    if ($book_id <= 0) {
        json_fail('Invalid book_id', 400);
    }

    $thumb_max_w = isset($_POST['thumb_max_w']) ? (int)$_POST['thumb_max_w'] : 0;
    if ($thumb_max_w < 64 || $thumb_max_w > 4096) {
        $thumb_max_w = 200;
    }

    $pdo = pdo();
    $cover = process_cover_upload($pdo, $book_id, $_FILES['image'] ?? [], $thumb_max_w);

    json_out([
        'ok' => true,
        'data' => [
            'id' => $book_id,
            'affected_rows' => $cover['affected_rows'] ?? 0,
            'path' => $cover['path'] ?? null,
            'thumb' => $cover['thumb'] ?? null,
        ],
    ]);

} catch (Throwable $e) {
    $code = (int)$e->getCode();
    $status = ($code >= 400 && $code < 600) ? $code : 500;
    json_fail($e->getMessage(), $status);
}
