<?php
declare(strict_types=1);

// public/generate_thumbs.php
// Regenerates cover thumbnails at a uniform pixel height.
// CLI usage:
//   php generate_thumbs.php [--height=240] [--force] [--dry-run] [--limit=10000]

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: text/plain; charset=utf-8');

$argv = $argv ?? [];
$height = 240;
$force  = false;
$dry_run = false;
$limit  = 100000;

foreach ($argv as $arg) {
  if (preg_match('/^--height=(\d{2,4})$/', $arg, $m)) {
    $height = max(20, min(2000, (int)$m[1]));
  } elseif ($arg === '--force') {
    $force = true;
  } elseif ($arg === '--dry-run') {
    $dry_run = true;
  } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
    $limit = max(1, (int)$m[1]);
  }
}

// Thumbnailer (height-based)
function make_thumb_by_height(string $src, string $dst, int $max_h = 240): bool {
  // Prefer Imagick
  if (class_exists('Imagick')) {
    try {
      $img = new Imagick($src);
      $img->setImageColorspace(Imagick::COLORSPACE_RGB);
      // Flatten alpha onto white
      $img->setBackgroundColor(new ImagickPixel('white'));
      $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
      // Keep aspect ratio; target height
      $img->thumbnailImage(0, $max_h); // width auto
      // Ensure directory exists
      @mkdir(dirname($dst), 0775, true);
      $ok = $img->writeImage($dst);
      $img->destroy();
      return (bool)$ok;
    } catch (Throwable $e) {
      // fall through to GD
    }
  }

  // GD fallback
  $info = @getimagesize($src);
  if (!$info) return false;
  [$w, $h] = $info;
  if ($w <= 0 || $h <= 0) return false;

  $mime = $info['mime'] ?? '';
  switch ($mime) {
    case 'image/jpeg': $im = @imagecreatefromjpeg($src); break;
    case 'image/png' : $im = @imagecreatefrompng($src);  break;
    case 'image/webp': $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : null; break;
    default: $im = null;
  }
  if (!$im) return false;

  $new_h = min($max_h, $h);
  $new_w = (int) round($w * $new_h / $h);

  $dst_im = imagecreatetruecolor($new_w, $new_h);
  // white background for any transparency
  $white = imagecolorallocate($dst_im, 255, 255, 255);
  imagefilledrectangle($dst_im, 0, 0, $new_w, $new_h, $white);
  imagealphablending($dst_im, true);

  imagecopyresampled($dst_im, $im, 0,0,0,0, $new_w, $new_h, $w, $h);

  @mkdir(dirname($dst), 0775, true);
  $ok = false;
  switch ($mime) {
    case 'image/jpeg': $ok = imagejpeg($dst_im, $dst, 85); break;
    case 'image/png' : $ok = imagepng($dst_im,  $dst, 6);  break;
    case 'image/webp': $ok = function_exists('imagewebp') ? imagewebp($dst_im, $dst, 85) : false; break;
  }
  imagedestroy($dst_im);
  imagedestroy($im);
  return (bool)$ok;
}

// Derive a "-thumb" filename next to the original
function thumb_path_from_cover(string $rel_cover): array {
  // $rel_cover like "uploads/123/cover.jpg" or "uploads/123/cover-abc.webp"
  $pi = pathinfo($rel_cover);
  $dir = $pi['dirname'] ?? '';
  $base = $pi['basename'] ?? '';
  $ext = strtolower($pi['extension'] ?? 'jpg');

  // insert "-thumb" before extension
  $name_without_ext = $pi['filename'] ?? 'cover';
  $thumb_name = $name_without_ext . '-thumb.' . $ext;

  $rel_thumb = ($dir !== '' ? ($dir . '/') : '') . $thumb_name;
  return [$rel_thumb, $ext];
}

$base = realpath(__DIR__) ?: __DIR__;
$uploads_base = realpath(__DIR__ . '/uploads') ?: (__DIR__ . '/uploads');

$pdo = pdo();

// Fetch candidates: books that have a cover, or have a legacy cover at uploads/<id>/cover.(jpg|png|webp)
$sql = "SELECT book_id, cover_image, cover_thumb FROM Books ORDER BY book_id DESC LIMIT :lim";
$st = $pdo->prepare($sql);
$st->bindValue(':lim', $limit, PDO::PARAM_INT);
$st->execute();

$count = 0;
$done  = 0;
$skipped = 0;
$errors = 0;

while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
  $count++;
  $id = (int)$row['book_id'];
  $rel_cover = $row['cover_image'];

  // If DB cover_image is empty, try legacy uploads/<id>/cover.*
  if (!$rel_cover) {
    foreach (['jpg','png','webp','jpeg'] as $ext) {
      $try_rel = "uploads/$id/cover.$ext";
      $try_abs = $base . '/' . $try_rel;
      if (is_file($try_abs)) { $rel_cover = $try_rel; break; }
    }
  }

  if (!$rel_cover) { $skipped++; continue; }

  $abs_cover = $base . '/' . $rel_cover;
  if (!is_file($abs_cover)) { 
    // try absolute from uploads_base if rel path somehow different
    $abs_cover2 = $uploads_base . '/' . basename($abs_cover);
    if (!is_file($abs_cover2)) { $skipped++; continue; }
    $abs_cover = $abs_cover2;
  }

  // Build intended thumb path
  [$rel_thumb] = thumb_path_from_cover($rel_cover);
  $abs_thumb = $base . '/' . $rel_thumb;

  $have_thumb_file = is_file($abs_thumb);
  $have_thumb_db   = (string)($row['cover_thumb'] ?? '') !== '';

  if ($have_thumb_file && $have_thumb_db && !$force) {
    $skipped++;
    continue;
  }

  // Ensure target dir exists
  @mkdir(dirname($abs_thumb), 0775, true);

  // Generate
  if ($dry_run) {
    echo "[DRY] id={$id} src={$rel_cover} -> thumb={$rel_thumb} (h={$height})\n";
    $done++;
    continue;
  }

  $ok = make_thumb_by_height($abs_cover, $abs_thumb, $height);
  if (!$ok) {
    $errors++;
    echo "[ERR]  id={$id} failed to write thumb for {$rel_cover}\n";
    continue;
  }

  // Update DB if needed or forced
  try {
    if ($force || !$have_thumb_db) {
      $upd = $pdo->prepare("UPDATE Books SET cover_thumb = :ct WHERE book_id = :id");
      $upd->execute([':ct' => $rel_thumb, ':id' => $id]);
    }
    $done++;
    echo "[OK]   id={$id} -> {$rel_thumb}\n";
  } catch (Throwable $e) {
    $errors++;
    echo "[ERR]  id={$id} DB update failed: " . $e->getMessage() . "\n";
  }
}

echo "\nSummary:\n";
echo " scanned : {$count}\n";
echo " generated: {$done}\n";
echo " skipped : {$skipped}\n";
echo " errors  : {$errors}\n";
