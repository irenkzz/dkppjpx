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

$id             = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nama_pemilik   = $_POST['nama_pemilik']   ?? '';
$judul_website  = $_POST['judul_website']  ?? '';
$alamat_website = $_POST['alamat_website'] ?? ''; 
$meta_deskripsi = $_POST['meta_deskripsi'] ?? '';
$meta_keyword   = $_POST['meta_keyword']   ?? '';
$email          = $_POST['email']          ?? '';
$twitter        = $_POST['twitter']        ?? '';
$twitter_widget = addslashes($_POST['twitter_widget'] ?? '');
$wtemp          = $_POST['wtemp']          ?? '';
$facebook       = $_POST['facebook']       ?? '';

$fb             = $_POST['fb']     ?? '';
$tube           = $_POST['tube']   ?? '';
$ig             = $_POST['ig']     ?? '';
$telpon         = $_POST['telpon'] ?? '';
$alamat         = $_POST['alamat'] ?? '';

$lokasi_file = $_FILES['fupload']['tmp_name'] ?? '';
$update      = 0;

if ($id <= 0) {
  header('location:../../media.php?module='.$module.'&r=gagal');
  exit;
}

// Apabila gambar favicon tidak diganti (atau tidak ada gambar yang di upload)
if (empty($lokasi_file)){
  $update = exec_prepared(
    "UPDATE identitas SET nama_pemilik = ?, nama_website = ?, alamat_website = ?, meta_deskripsi = ?, meta_keyword = ?, email = ?, facebook = ?, twitter = ?, fb = ?, tube = ?, ig = ?, twitter_widget = ?, wtemp = ?, telpon = ?, alamat = ? WHERE id_identitas = ?",
    "sssssssssssssssi",
    [
      $nama_pemilik,
      $judul_website,
      $alamat_website,
      $meta_deskripsi,
      $meta_keyword,
      $email,
      $facebook,
      $twitter,
      $fb,
      $tube,
      $ig,
      $twitter_widget,
      $wtemp,
      $telpon,
      $alamat,
      $id
    ]
  );
}
else{
    try {
        $res = upload_image_secure($_FILES['fupload'], [
            'dest_dir'      => dirname(__DIR__, 3),
            'max_bytes'     => 512 * 1024,
            'allow_mime'    => ['image/png'],
            'thumb_max_w'   => 64,
            'thumb_max_h'   => 64,
            'create_thumb'  => false,
            'preserve_alpha'=> true,
            'prefix'        => 'favicon_',
        ]);
        $nama_file = $res['filename'];
    } catch (Throwable $e) {
        echo "<script>alert('Upload favicon gagal: " . e($e->getMessage()) . "'); location=history.back();</script>";
        closedb();
        exit;
    }

    $old = basename($_POST['fupload_hapus'] ?? '');
    if ($old !== '') {
        @unlink(dirname(__DIR__, 3) . '/' . $old);
    }

    $update = exec_prepared(
        "UPDATE identitas SET nama_pemilik = ?, nama_website = ?, alamat_website = ?, meta_deskripsi = ?, meta_keyword = ?, email = ?, twitter = ?, twitter_widget = ?, wtemp = ?, facebook = ?, favicon = ? WHERE id_identitas = ?",
        "sssssssssssi",
        [
          $nama_pemilik,
          $judul_website,
          $alamat_website,
          $meta_deskripsi,
          $meta_keyword,
          $email,
          $twitter,
          $twitter_widget,
          $wtemp,
          $facebook,
          $nama_file,
          $id
        ]
      );
}
if($update) 
	header('location:../../media.php?module='.$module.'&r=sukses');
else 
	header('location:../../media.php?module='.$module.'&r=gagal');
closedb();
?>
