<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  include "../../../config/koneksi.php";
  include "../../../config/fungsi_seo.php";
  opendb();

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus Tag
  if ($module=='tag' AND $act=='hapus'){
    $hapus = "DELETE FROM tag WHERE id_tag='$_GET[id]'";
    querydb($hapus);
    
    header("location:../../media.php?module=".$module);
  }

  // Input tag
  elseif ($module=='tag' AND $act=='input'){
    $nama_tag = $_POST['nama_tag'];
    $tag_seo  = seo_title($_POST['nama_tag']);
    
    $input = "INSERT INTO tag(nama_tag, tag_seo) VALUES('$nama_tag', '$tag_seo')";
    querydb($input);
    
    header("location:../../media.php?module=".$module);
  }

  // Update tag
  elseif ($module=='tag' AND $act=='update'){
    $id       = $_POST['id'];
    $nama_tag = $_POST['nama_tag'];
    $tag_seo  = seo_title($_POST['nama_tag']);
    $pilihan  = $_POST['pilihan'];
    
    $update = "UPDATE tag SET nama_tag='$nama_tag', tag_seo='$tag_seo', pilihan='$pilihan' WHERE id_tag='$id'";
    querydb($update);
    
    header("location:../../media.php?module=".$module);
  }
  closedb();
}
?>
