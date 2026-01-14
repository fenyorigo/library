<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
$me = require_login();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_fail('Method Not Allowed', 405);
    }

    $d = json_in();
    $current = (string)($d['current_password'] ?? $d['currentPassword'] ?? '');
    $new = (string)($d['new_password'] ?? $d['newPassword'] ?? '');

    if ($current === '' || $new === '') {
        json_fail('Current and new password are required', 400);
    }

    $pdo = pdo();
    $sel = $pdo->prepare('SELECT username, password_hash FROM Users WHERE user_id = ? LIMIT 1');
    $sel->execute([(int)$me['uid']]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($current, (string)$row['password_hash'])) {
        auth_failure_delay();
        json_fail('Invalid current password', 401);
    }

    $errors = password_policy_errors($new, (string)$row['username']);
    if ($errors) {
        json_fail('Password does not meet policy', 422, ['details' => $errors]);
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    if ($hash === false) {
        json_fail('Failed to hash password', 500);
    }

    $upd = $pdo->prepare('UPDATE Users SET password_hash = ?, force_password_change = 0 WHERE user_id = ?');
    $upd->execute([$hash, (int)$me['uid']]);
    session_regenerate_id(true);

    log_auth_event('password_change', (int)$me['uid'], (string)$me['username'], [
        'self_service' => true,
    ]);

    json_out(['ok' => true, 'message' => 'Password updated']);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
