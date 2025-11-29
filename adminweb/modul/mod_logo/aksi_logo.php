<?php
require_once __DIR__ . "/../../includes/bootstrap.php"; // CSRF + DB helpers, secure session
require_once __DIR__ . "/../../includes/upload_helpers.php";
opendb();

// Apabila user belum login
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";  
    closedb();
    exit;
}

// Batasi hanya admin
if (($_SESSION['leveluser'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

$module = $_GET['module'] ?? '';

require_post_csrf();

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$update = 0;

$lokasi_file    = $_FILES['fupload']['tmp_name'] ?? '';

// Apabila gambar logo tidak diganti (atau tidak ada gambar yang di upload)
$file_dir = __DIR__ . '/../../../images';

if ($id <= 0 || empty($lokasi_file)){
        header('location:../../media.php?module='.$module.'&r=gagal');
        closedb();
        exit;
}
else{
    try {
        $res = upload_image_secure($_FILES['fupload'], [
            'dest_dir'     => $file_dir,
            'max_bytes'    => 3 * 1024 * 1024,
            'thumb_max_w'  => 400,
            'thumb_max_h'  => 400,
            'create_thumb' => false,
            'prefix'       => 'logo_',
        ]);
        $nama_file = $res['filename'];
    } catch (Throwable $e) {
        echo "<script>alert('Upload logo gagal: " . e($e->getMessage()) . "'); location=history.back();</script>";
        closedb();
        exit;
    }

    $old = basename($_POST['fupload_hapus'] ?? '');
    if ($old !== '') {
        @unlink($file_dir . '/' . $old);
    }

    $update = exec_prepared("UPDATE identitas SET logo = ? WHERE id_identitas = ?", "si", [$nama_file, $id]);
}
if($update) 
	header('location:../../media.php?module='.$module.'&r=sukses');
else 
	header('location:../../media.php?module='.$module.'&r=gagal');
closedb();
?>
