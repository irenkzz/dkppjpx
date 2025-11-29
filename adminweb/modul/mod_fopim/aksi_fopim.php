<?php
require_once __DIR__ . "/../../includes/bootstrap.php"; // CSRF + DB helpers, secure session
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

$ekstensi =  array('jpg','jpeg','png');
$lokasi_file    = $_FILES['fupload']['tmp_name'] ?? '';
$nama_file      = $_FILES['fupload']['name'] ?? '';
$ext = pathinfo($nama_file, PATHINFO_EXTENSION);

// Apabila gambar favicon tidak diganti (atau tidak ada gambar yang di upload)
if ($id <= 0 || empty($lokasi_file)){
		header('location:../../media.php?module='.$module.'&r=gagal');
}
else{
	if(!in_array($ext,$ekstensi) ) {
		echo '<script>alert("Ekstensi file gambar tidak diperbolehkan, silahkan coba kembali !"); location=history.back();</script>';
	}else{
		// folder untuk gambar favicon ada di root
		$folder = "../../../images/";
		$file_upload = $folder . $nama_file;
		// upload gambar favicon
		move_uploaded_file($_FILES["fupload"]["tmp_name"], $file_upload);
		unlink("../../../images/$_POST[fupload_hapus]");
		$update = exec_prepared("UPDATE identitas SET fopim = ? WHERE id_identitas = ?", "si", [$nama_file, $id]);
	}
}
if($update) 
	header('location:../../media.php?module='.$module.'&r=sukses');
else 
	header('location:../../media.php?module='.$module.'&r=gagal');
closedb();
?>
