<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . '/../../includes/bootstrap.php';
  include "../../../config/library.php";
  opendb();
  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus sekilasinfo
  if ($module=='sekilasinfo' AND $act=='hapus'){
      require_post_csrf();
      $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
      if ($id > 0) {
          exec_prepared("DELETE FROM sekilasinfo WHERE id_sekilas = ?", "i", [$id]);
      }
      header("location:../../media.php?module=".$module);
      exit;
  }

  // Input sekilasinfo
  elseif ($module=='sekilasinfo' AND $act=='input'){
    require_post_csrf();
    $info  = $_POST['info'];

      exec_prepared(
          "INSERT INTO sekilasinfo (info, tgl_posting) VALUES (?, ?)",
          "ss",
          [$info, $tgl_sekarang]
      );

      header("location:../../media.php?module=".$module);
      exit;
  }

  // Update sekilasinfo
  elseif ($module=='sekilasinfo' AND $act=='update'){
    require_post_csrf();
    $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $info        = $_POST['info'];

      exec_prepared(
          "UPDATE sekilasinfo SET info = ? WHERE id_sekilas = ?",
          "si",
          [$info, $id]
      );

      header("location:../../media.php?module=".$module);
      exit;
  }
  closedb();
}
?>
