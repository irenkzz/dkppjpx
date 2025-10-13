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

  // Hapus menu
  if ($module=='menu' AND $act=='hapus'){
    require_post_csrf();
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
      exec_prepared("DELETE FROM menu WHERE id_menu = ?", "i", [$id]);
    }
    header("location:../../media.php?module=".$module);
    exit;
  }

  // Input menu
  elseif ($module=='menu' AND $act=='input'){
    require_post_csrf();
    $nama_menu = $_POST['nama_menu'];
    $link      = $_POST['link'];
    $id_parent = isset($_POST['id_parent']) ? (int)$_POST['id_parent'] : 0;

    exec_prepared(
      "INSERT INTO menu (nama_menu, link, id_parent) VALUES (?, ?, ?)",
      "ssi",
      [$nama_menu, $link, $id_parent]
    );

    header("location:../../media.php?module=".$module);
    exit;
  }

  // Update menu
  elseif ($module=='menu' AND $act=='update'){
    require_post_csrf();
    $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_menu = $_POST['nama_menu'];
    $link      = $_POST['link'];
    $id_parent = isset($_POST['id_parent']) ? (int)$_POST['id_parent'] : 0;
    $aktif     = $_POST['aktif'];

    exec_prepared(
      "UPDATE menu SET nama_menu = ?, link = ?, aktif = ?, id_parent = ? WHERE id_menu = ?",
      "sssii",
      [$nama_menu, $link, $aktif, $id_parent, $id]
    );

    header("location:../../media.php?module=".$module);
    exit;
  }
  closedb();
}
?>
