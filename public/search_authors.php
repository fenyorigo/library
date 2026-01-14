<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = pdo();

    $q = trim($_GET['q'] ?? '');
    $len = function_exists('mb_strlen') ? mb_strlen($q) : strlen($q);
    if ($q === '' || $len < 2) {
        json_out(['ok' => true, 'data' => []]);
    }

    $has_hu = false;
    try {
        $col = $pdo->query("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'Authors'
              AND column_name = 'is_hungarian'
            LIMIT 1
        ")->fetchColumn();
        $has_hu = (bool)$col;
    } catch (Throwable $e) {
        $has_hu = false;
    }

    if ($has_hu) {
        $display_expr = "COALESCE(NULLIF(TRIM(name),''),
                                 NULLIF(TRIM(CASE WHEN is_hungarian = 1
                                      THEN CONCAT(COALESCE(last_name,''),' ',COALESCE(first_name,''))
                                      ELSE CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) END), ''),
                                 NULLIF(TRIM(sort_name),''))";
    } else {
        $display_expr = "COALESCE(NULLIF(TRIM(name),''),
                                 NULLIF(TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))), ''),
                                 NULLIF(TRIM(sort_name),''))";
    }

    $sql = "SELECT author_id AS id, $display_expr AS name
            FROM Authors
            WHERE (
              name LIKE ? COLLATE utf8mb4_0900_ai_ci OR
              first_name LIKE ? COLLATE utf8mb4_0900_ai_ci OR
              last_name LIKE ? COLLATE utf8mb4_0900_ai_ci OR
              sort_name LIKE ? COLLATE utf8mb4_0900_ai_ci
            )
            ORDER BY CASE WHEN $display_expr LIKE ? COLLATE utf8mb4_0900_ai_ci THEN 0 ELSE 1 END,
                     $display_expr COLLATE utf8mb4_0900_ai_ci
            LIMIT 20";
    try {
        $stmt = $pdo->prepare($sql);
        $like = "%$q%";
        $prefix = "$q%";
        $stmt->execute([$like, $like, $like, $like, $prefix]);
        json_out(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Throwable $e) {
        // Fallback if collation not supported
        $sql2 = "SELECT author_id AS id, $display_expr AS name
                 FROM Authors
                 WHERE (name LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR sort_name LIKE ?)
                 ORDER BY CASE WHEN $display_expr LIKE ? THEN 0 ELSE 1 END, $display_expr
                 LIMIT 20";
        $stmt = $pdo->prepare($sql2);
        $like = "%$q%";
        $prefix = "$q%";
        $stmt->execute([$like, $like, $like, $like, $prefix]);
        json_out(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
