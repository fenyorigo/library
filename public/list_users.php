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
    $cols = ['user_id', 'username', 'role', 'is_active'];
    if (users_table_has_column($pdo, 'created_at')) $cols[] = 'created_at';
    if (users_table_has_column($pdo, 'last_login')) $cols[] = 'last_login';

    $sql = 'SELECT ' . implode(', ', $cols) . ' FROM Users ORDER BY username ASC';
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'user_id' => (int)$row['user_id'],
            'username' => (string)$row['username'],
            'role' => (string)$row['role'],
            'is_active' => (int)$row['is_active'],
            'created_at' => $row['created_at'] ?? null,
            'last_login' => $row['last_login'] ?? null,
        ];
    }

    json_out([
        'ok' => true,
        'data' => [
            'rows' => $out,
        ],
    ]);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
