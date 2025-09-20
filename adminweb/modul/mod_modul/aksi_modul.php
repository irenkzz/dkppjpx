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

  // Input modul
  if ($module=='modul' AND $act=='input'){
    // cari urutan terakhir
    $query = querydb("SELECT urutan FROM modul ORDER BY urutan DESC LIMIT 1");
    $r     = $query->fetch_array();
    
    $urutan     = $r['urutan']+1;
    $nama_modul = $_POST['nama_modul'];
    $link       = $_POST['link'];
    
    $input = "INSERT INTO modul(nama_modul, link, urutan) VALUES('$nama_modul', '$link', '$urutan')";
    querydb($input);
    
    header("location:../../media.php?module=".$module);
  }

  // Update modul
  elseif ($module=='modul' AND $act=='update'){
    $id         = $_POST['id'];
    $urutan     = $_POST['urutan'];
    $nama_modul = $_POST['nama_modul'];
    $link       = $_POST['link'];
    $status     = $_POST['status'];
    $aktif      = $_POST['aktif'];
    
    $update = "UPDATE modul SET nama_modul = '$nama_modul',
                                link       = '$link',
                                urutan     = '$urutan',
                                status     = '$status', 
                                aktif      = '$aktif' 
                          WHERE id_modul   = '$id'";
    querydb($update);
    
    header("location:../../media.php?module=".$module);
  }
  closedb();
}
?>
