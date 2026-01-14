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

    $first = N($d['first_name'] ?? null);
    $last  = N($d['last_name'] ?? null);
    $is_hu  = array_key_exists('is_hungarian', $d) ? (int)!!$d['is_hungarian'] : 0;

    $name = N($d['name'] ?? null);
    $sort = N($d['sort_name'] ?? null);

    if ($name && !$first && !$last) {
        $parsed = parse_author_free_text($name, (bool)$is_hu);
        $first = $parsed[0] ?? null;
        $last = $parsed[1] ?? null;
    }

    if (!$name) {
        $name = format_author_display($first, $last, $is_hu);
    }
    if (!$sort) {
        $sort = format_author_sort($first, $last);
    }

    if (!$name && !$first && !$last) {
        json_fail('Author name or first/last is required', 400);
    }

    // Try to find existing author before inserting
    if ($sort) {
        $sel = $pdo->prepare('SELECT author_id FROM Authors WHERE sort_name = ? LIMIT 1');
        $sel->execute([$sort]);
        $id = $sel->fetchColumn();
        if ($id) {
            json_out([
                'ok' => true,
                'data' => [
                    'id' => (int)$id,
                    'affected_rows' => 0,
                    'name' => $name,
                    'first_name' => $first,
                    'last_name' => $last,
                    'sort_name' => $sort,
                    'is_hungarian' => $is_hu,
                ],
            ]);
        }
    }
    if ($name) {
        $sel = $pdo->prepare('SELECT author_id FROM Authors WHERE name = ? LIMIT 1');
        $sel->execute([$name]);
        $id = $sel->fetchColumn();
        if ($id) {
            json_out([
                'ok' => true,
                'data' => [
                    'id' => (int)$id,
                    'affected_rows' => 0,
                    'name' => $name,
                    'first_name' => $first,
                    'last_name' => $last,
                    'sort_name' => $sort,
                    'is_hungarian' => $is_hu,
                ],
            ]);
        }
    }

    $ins = $pdo->prepare('
        INSERT INTO Authors (name, first_name, last_name, sort_name, is_hungarian)
        VALUES (:name, :first, :last, :sort, :hu)
    ');
    $ins->execute([
        ':name'  => $name,
        ':first' => $first,
        ':last'  => $last,
        ':sort'  => $sort,
        ':hu'    => $is_hu,
    ]);

    $id = (int)$pdo->lastInsertId();
    json_out([
        'ok' => true,
        'data' => [
            'id' => $id,
            'affected_rows' => $ins->rowCount(),
            'name' => $name,
            'first_name' => $first,
            'last_name' => $last,
            'sort_name' => $sort,
            'is_hungarian' => $is_hu,
        ],
    ]);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
