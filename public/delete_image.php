<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

// public/delete_image.php
error_reporting(E_ALL & ~E_DEPRECATED);   // log deprecations, don’t print them
ini_set('display_errors', '0');           // never echo errors in JSON APIs
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('Method Not Allowed', 405);
  }

  $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
  $type   = $_POST['type'] ?? '';
  if ($book_id <= 0 || !in_array($type, ['cover','back'], true)) {
    json_fail('Invalid parameters', 400);
  }

  $col_img = $type === 'cover' ? 'cover_image' : 'back_image';
  $col_thm = $type === 'cover' ? 'cover_thumb' : 'back_thumb';

  $pdo = pdo();
  $row = $pdo->prepare("SELECT $col_img AS img, $col_thm AS thm FROM Books WHERE book_id=?");
  $row->execute([$book_id]);
  $cur = $row->fetch(PDO::FETCH_ASSOC);
  if (!$cur) { json_fail('Book not found', 404); }

  $delete_paths = array_filter([$cur['img'] ?? null, $cur['thm'] ?? null]);

  // Remove files safely (only under public/uploads/)
  $base = realpath(__DIR__ . '/uploads') . DIRECTORY_SEPARATOR;
  foreach ($delete_paths as $rel) {
    $abs = realpath(__DIR__ . '/' . $rel);
    if ($abs !== false && strpos($abs, $base) === 0 && is_file($abs)) {
      @unlink($abs);
    }
  }

  // Clear DB columns
  $stmt = $pdo->prepare("UPDATE Books SET $col_img=NULL, $col_thm=NULL WHERE book_id=?");
  $stmt->execute([$book_id]);

  json_out([
    'ok' => true,
    'data' => [
      'id' => $book_id,
      'affected_rows' => $stmt->rowCount(),
      'type' => $type,
    ],
  ]);
} catch (Throwable $e) {
  json_fail($e->getMessage(), 500);
}
