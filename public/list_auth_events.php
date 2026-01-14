<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        json_fail('Method Not Allowed', 405);
    }

    $pdo = pdo();
    if (!auth_events_table_exists($pdo)) {
        json_out([
            'ok' => true,
            'data' => [
                'rows' => [],
                'meta' => [
                    'page' => 1,
                    'per_page' => 50,
                    'total' => 0,
                ],
            ],
        ]);
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = (int)($_GET['per'] ?? $_GET['per_page'] ?? 50);
    if ($per_page < 10) $per_page = 10;
    if ($per_page > 200) $per_page = 200;
    $offset = ($page - 1) * $per_page;

    $event_type = trim((string)($_GET['event_type'] ?? ''));
    $q = trim((string)($_GET['q'] ?? ''));
    $user_id = (int)($_GET['user_id'] ?? 0);

    $where = [];
    $params = [];

    if ($event_type !== '') {
        $where[] = 'event_type = ?';
        $params[] = $event_type;
    }
    if ($user_id > 0) {
        $where[] = 'user_id = ?';
        $params[] = $user_id;
    }
    if ($q !== '') {
        $where[] = '(username_snapshot LIKE ? OR event_type LIKE ? OR ip_address LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $count_sql = "SELECT COUNT(*) FROM AuthEvents $where_sql";
    $count = $pdo->prepare($count_sql);
    foreach ($params as $i => $param) {
        $count->bindValue($i + 1, $param);
    }
    $count->execute();
    $total = (int)$count->fetchColumn();

    $sql = "
        SELECT id, user_id, username_snapshot, event_type, ip_address, user_agent, details, created_at
        FROM AuthEvents
        $where_sql
        ORDER BY created_at DESC, id DESC
        LIMIT ? OFFSET ?
    ";
    $st = $pdo->prepare($sql);
    $i = 1;
    foreach ($params as $param) {
        $st->bindValue($i, $param);
        $i++;
    }
    $st->bindValue($i++, $per_page, PDO::PARAM_INT);
    $st->bindValue($i++, $offset, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $row) {
        $details = null;
        if (!empty($row['details'])) {
            $decoded = json_decode((string)$row['details'], true);
            $details = is_array($decoded) ? $decoded : null;
        }
        $out[] = [
            'id' => (int)$row['id'],
            'user_id' => isset($row['user_id']) ? (int)$row['user_id'] : null,
            'username_snapshot' => (string)$row['username_snapshot'],
            'event_type' => (string)$row['event_type'],
            'ip_address' => (string)$row['ip_address'],
            'user_agent' => $row['user_agent'] !== null ? (string)$row['user_agent'] : null,
            'details' => $details,
            'created_at' => (string)$row['created_at'],
        ];
    }

    json_out([
        'ok' => true,
        'data' => [
            'rows' => $out,
            'meta' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
            ],
        ],
    ]);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
