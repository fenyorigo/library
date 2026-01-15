<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = pdo();
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    if ($q === '') { json_out(['ok' => true, 'data' => []]); }

    // Accent/case-insensitive search:
    // If you have utf8mb4_hungarian_ci installed, prefer it.
    // Otherwise utf8mb4_general_ci is a decent fallback.
    $sql = "
        SELECT publisher_id AS id, name
          FROM Publishers
         WHERE name LIKE :q COLLATE utf8mb4_0900_ai_ci
         ORDER BY CASE WHEN name LIKE :prefix COLLATE utf8mb4_0900_ai_ci THEN 0 ELSE 1 END, name
         LIMIT 20
    ";
    try {
        $st = $pdo->prepare($sql);
        $st->execute([':q' => '%' . $q . '%', ':prefix' => $q . '%']);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // Fallback if collation not supported (e.g., MariaDB).
        $sql2 = "
            SELECT publisher_id AS id, name
              FROM Publishers
             WHERE name LIKE :q
             ORDER BY CASE WHEN name LIKE :prefix THEN 0 ELSE 1 END, name
             LIMIT 20
        ";
        $st = $pdo->prepare($sql2);
        $st->execute([':q' => '%' . $q . '%', ':prefix' => $q . '%']);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    }
    json_out(['ok' => true, 'data' => is_array($rows) ? $rows : []]);
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
