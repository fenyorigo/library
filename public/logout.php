<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

start_secure_session();
if (!empty($_SESSION['uid'])) {
    log_auth_event('logout', (int)$_SESSION['uid'], (string)($_SESSION['username'] ?? ''), [
        'role' => (string)($_SESSION['role'] ?? ''),
    ]);
}
$_SESSION = [];
session_destroy();

json_out(['ok' => true, 'data' => null]);
