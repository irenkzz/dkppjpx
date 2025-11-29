<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";  
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . "/../../includes/bootstrap.php"; // CSRF + DB helpers
  opendb();
  
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
  
  $ekstensi    = array('jpg','jpeg','png');
  $lokasi_file = $_FILES['fupload']['tmp_name'] ?? '';
  $nama_file   = $_FILES['fupload']['name'] ?? '';
  $ext         = pathinfo($nama_file, PATHINFO_EXTENSION);
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
    // folder untuk gambar favicon ada di root
    $folder = "../../../";
    $file_upload = $folder . $nama_file;
    // upload gambar favicon
	if(!in_array($ext,$ekstensi) ) {
		echo '<script>alert("Ekstensi file proposal tidak diperbolehkan, silahkan coba kembali !"); location=history.back();</script>';
	}else{
		move_uploaded_file($_FILES["fupload"]["tmp_name"], $file_upload);
		unlink("../../../$_POST[fupload_hapus]");
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
  }
  if($update) 
		header('location:../../media.php?module='.$module.'&r=sukses');
	else 
		header('location:../../media.php?module='.$module.'&r=gagal');
	closedb();
}
?>
