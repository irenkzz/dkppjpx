<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  include "../../../config/koneksi.php";
  include "../../../config/library.php";
  opendb();

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus menu
  if ($module=='menu' AND $act=='hapus'){
    $query = "DELETE FROM menu WHERE id_menu='$_GET[id]'";
    querydb($query);
    header("location:../../media.php?module=".$module);
  }

  // Input menu
  elseif ($module=='menu' AND $act=='input'){
    $nama_menu = $_POST['nama_menu'];
    $link      = $_POST['link'];
    $id_parent = $_POST['id_parent'];

    $input = "INSERT INTO menu(nama_menu, 
                               link,
                               id_parent) 
                        VALUES('$nama_menu', 
                               '$link',
                               '$id_parent')";
    querydb($input);

    header("location:../../media.php?module=".$module);
  } 

  // Update menu
  elseif ($module=='menu' AND $act=='update'){
    $id        = $_POST['id'];
    $nama_menu = $_POST['nama_menu'];
    $link      = $_POST['link'];
    $id_parent = $_POST['id_parent'];
    $aktif     = $_POST['aktif'];

    $update = "UPDATE menu SET nama_menu = '$nama_menu',
                               link      = '$link', 
                               aktif     = '$aktif', 
                               id_parent = '$id_parent'
                         WHERE id_menu   = '$id'";
    querydb($update);
      
    header("location:../../media.php?module=".$module);
  }
  closedb();
}
?>
