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
    $username = N($d['username'] ?? null);
    $role = N($d['role'] ?? null) ?? 'reader';
    $password = (string)($d['password'] ?? '');

    if (!$username) {
        json_fail('Username is required', 400);
    }
    if ($password === '') {
        json_fail('Password is required', 400);
    }
    if (!in_array($role, ['admin', 'reader'], true)) {
        json_fail('Invalid role', 400);
    }

    $errors = password_policy_errors($password, $username);
    if ($errors) {
        json_fail('Password does not meet policy', 400, ['details' => $errors]);
    }

    $pdo = pdo();
    $sel = $pdo->prepare('SELECT user_id FROM Users WHERE username = ? LIMIT 1');
    $sel->execute([$username]);
    if ($sel->fetchColumn()) {
        json_fail('Username already exists', 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($hash === false) {
        json_fail('Failed to hash password', 500);
    }

    $ins = $pdo->prepare('
        INSERT INTO Users (username, password_hash, role, is_active)
        VALUES (?, ?, ?, 1)
    ');
    $ins->execute([$username, $hash, $role]);

    json_out([
        'ok' => true,
        'data' => [
            'user_id' => (int)$pdo->lastInsertId(),
            'username' => $username,
            'role' => $role,
            'is_active' => 1,
        ],
    ], 201);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
