<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

$pdo = pdo();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $orph_authors = $pdo->query("
        SELECT a.author_id, a.name, a.first_name, a.last_name, a.sort_name, a.is_hungarian
        FROM Authors a
        LEFT JOIN Books_Authors ba ON ba.author_id = a.author_id
        WHERE ba.author_id IS NULL
        ORDER BY a.author_id DESC
        LIMIT 500
    ")->fetchAll(PDO::FETCH_ASSOC);

    $orph_publishers = $pdo->query("
        SELECT p.publisher_id, p.name
        FROM Publishers p
        LEFT JOIN Books b ON b.publisher_id = p.publisher_id
        WHERE b.publisher_id IS NULL
        ORDER BY p.publisher_id DESC
        LIMIT 500
    ")->fetchAll(PDO::FETCH_ASSOC);

    $orph_links = $pdo->query("
        SELECT ba.book_id, ba.author_id,
               b.title AS book_title,
               a.name AS author_name
        FROM Books_Authors ba
        LEFT JOIN Books b ON b.book_id = ba.book_id
        LEFT JOIN Authors a ON a.author_id = ba.author_id
        WHERE b.book_id IS NULL OR a.author_id IS NULL
        ORDER BY ba.book_id DESC, ba.author_id DESC
        LIMIT 500
    ")->fetchAll(PDO::FETCH_ASSOC);

    json_out([
        'ok' => true,
        'data' => [
            'orphan_authors' => $orph_authors,
            'orphan_publishers' => $orph_publishers,
            'orphan_links' => $orph_links,
        ],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('Method Not Allowed', 405);
}

$d = json_in();
$action = $d['action'] ?? '';

try {
    switch ($action) {
        case 'delete_author': {
            $author_id = (int)($d['author_id'] ?? 0);
            if ($author_id <= 0) json_fail('Invalid author_id', 400);

            $has_links = $pdo->prepare('SELECT 1 FROM Books_Authors WHERE author_id = ? LIMIT 1');
            $has_links->execute([$author_id]);
            if ($has_links->fetchColumn()) json_fail('Author still linked to books', 409);

            $del = $pdo->prepare('DELETE FROM Authors WHERE author_id = ?');
            $del->execute([$author_id]);
            json_out([
                'ok' => true,
                'data' => [
                    'id' => $author_id,
                    'affected_rows' => $del->rowCount(),
                ],
            ]);
        }
        case 'delete_publisher': {
            $publisher_id = (int)($d['publisher_id'] ?? 0);
            if ($publisher_id <= 0) json_fail('Invalid publisher_id', 400);

            $has_links = $pdo->prepare('SELECT 1 FROM Books WHERE publisher_id = ? LIMIT 1');
            $has_links->execute([$publisher_id]);
            if ($has_links->fetchColumn()) json_fail('Publisher still linked to books', 409);

            $del = $pdo->prepare('DELETE FROM Publishers WHERE publisher_id = ?');
            $del->execute([$publisher_id]);
            json_out([
                'ok' => true,
                'data' => [
                    'id' => $publisher_id,
                    'affected_rows' => $del->rowCount(),
                ],
            ]);
        }
        case 'update_author': {
            $author_id = (int)($d['author_id'] ?? 0);
            if ($author_id <= 0) json_fail('Invalid author_id', 400);

            $is_hungarian = array_key_exists('is_hungarian', $d)
                ? (int)!!$d['is_hungarian']
                : null;

            $name = N($d['name'] ?? null);
            $first = N($d['first_name'] ?? null);
            $last = N($d['last_name'] ?? null);
            $sort = N($d['sort_name'] ?? null);

            if ($is_hungarian !== null) {
                $name = format_author_display($first, $last, $is_hungarian);
                $sort = format_author_sort($first, $last);
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
        }
        case 'update_publisher': {
            $publisher_id = (int)($d['publisher_id'] ?? 0);
            if ($publisher_id <= 0) json_fail('Invalid publisher_id', 400);

            $name = N($d['name'] ?? null);
            $upd = $pdo->prepare("UPDATE Publishers SET name = :name WHERE publisher_id = :id");
            $upd->execute([':name' => $name, ':id' => $publisher_id]);
            json_out([
                'ok' => true,
                'data' => [
                    'id' => $publisher_id,
                    'affected_rows' => $upd->rowCount(),
                ],
            ]);
        }
        case 'delete_link': {
            $book_id = (int)($d['book_id'] ?? 0);
            $author_id = (int)($d['author_id'] ?? 0);
            if ($book_id <= 0 || $author_id <= 0) json_fail('Invalid book_id/author_id', 400);

            $del = $pdo->prepare('DELETE FROM Books_Authors WHERE book_id = ? AND author_id = ?');
            $del->execute([$book_id, $author_id]);
            json_out([
                'ok' => true,
                'data' => [
                    'book_id' => $book_id,
                    'author_id' => $author_id,
                    'affected_rows' => $del->rowCount(),
                ],
            ]);
        }
        case 'reassign_link': {
            $book_id = (int)($d['book_id'] ?? 0);
            $author_id = (int)($d['author_id'] ?? 0);
            $new_author_id = (int)($d['new_author_id'] ?? 0);
            if ($book_id <= 0 || $author_id <= 0 || $new_author_id <= 0) {
                json_fail('Invalid book_id/author_id/new_author_id', 400);
            }

            $pdo->beginTransaction();
            $exists = $pdo->prepare('SELECT 1 FROM Authors WHERE author_id = ? LIMIT 1');
            $exists->execute([$new_author_id]);
            if (!$exists->fetchColumn()) {
                $pdo->rollBack();
                json_fail('Target author not found', 404);
            }

            $upd = $pdo->prepare('UPDATE Books_Authors SET author_id = ? WHERE book_id = ? AND author_id = ?');
            $upd->execute([$new_author_id, $book_id, $author_id]);
            $pdo->commit();
            json_out([
                'ok' => true,
                'data' => [
                    'book_id' => $book_id,
                    'author_id' => $author_id,
                    'new_author_id' => $new_author_id,
                    'affected_rows' => $upd->rowCount(),
                ],
            ]);
        }
        default:
            json_fail('Unknown action', 400);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_fail($e->getMessage(), 500);
}
