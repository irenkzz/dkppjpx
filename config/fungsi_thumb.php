<?php
require_once __DIR__ . '/../adminweb/includes/upload_helpers.php';

/**
 * Resolve an upload array from flexible input (array, field name, or default `fupload`).
 */
function fx_get_upload_file($source = null): array {
    if (is_array($source) && isset($source['tmp_name'])) {
        return $source;
    }
    if (is_string($source) && isset($_FILES[$source])) {
        return $_FILES[$source];
    }
    if (isset($_FILES['fupload'])) {
        return $_FILES['fupload'];
    }
    throw new InvalidArgumentException('Upload: no file data provided.');
}

// Upload gambar untuk berita
function UploadImage($source = null) {
    $file = fx_get_upload_file($source);
    $res = upload_image_secure($file, [
        'dest_dir'     => dirname(__DIR__) . '/foto_berita',
        'thumb_max_w'  => 390,
        'thumb_max_h'  => 390,
        'jpeg_quality' => 85,
        'prefix'       => 'berita_',
    ]);
    return $res['filename'];
}

function UploadSlider($source = null) {
    $file = fx_get_upload_file($source);
    $res = upload_image_secure($file, [
        'dest_dir'     => dirname(__DIR__) . '/foto_slider',
        'thumb_max_w'  => 200,
        'thumb_max_h'  => 200,
        'jpeg_quality' => 85,
        'prefix'       => 'slider_',
    ]);
    return $res['filename'];
}

function UploadBanner($source = null) {
    $file = fx_get_upload_file($source);
    $res = upload_image_secure($file, [
        'dest_dir'     => dirname(__DIR__) . '/foto_banner',
        'thumb_max_w'  => 480,
        'thumb_max_h'  => 320,
        'jpeg_quality' => 85,
        'prefix'       => 'banner_',
    ]);
    return $res['filename'];
}

// Upload file untuk download file
function UploadFile($source = null) {
    $file = fx_get_upload_file($source);
    $res = upload_file_secure($file, [
        'dest_dir'   => dirname(__DIR__) . '/files',
        'allow_ext'  => ['pdf','doc','docx','jpg','jpeg','png'],
        'prefix'     => 'file_',
    ]);
    return $res['filename'];
}

// Upload gambar untuk album galeri foto
function UploadAlbum($source = null) {
    $file = fx_get_upload_file($source);
    $res = upload_image_secure($file, [
        'dest_dir'     => dirname(__DIR__) . '/img_album',
        'thumb_max_w'  => 500,
        'thumb_max_h'  => 500,
        'jpeg_quality' => 85,
        'prefix'       => 'album_',
    ]);
    return $res['filename'];
}

// Upload gambar untuk galeri foto
function UploadGallery($source = null) {
    $file = fx_get_upload_file($source);
    $res = upload_image_secure($file, [
        'dest_dir'     => dirname(__DIR__) . '/img_galeri',
        'thumb_max_w'  => 100,
        'thumb_max_h'  => 100,
        'jpeg_quality' => 85,
        'prefix'       => 'gallery_',
    ]);
    return $res['filename'];
}

// Upload gambar untuk sekilas info
function UploadInfo($source = null) {
    $file = fx_get_upload_file($source);
    $res = upload_image_secure($file, [
        'dest_dir'     => dirname(__DIR__) . '/foto_info',
        'thumb_max_w'  => 54,
        'thumb_max_h'  => 54,
        'jpeg_quality' => 85,
        'prefix'       => 'info_',
    ]);
    return $res['filename'];
}

// Upload gambar untuk favicon
function UploadFavicon($source = null) {
    $file = fx_get_upload_file($source);
    $res = upload_image_secure($file, [
        'dest_dir'      => dirname(__DIR__),
        'max_bytes'     => 512 * 1024, // 512 KB
        'allow_mime'    => ['image/png'],
        'thumb_max_w'   => 64,
        'thumb_max_h'   => 64,
        'create_thumb'  => false, // no extra copy in the webroot
        'preserve_alpha'=> true,
        'prefix'        => 'favicon_',
    ]);
    return $res['filename'];
}
