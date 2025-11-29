<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . "/../../includes/bootstrap.php"; // CSRF helpers + DB
  require_once __DIR__ . "/../../../config/library.php";
  opendb();

  $module = $_GET['module'] ?? '';
  $act    = $_GET['act'] ?? '';

  // Hapus listslider (POST + CSRF)
  if ($module=='listslider' AND $act=='hapus'){
    require_post_csrf();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
      exec_prepared("DELETE FROM listslider WHERE id_list = ?", "i", [$id]);
    }
    header("location:../../media.php?module=".$module);
    exit;
  }

  // Input listslider (POST + CSRF)
  elseif ($module=='listslider' AND $act=='input'){
    require_post_csrf();

    $nama_menu  = $_POST['nama_menu'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    $link  	  = $_POST['link_menu'] ?? '';

    exec_prepared(
      "INSERT INTO listslider (nama_menu, keterangan, link) VALUES (?, ?, ?)",
      "sss",
      [$nama_menu, $keterangan, $link]
    );

    header("location:../../media.php?module=".$module);
    exit;
  }

  // Update listslider (POST + CSRF)
  elseif ($module=='listslider' AND $act=='update'){
    require_post_csrf();

    $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_menu   = $_POST['nama_menu'] ?? '';
    $keterangan  = $_POST['keterangan'] ?? '';
    $link  	  = $_POST['link_menu'] ?? '';

    if ($id > 0) {
      exec_prepared(
        "UPDATE listslider SET nama_menu = ?, keterangan = ?, link = ? WHERE id_list = ?",
        "sssi",
        [$nama_menu, $keterangan, $link, $id]
      );
    }

    header("location:../../media.php?module=".$module);
    exit;
  }
  closedb();
}
?>
