<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

// simple thumb helper (Imagick preferred, GD fallback)
function make_thumb($src, $dst, $max_w = 200) {
    $max_w = max(1, (int)$max_w);

    // Try Imagick first
    if (class_exists('Imagick')) {
        try {
            $img = new Imagick();
            $img->readImage($src);

            // Validate geometry early
            $w = $img->getImageWidth();
            $h = $img->getImageHeight();
            if ($w < 1 || $h < 1) {
                $img->clear(); $img->destroy();
                throw new RuntimeException("Invalid image geometry (w={$w}, h={$h})");
            }

            // Normalize colorspace / alpha
            $img->setImageColorspace(Imagick::COLORSPACE_RGB);
            $img->setBackgroundColor(new ImagickPixel('white'));
            if (method_exists($img, 'setImageAlphaChannel')) {
                $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            }

            // Do not upscale small images
            if ($w <= $max_w) {
                $ok = $img->writeImage($dst);
                $img->clear(); $img->destroy();
                return (bool)$ok;
            }

            // Best-fit resize (keeps aspect)
            $img->thumbnailImage($max_w, 0, true);
            $ok = $img->writeImage($dst);
            $img->clear(); $img->destroy();
            return (bool)$ok;

        } catch (Throwable $e) {
            // Imagick failed → fall back to GD below
        }
    }

    // GD fallback
    $info = @getimagesize($src);
    if (!$info) return false;

    [$w, $h] = $info;
    if ($w < 1 || $h < 1) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $im = imagecreatefromjpeg($src); break;
        case 'image/png':  $im = imagecreatefrompng($src);  break;
        case 'image/webp': $im = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($src) : null; break;
        default: $im = null;
    }
    if (!$im) return false;

    if ($w <= $max_w) {
        return @copy($src, $dst);
    }

    $new_w = min($max_w, $w);
    $new_h = (int) round($h * $new_w / $w);

    $dst_im = imagecreatetruecolor($new_w, $new_h);
    imagealphablending($dst_im, false);
    imagesavealpha($dst_im, true);
    imagecopyresampled($dst_im, $im, 0, 0, 0, 0, $new_w, $new_h, $w, $h);

    $ok = false;
    switch ($mime) {
        case 'image/jpeg': $ok = imagejpeg($dst_im, $dst, 85); break;
        case 'image/png':  $ok = imagepng($dst_im,  $dst, 6);  break;
        case 'image/webp': $ok = function_exists('imagewebp') ? imagewebp($dst_im, $dst, 85) : false; break;
    }
    return $ok;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_fail('Method Not Allowed', 405);
    }

    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    if ($book_id <= 0) {
        json_fail('Invalid book_id', 400);
    }

    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        json_fail('No file uploaded or upload error', 400);
    }

    // validate mime
    $tmp_path = $_FILES['image']['tmp_name'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($tmp_path);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($allowed[$mime])) {
        json_fail('Unsupported file type', 415);
    }
    if ($_FILES['image']['size'] > 10*1024*1024) {
        json_fail('File too large', 413);
    }

    $pdo = pdo();

    // paths
    $base_dir = __DIR__ . '/uploads/' . $book_id;
    if (!is_dir($base_dir) && !mkdir($base_dir, 0775, true)) {
        throw new RuntimeException('Unable to create upload directory');
    }

    // remove any existing deterministic or legacy cover files
    foreach (glob($base_dir . "/cover*.*") ?: [] as $old) { @unlink($old); }
    foreach (glob($base_dir . "/cover-thumb*.*") ?: [] as $old) { @unlink($old); }

    // deterministic names
    $ext      = $allowed[$mime];
    $cover_fs  = $base_dir . "/cover.$ext";
    $thumb_fs  = $base_dir . "/cover-thumb.$ext";

    // move upload
    if (!move_uploaded_file($tmp_path, $cover_fs)) {
        throw new RuntimeException('Failed to move uploaded file');
    }

    // build thumb (200px default)
    $thumb_ok = make_thumb($cover_fs, $thumb_fs, 200);

    // relative paths for DB
    $rel_img = 'uploads/' . $book_id . '/cover.' . $ext;
    $rel_thm = $thumb_ok ? ('uploads/' . $book_id . '/cover-thumb.' . $ext) : null;

    // update DB — hard-code columns (we dropped back_* columns)
    $upd = $pdo->prepare("UPDATE Books SET cover_image = ?, cover_thumb = ? WHERE book_id = ?");
    $upd->execute([$rel_img, $rel_thm, $book_id]);

    json_out([
        'ok' => true,
        'data' => [
            'id' => $book_id,
            'affected_rows' => $upd->rowCount(),
            'path' => $rel_img,
            'thumb' => $rel_thm,
        ],
    ]);

} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
