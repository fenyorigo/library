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

    $role = array_key_exists('role', $d) ? N($d['role']) : null;
    $is_active = array_key_exists('is_active', $d) ? (int)!!$d['is_active'] : null;

    if ($role !== null && !in_array($role, ['admin', 'reader'], true)) {
        json_fail('Invalid role', 400);
    }

    if ($role === null && $is_active === null) {
        json_fail('Nothing to update', 400);
    }

    $pdo = pdo();
    $sel = $pdo->prepare('SELECT user_id, username, role, is_active FROM Users WHERE user_id = ? LIMIT 1');
    $sel->execute([$user_id]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        json_fail('User not found', 404);
    }

    $prev_role = (string)$row['role'];
    $prev_active = (int)$row['is_active'];

    $next_role = $role ?? (string)$row['role'];
    $next_active = $is_active ?? (int)$row['is_active'];

    $current_active_admin = ((string)$row['role'] === 'admin') && ((int)$row['is_active'] === 1);
    $next_active_admin = ($next_role === 'admin') && ((int)$next_active === 1);
    if ($current_active_admin && !$next_active_admin) {
        if (count_active_admins($pdo) <= 1) {
            json_fail('Cannot disable the last active admin', 400);
        }
    }

    $fields = [];
    $params = [];
    if ($role !== null) {
        $fields[] = 'role = ?';
        $params[] = $next_role;
    }
    if ($is_active !== null) {
        $fields[] = 'is_active = ?';
        $params[] = (int)$next_active;
    }

    if ($fields) {
        $params[] = $user_id;
        $sql = 'UPDATE Users SET ' . implode(', ', $fields) . ' WHERE user_id = ?';
        $upd = $pdo->prepare($sql);
        $upd->execute($params);
    }

    if ($prev_role !== $next_role) {
        log_auth_event('role_change', $user_id, (string)$row['username'], [
            'actor_user_id' => (int)$me['uid'],
            'actor_username' => (string)$me['username'],
            'previous_role' => $prev_role,
            'new_role' => $next_role,
        ]);
    }

    if ($prev_active !== (int)$next_active) {
        $event_type = ((int)$next_active === 1) ? 'user_enabled' : 'user_disabled';
        log_auth_event($event_type, $user_id, (string)$row['username'], [
            'actor_user_id' => (int)$me['uid'],
            'actor_username' => (string)$me['username'],
            'previous_active' => $prev_active,
            'new_active' => (int)$next_active,
        ]);
    }

    json_out([
        'ok' => true,
        'data' => [
            'user_id' => $user_id,
            'role' => $next_role,
            'is_active' => (int)$next_active,
        ],
    ]);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
