<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
$me = require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_fail('Method Not Allowed', 405);
    }

    $d = json_in();
    $user_id = (int)($d['user_id'] ?? $d['id'] ?? 0);
    if ($user_id <= 0) {
        json_fail('user_id is required', 400);
    }
    if ($user_id === (int)$me['uid']) {
        json_fail('Cannot delete the currently logged-in user', 400);
    }

    $pdo = pdo();
    $sel = $pdo->prepare('SELECT user_id, role FROM Users WHERE user_id = ? LIMIT 1');
    $sel->execute([$user_id]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        json_fail('User not found', 404);
    }

    if ((string)$row['role'] === 'admin' && count_admins($pdo) <= 1) {
        json_fail('Cannot delete the last admin', 400);
    }

    $del = $pdo->prepare('DELETE FROM Users WHERE user_id = ?');
    $del->execute([$user_id]);

    json_out([
        'ok' => true,
        'data' => [
            'deleted' => (int)$del->rowCount(),
        ],
    ]);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
