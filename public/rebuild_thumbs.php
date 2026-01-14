<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

// Never leak notices/warnings into JSON
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

// --- helpers ---
function make_thumb(string $src, string $dst, int $max_h = 200): bool {
    // Prefer Imagick if available
    if (class_exists('Imagick')) {
        $img = new Imagick($src);
        // strip alpha to avoid weird backgrounds on JPEG
        $img->setImageColorspace(Imagick::COLORSPACE_RGB);
        $img->setBackgroundColor(new ImagickPixel('white'));
        if ($img->getImageAlphaChannel()) {
            $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        }
        $img->thumbnailImage(0, $max_h); // constrain by height
        $ok = $img->writeImage($dst);
        $img->destroy();
        return (bool)$ok;
    }

    // GD fallback
    $info = @getimagesize($src);
    if (!$info) return false;
    [$w,$h] = $info;
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $im = @imagecreatefromjpeg($src); $ext='jpg'; break;
        case 'image/png':  $im = @imagecreatefrompng($src);  $ext='png'; break;
        case 'image/webp': $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : null; $ext='webp'; break;
        default: $im = null; $ext = '';
    }
    if (!$im) return false;

    $new_h = min($max_h, $h);
    $new_w = (int) round($w * $new_h / $h);

    $dst_im = imagecreatetruecolor($new_w, $new_h);
    imagealphablending($dst_im, false);
    imagesavealpha($dst_im, true);
    imagecopyresampled($dst_im, $im, 0,0,0,0, $new_w,$new_h, $w,$h);

    $ok = false;
    switch ($ext) {
        case 'jpg':  $ok = imagejpeg($dst_im, $dst, 85); break;
        case 'png':  $ok = imagepng($dst_im,  $dst, 6);  break;
        case 'webp': $ok = function_exists('imagewebp') ? imagewebp($dst_im, $dst, 85) : false; break;
    }
    return (bool)$ok;
}

// --- params ---
$limit = max(0, (int)($_GET['limit'] ?? 200));   // 0 = no limit
$offset = max(0, (int)($_GET['offset'] ?? 0));
$max_h  = max(40, (int)($_GET['h'] ?? 200));      // min sensible height

$uploads_dir = realpath(__DIR__ . '/uploads') ?: (__DIR__ . '/uploads');

// --- scan uploads ---
$scanned = 0;
$updated = 0;
$skipped = 0;
$errors  = [];

try {
    $pdo = pdo();

    // Find book_id folders (numeric)
    $dirs = @scandir($uploads_dir) ?: [];
    $num_dirs = [];
    foreach ($dirs as $d) {
        if ($d === '.' || $d === '..') continue;
        if (!ctype_digit($d)) continue; // only numeric dirs
        $num_dirs[] = $d;
    }

    sort($num_dirs, SORT_NATURAL);
    $total_dirs = count($num_dirs);
    $batch = ($limit === 0) ? $num_dirs : array_slice($num_dirs, $offset, $limit);

    foreach ($batch as $d) {
        $book_id = (int)$d;
        $book_dir = $uploads_dir . DIRECTORY_SEPARATOR . $d;
        if (!is_dir($book_dir)) continue;

        $scanned++;
        if ($limit && $updated >= $limit) { $skipped++; continue; }

        // Detect an existing cover (deterministic or legacy random):
        // Prefer deterministic cover.ext if present, else search for legacy "cover-*.ext"
        $cover_path = null;
        $ext = null;

        foreach (['jpg','jpeg','png','webp'] as $e) {
            $p = $book_dir . "/cover.$e";
            if (is_file($p)) { $cover_path = $p; $ext = ($e === 'jpeg') ? 'jpg' : $e; break; }
        }

        if (!$cover_path) {
            // legacy random name (any cover-*.ext)
            $matches = glob($book_dir . "/cover-*.*");
            if ($matches && is_file($matches[0])) {
                $cover_path = $matches[0];
                $ext = strtolower(pathinfo($cover_path, PATHINFO_EXTENSION));
                if ($ext === 'jpeg') $ext = 'jpg';
            }
        }

        if (!$cover_path || !$ext) { $skipped++; continue; }

        // Build deterministic thumb path
        $thumb_path = $book_dir . "/cover-thumb.$ext";

        // Generate thumbnail if missing or if query forces re-gen (?re=1)
        $force = isset($_GET['re']) && $_GET['re'] !== '0';
        if (!$force && is_file($thumb_path)) { $skipped++; continue; }

        // Generate
        if (!make_thumb($cover_path, $thumb_path, $max_h)) {
            if (count($errors) < 25) $errors[] = "book #$book_id: failed to generate thumb";
            continue;
        }

        // Ensure DB.cover_thumb points to this file (create relative path)
        $rel_thumb = "uploads/$book_id/cover-thumb.$ext";

        try {
            $st = $pdo->prepare("UPDATE Books SET cover_thumb = :t WHERE book_id = :id");
            $st->execute([':t' => $rel_thumb, ':id' => $book_id]);
        } catch (Throwable $e) {
            if (count($errors) < 25) $errors[] = "book #$book_id: DB update error: " . $e->getMessage();
            // continue; thumb exists on disk anyway
        }

        $updated++;
    }

    json_out([
        'ok' => true,
        'data' => [
            'total_dirs' => $total_dirs,
            'offset' => $offset,
            'limit' => $limit,
            'scanned' => $scanned,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ],
    ]);

} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
