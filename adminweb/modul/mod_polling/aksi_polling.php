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
  $act    = $_GET['act'];

  // Hapus polling
  if ($module=='polling' AND $act=='hapus'){
    $hapus = "DELETE FROM poling WHERE id_poling='$_GET[id]'";
    querydb($hapus);
    
    header("location:../../media.php?module=".$module);
  }

  // Input polling
  elseif ($module=='polling' AND $act=='input'){
    $pilihan = $_POST['pilihan'];
    $status  = $_POST['status'];
    
    $input = "INSERT INTO poling(pilihan, status) VALUES('$pilihan', '$status')";
    querydb($input);
    
    header("location:../../media.php?module=".$module);
  }

  // Update polling
  elseif ($module=='polling' AND $act=='update'){
    $id      = $_POST['id'];
    $pilihan = $_POST['pilihan'];
    $status  = $_POST['status'];
    $aktif   = $_POST['aktif'];
    
    $update = "UPDATE poling SET pilihan='$pilihan', status='$status', aktif='$aktif' WHERE id_poling='$id'";
    querydb($update);
    
    header("location:../../media.php?module=".$module);
  }
  closedb();
}
?>
