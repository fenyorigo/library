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
    if ($author_id <= 0) json_fail('Invalid author_id', 400);

    $name = N($d['name'] ?? null);
    $first = N($d['first_name'] ?? null);
    $last = N($d['last_name'] ?? null);
    $sort = N($d['sort_name'] ?? null);
    $is_hungarian = array_key_exists('is_hungarian', $d)
        ? (int)!!$d['is_hungarian']
        : null;

    if ($is_hungarian !== null) {
        $name = format_author_display($first, $last, $is_hungarian);
        $sort = format_author_sort($first, $last);
    }

    if ($name) {
        $dup = $pdo->prepare("SELECT author_id FROM Authors WHERE name = ? AND author_id <> ? LIMIT 1");
        $dup->execute([$name, $author_id]);
        $dup_id = (int)$dup->fetchColumn();
        if ($dup_id > 0) {
            $pdo->beginTransaction();
            // Remove links that would duplicate after merge
            $pdo->prepare("
                DELETE ba FROM Books_Authors ba
                JOIN Books_Authors bt ON bt.book_id = ba.book_id AND bt.author_id = ?
                WHERE ba.author_id = ?
            ")->execute([$dup_id, $author_id]);
            // Reassign remaining links
            $pdo->prepare("UPDATE Books_Authors SET author_id = ? WHERE author_id = ?")
                ->execute([$dup_id, $author_id]);
            // Delete old author row
            $pdo->prepare("DELETE FROM Authors WHERE author_id = ?")
                ->execute([$author_id]);
            $pdo->commit();

            json_out([
                'ok' => true,
                'data' => [
                    'id' => $author_id,
                    'merged_into' => $dup_id,
                    'affected_rows' => 1,
                ],
            ]);
        }
    }

    $upd = $pdo->prepare("
        UPDATE Authors
           SET name = :name,
               first_name = :first,
               last_name = :last,
               sort_name = :sort
         WHERE author_id = :id
    ");
    $upd->execute([
        ':name' => $name,
        ':first' => $first,
        ':last' => $last,
        ':sort' => $sort,
        ':id' => $author_id,
    ]);

    if ($is_hungarian !== null) {
        $upd2 = $pdo->prepare("UPDATE Authors SET is_hungarian = :hu WHERE author_id = :id");
        $upd2->execute([':hu' => $is_hungarian, ':id' => $author_id]);
    }

    json_out([
        'ok' => true,
        'data' => [
            'id' => $author_id,
            'affected_rows' => $upd->rowCount(),
        ],
    ]);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
