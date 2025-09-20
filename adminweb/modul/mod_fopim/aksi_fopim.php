<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";  
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  include "../../../config/koneksi.php";
  opendb();
  
  $module = $_GET['module'];

  $id             = $_POST['id'];

  $ekstensi =  array('jpg','jpeg','png');
  $lokasi_file    = $_FILES['fupload']['tmp_name'];
  $nama_file      = $_FILES['fupload']['name'];
  $ext = pathinfo($nama_file, PATHINFO_EXTENSION);

  // Apabila gambar favicon tidak diganti (atau tidak ada gambar yang di upload)
  if (empty($lokasi_file)){
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
		$edit = "UPDATE identitas SET fopim = '$nama_file' WHERE id_identitas = '$id'";
		$update=querydb($edit);
	}
  }
  if($update) 
		header('location:../../media.php?module='.$module.'&r=sukses');
  else 
		header('location:../../media.php?module='.$module.'&r=gagal');
	closedb();
}
?>