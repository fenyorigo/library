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

    $pdo = pdo();
    $d = json_in();
    $author_id = (int)($d['author_id'] ?? 0);
    if ($author_id <= 0) {
        json_fail('Invalid author_id', 400);
    }

    $pdo->beginTransaction();
    $del_links = $pdo->prepare('DELETE FROM Books_Authors WHERE author_id = ?');
    $del_links->execute([$author_id]);

    $del = $pdo->prepare('DELETE FROM Authors WHERE author_id = ?');
    $del->execute([$author_id]);
    $pdo->commit();

    json_out([
        'ok' => true,
        'data' => [
            'id' => $author_id,
            'links_cleared' => $del_links->rowCount(),
            'affected_rows' => $del->rowCount(),
        ],
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_fail($e->getMessage(), 500);
}
