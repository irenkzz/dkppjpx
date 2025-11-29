<?php
// adminweb/includes/upload_helpers.php

/**
 * Secure, opinionated image uploader using GD:
 * - Checks real MIME with finfo
 * - Optionally ensures it's a real image via getimagesize
 * - Generates random filename
 * - Re-encodes to JPEG (or keeps PNG for transparency when needed)
 * - Strips metadata and fixes EXIF orientation
 * - Writes full image and thumbnail
 */
function upload_image_secure(array $file, array $opts = []): array {
    // ---------- config ----------
    $defaults = [
        'max_bytes'      => 5 * 1024 * 1024,        // 5 MB
        'allow_mime'     => ['image/jpeg','image/png','image/gif'],
        'dest_dir'       => __DIR__ . '/../../foto_banner', // change to your dir
        'thumb_max_w'    => 480,
        'thumb_max_h'    => 480,
        'create_thumb'   => true,
        'thumb_prefix'   => 'small_',
        'jpeg_quality'   => 85,
        'preserve_alpha' => true,  // keep PNG as PNG to preserve transparency
        'prefix'         => '',    // e.g. 'banner_'
    ];
    $cfg = array_merge($defaults, $opts);

    // ensure dest dir exists
    if (!is_dir($cfg['dest_dir'])) {
        if (!mkdir($cfg['dest_dir'], 0755, true)) {
            throw new RuntimeException('Upload: failed to create destination directory.');
        }
    }

    // basic checks
    if (!isset($file['tmp_name'], $file['name'], $file['size'], $file['error'])) {
        throw new InvalidArgumentException('Upload: invalid $_FILES entry.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }
    if ($file['size'] <= 0 || $file['size'] > $cfg['max_bytes']) {
        throw new RuntimeException('Upload: file too large or empty.');
    }

    // real MIME via finfo
    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($file['tmp_name']) ?: 'application/octet-stream';
    if (!in_array($mime, $cfg['allow_mime'], true)) {
        throw new RuntimeException('Upload: unsupported image type: ' . $mime);
    }

    // ensure it’s actually an image
    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        throw new RuntimeException('Upload: file is not a valid image.');
    }
    [$srcW, $srcH] = $imgInfo;

    // open image into GD
    switch ($mime) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($file['tmp_name']);
            // fix EXIF orientation if possible
            if (function_exists('exif_read_data')) {
                try {
                    $exif = @exif_read_data($file['tmp_name']);
                    if (!empty($exif['Orientation'])) {
                        $src = gd_fix_orientation($src, (int)$exif['Orientation']);
                    }
                } catch (Throwable $e) { /* ignore */ }
            }
            break;
        case 'image/png':
            $src = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($file['tmp_name']);
            break;
        default:
            throw new RuntimeException('Upload: unsupported image type (open).');
    }
    if (!$src) throw new RuntimeException('Upload: failed to load image.');

    // choose output format:
    // - keep PNG if transparency must be preserved
    // - otherwise convert to JPEG to strip metadata and unify format
    $outIsPng = ($mime === 'image/png' && $cfg['preserve_alpha'] === true);

    // generate random server filename
    $rand   = bin2hex(random_bytes(12));
    $base   = $cfg['prefix'] . $rand;
    $ext    = $outIsPng ? '.png' : '.jpg';
    $final  = $base . $ext;
    $thumb  = $cfg['create_thumb'] ? $cfg['thumb_prefix'] . $final : null;

    $destPath  = rtrim($cfg['dest_dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $final;
    $thumbPath = $thumb ? rtrim($cfg['dest_dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $thumb : null;

    // write full-size (re-encode)
    if ($outIsPng) {
        imagesavealpha($src, true);
        if (!imagepng($src, $destPath)) throw new RuntimeException('Upload: failed to save PNG.');
    } else {
        // convert to truecolor (avoid palette issues)
        $true = imagecreatetruecolor($srcW, $srcH);
        imagecopy($true, $src, 0, 0, 0, 0, $srcW, $srcH);
        if (!imagejpeg($true, $destPath, $cfg['jpeg_quality'])) throw new RuntimeException('Upload: failed to save JPEG.');
        imagedestroy($true);
    }

    // generate thumbnail (fit within max box, keep aspect)
    if ($cfg['create_thumb']) {
        $thumbImg = gd_resize_contain($src, $cfg['thumb_max_w'], $cfg['thumb_max_h'], $outIsPng);
        if ($outIsPng) {
            imagesavealpha($thumbImg, true);
            if (!imagepng($thumbImg, $thumbPath)) throw new RuntimeException('Upload: failed to save PNG thumb.');
        } else {
            if (!imagejpeg($thumbImg, $thumbPath, $cfg['jpeg_quality'])) throw new RuntimeException('Upload: failed to save JPEG thumb.');
        }
        imagedestroy($thumbImg);
    }

    // cleanup
    imagedestroy($src);

    // set safe perms
    @chmod($destPath, 0644);
    if ($thumbPath) {
        @chmod($thumbPath, 0644);
    }

    return [
        'filename'      => $final,
        'thumbnail'     => $thumb,
        'mime'          => $outIsPng ? 'image/png' : 'image/jpeg',
        'width'         => $srcW,
        'height'        => $srcH,
        'dest_path'     => $destPath,
        'thumb_path'    => $thumbPath,
    ];
}

/** EXIF orientation fix for JPEGs loaded into GD */
function gd_fix_orientation($img, int $orientation) {
    switch ($orientation) {
        case 2: return image_flip($img, IMG_FLIP_HORIZONTAL); // Mirror horizontal
        case 3: return imagerotate($img, 180, 0);
        case 4: return image_flip($img, IMG_FLIP_VERTICAL);   // Mirror vertical
        case 5: $img = imagerotate($img, -90, 0); return image_flip($img, IMG_FLIP_HORIZONTAL);
        case 6: return imagerotate($img, -90, 0);
        case 7: $img = imagerotate($img, 90, 0);  return image_flip($img, IMG_FLIP_HORIZONTAL);
        case 8: return imagerotate($img, 90, 0);
        default: return $img;
    }
}

/** flip wrapper for older PHPs */
function image_flip($img, int $mode) {
    if (function_exists('imageflip')) {
        imageflip($img, $mode);
        return $img;
    }
    // rudimentary fallback: horizontal only
    $w = imagesx($img); $h = imagesy($img);
    $dest = imagecreatetruecolor($w, $h);
    if ($mode === IMG_FLIP_HORIZONTAL) {
        for ($x=0; $x<$w; $x++) imagecopy($dest, $img, $w-$x-1, 0, $x, 0, 1, $h);
    } elseif ($mode === IMG_FLIP_VERTICAL) {
        for ($y=0; $y<$h; $y++) imagecopy($dest, $img, 0, $h-$y-1, 0, $y, $w, 1);
    } else {
        // no-op for diagonal in fallback
        imagedestroy($dest);
        return $img;
    }
    imagedestroy($img);
    return $dest;
}

/** resize contain (fit within max box, keep aspect, fill transparent if PNG) */
function gd_resize_contain($src, int $maxW, int $maxH, bool $asPng) {
    $srcW = imagesx($src); $srcH = imagesy($src);
    $scale = min($maxW / max(1,$srcW), $maxH / max(1,$srcH), 1);
    $dstW = (int)floor($srcW * $scale);
    $dstH = (int)floor($srcH * $scale);

    $dst = imagecreatetruecolor($dstW, $dstH);
    if ($asPng) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $trans = imagecolorallocatealpha($dst, 0,0,0,127);
        imagefill($dst, 0, 0, $trans);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
    return $dst;
}

function upload_file_secure(array $file, array $opts = []): array {
    $defaults = [
        'max_bytes'   => 10 * 1024 * 1024, // 10 MB
        'allow_ext'   => ['pdf','doc','docx','jpg','jpeg','png'],
        'allow_mime_by_ext' => [
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
        ],
        'dest_dir'    => __DIR__ . '/../../files',
        'prefix'      => 'file_',
    ];
    $cfg = array_merge($defaults, $opts);

    if (!is_dir($cfg['dest_dir'])) mkdir($cfg['dest_dir'], 0755, true);

    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload error: ' . $file['error']);
    if ($file['size'] <= 0 || $file['size'] > $cfg['max_bytes']) throw new RuntimeException('File too large or empty');

    $orig  = $file['name'] ?? 'upload.bin';
    $ext   = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $cfg['allow_ext'], true)) throw new RuntimeException('Extension not allowed');

    $fi   = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($file['tmp_name']) ?: 'application/octet-stream';
    $allowedMimes = $cfg['allow_mime_by_ext'][$ext] ?? [];
    if (!empty($allowedMimes) && !in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException('MIME type not allowed for this extension');
    }

    $rand  = bin2hex(random_bytes(12));
    $final = $cfg['prefix'] . $rand . '.' . $ext;
    $dest  = rtrim($cfg['dest_dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $final;

    if (!move_uploaded_file($file['tmp_name'], $dest)) throw new RuntimeException('Failed to move upload');

    @chmod($dest, 0644);

    return ['filename' => $final, 'dest_path' => $dest, 'ext' => $ext];
}
