<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

$pdo = pdo();

$q = trim($_GET['q'] ?? '');
if ($q === '' || mb_strlen($q) < 2) { json_out(['ok' => true, 'data' => []]); }

$sql = "SELECT publisher_id AS id, name
        FROM Publishers
        WHERE name LIKE :q
        ORDER BY CASE WHEN name LIKE :prefix THEN 0 ELSE 1 END, name
        LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([':q' => "%$q%", ':prefix' => "$q%"]);
json_out(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
