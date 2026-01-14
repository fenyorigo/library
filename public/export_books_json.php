<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_login();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors','0');

/**
 * Inputs
 * - q:    free-text search (tokenized, AND across tokens)
 * - sort: id|title|subtitle|series|publisher|year|authors|bookcase
 * - dir:  asc|desc
 *
 * NOTE: This export returns **all matching rows** (no pagination).
 */
$q      = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$sort_in = strtolower((string)($_GET['sort'] ?? 'title'));
$dir_in  = strtolower((string)($_GET['dir']  ?? 'asc'));
$dir_sql = ($dir_in === 'desc') ? 'DESC' : 'ASC';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$pdo = pdo();

/** Sorting whitelist */
$sortable = [
    'id'        => 'b.book_id',
    'title'     => 'b.title',
    'subtitle'  => 'b.subtitle',
    'series'    => 'b.series',
    'publisher' => 'p.name',
    'year'      => 'b.year_published',
    'authors'   => "CASE WHEN authors IS NULL THEN 1 ELSE 0 END, authors",
    'bookcase'  => 'pl.bookcase_no, pl.shelf_no',
];
$order_by = $sortable[$sort_in] ?? $sortable['title'];

/** WHERE conditions (exactly like list_books.php) */
$where_chunks = [];
$params = [];

if ($q !== '') {
    $tokens = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach ($tokens as $i => $tok) {
        $like = '%' . $tok . '%';

        $ph = [
            "t{$i}_title"    => $like,
            "t{$i}_subtitle" => $like,
            "t{$i}_series"   => $like,
            "t{$i}_isbn"     => $like,
            "t{$i}_lccn"     => $like,
            "t{$i}_pub"      => $like,
            "t{$i}_an"       => $like,
            "t{$i}_afn"      => $like,
            "t{$i}_aln"      => $like,
            "t{$i}_asn"      => $like,
        ];

        $where_chunks[] = "("
            . "b.title LIKE :t{$i}_title OR "
            . "b.subtitle LIKE :t{$i}_subtitle OR "
            . "b.series LIKE :t{$i}_series OR "
            . "b.isbn LIKE :t{$i}_isbn OR "
            . "b.lccn LIKE :t{$i}_lccn OR "
            . "p.name LIKE :t{$i}_pub OR "
            . "EXISTS ("
            . "  SELECT 1 FROM Books_Authors ba "
            . "  JOIN Authors a ON a.author_id = ba.author_id "
            . "  WHERE ba.book_id = b.book_id "
            . "    AND (a.name       LIKE :t{$i}_an "
            . "         OR a.first_name LIKE :t{$i}_afn "
            . "         OR a.last_name  LIKE :t{$i}_aln "
            . "         OR a.sort_name  LIKE :t{$i}_asn)"
            . ")"
            . ")";

        foreach ($ph as $k => $v) { $params[$k] = $v; }
    }
}

$where_sql = $where_chunks ? ('WHERE ' . implode(' AND ', $where_chunks)) : '';

/** COUNT */
$sql_count = "
  SELECT COUNT(*) AS c
  FROM Books b
  LEFT JOIN Publishers p ON p.publisher_id = b.publisher_id
  LEFT JOIN Placement  pl ON pl.placement_id = b.placement_id
  $where_sql
";
try {
    $stc = $pdo->prepare($sql_count);
    foreach ($params as $k => $v) { $stc->bindValue(':' . $k, $v, PDO::PARAM_STR); }
    $stc->execute();
    $total = (int)$stc->fetchColumn();
} catch (Throwable $e) {
    json_fail('COUNT failed: ' . $e->getMessage(), 500);
}

/** MAIN SELECT (all rows) */
$sql = "
SELECT
  b.book_id AS id,
  b.title, b.subtitle, b.series,
  b.year_published,
  b.isbn, b.lccn,
  b.loaned_to, b.loaned_date,
  b.cover_image,
  b.cover_image AS cover_thumb,
  p.name AS publisher,
  pl.bookcase_no, pl.shelf_no,
  (
    SELECT GROUP_CONCAT(DISTINCT
             NULLIF(
               TRIM(
                 COALESCE(
                   a.name,
                   CASE
                     WHEN a.is_hungarian = 1
                       THEN CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))
                     ELSE CONCAT(COALESCE(a.first_name,''),' ',COALESCE(a.last_name,''))
                   END
                 )
               ), ''
             )
             ORDER BY ba.author_ord SEPARATOR '; ')
      FROM Books_Authors ba
      JOIN Authors a ON a.author_id = ba.author_id
     WHERE ba.book_id = b.book_id
  ) AS authors,
  (
    SELECT GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR '; ')
      FROM Books_Subjects bs
      JOIN Subjects s ON s.subject_id = bs.subject_id
     WHERE bs.book_id = b.book_id
  ) AS subjects
FROM Books b
LEFT JOIN Publishers p ON p.publisher_id = b.publisher_id
LEFT JOIN Placement  pl ON pl.placement_id = b.placement_id
$where_sql
ORDER BY $order_by $dir_sql, b.book_id ASC
";
try {
    $st = $pdo->prepare($sql);
    foreach ($params as $k => $v) { $st->bindValue(':' . $k, $v, PDO::PARAM_STR); }
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    /** Add cover_filename per row */
    foreach ($rows as &$r) {
        if (!empty($r['cover_image'])) {
            $r['cover_filename'] = basename($r['cover_image']);
        } else {
            $r['cover_filename'] = null;
        }
    }

} catch (Throwable $e) {
    json_fail('SELECT failed: ' . $e->getMessage(), 500);
}

/** OUTPUT */
json_out([
    'ok' => true,
    'data' => $rows,
    'meta' => [
        'total' => $total,
    ],
]);
