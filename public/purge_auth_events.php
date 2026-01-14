<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_fail('Method Not Allowed', 405);
    }

    $d = json_in();
    $months = (int)($d['months'] ?? 0);
    if (!in_array($months, [6, 12], true)) {
        json_fail('Invalid months value', 400);
    }

    $pdo = pdo();
    if (!auth_events_table_exists($pdo)) {
        json_out(['ok' => true, 'data' => ['deleted' => 0]]);
    }

    $del = $pdo->prepare(
        "DELETE FROM AuthEvents WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? MONTH)"
    );
    $del->execute([$months]);
    $deleted = $del->rowCount();

    json_out([
        'ok' => true,
        'data' => [
            'deleted' => $deleted,
            'months' => $months,
        ],
    ]);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
