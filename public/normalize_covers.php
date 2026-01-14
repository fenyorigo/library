<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require_admin();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = pdo();

    // params: ?dry=1 (no writes), &re=1 (force rethumb), &h=240 (thumb height)
    $dry  = isset($_GET['dry']) && $_GET['dry'] !== '0';
    $re   = isset($_GET['re'])  && $_GET['re']  !== '0';
    $h    = isset($_GET['h'])   ? max(60, (int)$_GET['h']) : 200;

    // Allow CLI flags as well (php normalize_covers.php --dry-run --re --h=240)
    if (PHP_SAPI === 'cli') {
        $opts = @getopt('', ['dry-run::','re::','h::','height::']);
        if (isset($opts['dry-run'])) $dry = true;           // presence implies true
        if (isset($opts['re']))      $re  = true;
        if (isset($opts['h']))       $h   = max(60, (int)$opts['h']);
        if (isset($opts['height']))  $h   = max(60, (int)$opts['height']);
    }

    $base = realpath(__DIR__ . '/uploads');
    if ($base === false) throw new RuntimeException('uploads/ not found');
    $base = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // helper: safe path
    $safe = function(string $p) use ($base): string {
        $rp = realpath($p);
        if ($rp === false) $rp = $p; // may not exist yet (targets)
        if (strpos($rp, $base) !== 0) throw new RuntimeException("Path escapes uploads/: $p");
        return $rp;
    };

    // simple thumb (uses your make_thumb if present)
    if (!function_exists('make_thumb')) {
        function make_thumb($src,$dst,$max_w=200) {
            if (class_exists('Imagick')) {
                $im = new Imagick($src);
                $im->setImageColorspace(Imagick::COLORSPACE_RGB);
                $im->setBackgroundColor(new ImagickPixel('white'));
                $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);

                // Do NOT upscale: if image is already ≤ maxW, just copy
                if ($im->getImageWidth() <= $max_w) {
                    // Write out as-is
                    $im->writeImage($dst);
                    $im->destroy();
                    return true;
                }

                // Downscale only (keep aspect, bestfit)
                $im->thumbnailImage($max_w, 0, true);
                $im->writeImage($dst);
                $im->destroy();
                return true;
            }
            $info = @getimagesize($src); if (!$info) return false;
            [$w,$h] = $info; $mime = $info['mime'];

            // Do NOT upscale small sources
            if ($w <= $max_w) {
                // Copy the original to dst without resizing
                return @copy($src, $dst);
            }

            switch ($mime) {
                case 'image/jpeg': $im=imagecreatefromjpeg($src); break;
                case 'image/png':  $im=imagecreatefrompng($src);  break;
                case 'image/webp': $im=function_exists('imagecreatefromwebp')?imagecreatefromwebp($src):null; break;
                default: $im=null;
            }
            if (!$im) return false;
            $new_w = min($max_w,$w); $new_h = (int)round($h*$new_w/$w);
            $dst_im = imagecreatetruecolor($new_w,$new_h);
            imagealphablending($dst_im,false); imagesavealpha($dst_im,true);
            imagecopyresampled($dst_im,$im,0,0,0,0,$new_w,$new_h,$w,$h);
            $ok=false;
            switch ($mime) {
                case 'image/jpeg': $ok=imagejpeg($dst_im,$dst,85); break;
                case 'image/png':  $ok=imagepng($dst_im,$dst,6);  break;
                case 'image/webp': $ok=function_exists('imagewebp')?imagewebp($dst_im,$dst,85):false; break;
            }
            return $ok;
        }
    }

    $scanned=0; $renamed=0; $thumbs=0; $deleted=0; $updated=0; $skipped=0; $notes=[];

    // find book dirs: uploads/<id> where <id> is numeric
    $dirs = glob($base . '*', GLOB_ONLYDIR) ?: [];
    foreach ($dirs as $dir) {
        $bn = basename($dir);
        if (!ctype_digit($bn)) continue;
        $book_id = (int)$bn;
        $scanned++;

        // collect cover candidates
        $cands = [];
        foreach (['jpg','jpeg','png','webp'] as $ext) {
            // deterministic names
            $p1 = "$dir/cover.$ext";      if (is_file($p1)) $cands[] = $p1;
            // legacy random names
            foreach (glob("$dir/cover-*.$ext") ?: [] as $p) { if (is_file($p)) $cands[] = $p; }
        }
        if (!$cands) { $skipped++; continue; }

        // choose "best" = prefer deterministic; else most recent mtime
        $target_ext = null; $chosen = null;
        foreach ($cands as $p) {
            if (preg_match('~/(cover\.(jpg|jpeg|png|webp))$~i',$p,$m)) { $chosen=$p; $target_ext=strtolower($m[2]); break; }
        }
        if (!$chosen) {
            usort($cands, fn($a,$b)=>filemtime($b)<=>filemtime($a));
            $chosen = $cands[0];
            $target_ext = strtolower(pathinfo($chosen, PATHINFO_EXTENSION));
        }

        $det  = "$dir/cover.$target_ext";
        $deth = "$dir/cover-thumb.$target_ext";

        // rename/move if chosen is not the deterministic name
        if (realpath($chosen) !== realpath($det)) {
            if (!$dry) {
                // ensure no stale deterministic file to avoid clobber surprises
                if (is_file($det)) @unlink($det);
                if (!@rename($chosen, $det)) throw new RuntimeException("Rename failed: $chosen → $det");
            }
            $renamed++;
        }

        // rebuild thumb if requested, or if missing/wrong ext
        $need_thumb = $re || !is_file($deth);
        if ($need_thumb) {
            if (!$dry) {
                // cleanup other cover-thumb* to keep it clean
                foreach (glob("$dir/cover-thumb.*") ?: [] as $oldt) { if ($oldt !== $deth) @unlink($oldt); }
                if (!make_thumb($det, $deth, $h)) throw new RuntimeException("Thumb failed for $det");
            }
            $thumbs++;
        }

        // remove legacy random cover-*.* and old thumbs
        foreach (glob("$dir/cover-*.*") ?: [] as $old) {
            if (preg_match('~/(cover-thumb\.)~i',$old)) continue; // handled separately above
            if (basename($old) === basename($det)) continue;
            if (!$dry) @unlink($old);
            $deleted++;
        }
        foreach (glob("$dir/cover-thumb-*.*") ?: [] as $oldt) {
            if (basename($oldt) === basename($deth)) continue;
            if (!$dry) @unlink($oldt);
            $deleted++;
        }

        // update DB paths to deterministic names (relative)
        $rel  = 'uploads/' . $book_id . '/' . basename($det);
        $relh = 'uploads/' . $book_id . '/' . basename($deth);

        if (!$dry) {
            $st = $pdo->prepare("UPDATE Books SET cover_image = ?, cover_thumb = ? WHERE book_id = ?");
            $st->execute([$rel, $relh, $book_id]);
            $updated++;
        }
    }

    $would_update = ($dry ? $scanned : null);

    json_out([
        'ok' => true,
        'data' => [
            'dry_run' => $dry,
            'scanned' => $scanned,
            'renamed' => $renamed,
            'thumbs_rebuilt' => $thumbs,
            'deleted_legacy' => $deleted,
            'db_rows_updated' => $updated,
            'db_rows_would_update' => $would_update,
            'height' => $h,
        ],
    ]);

} catch (Throwable $e) {
    json_fail($e->getMessage(), 500);
}
