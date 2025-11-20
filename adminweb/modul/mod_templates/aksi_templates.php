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

  // Hapus templates
  if ($module=='templates' AND $act=='hapus'){
    require_post_csrf();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
      exec_prepared("DELETE FROM templates WHERE id_templates = ?", "i", [$id]);
    }

    header("location:../../media.php?module=".$module);
    exit;
  }

  // Input templates
  if ($module=='templates' AND $act=='input'){
    require_post_csrf();

    $nama_templates = $_POST['nama_templates'];
    $pembuat        = $_POST['pembuat'];
    $folder         = $_POST['folder'];

    exec_prepared(
      "INSERT INTO templates (judul, pembuat, folder) VALUES (?, ?, ?)",
      "sss",
      [$nama_templates, $pembuat, $folder]
    );

    header("location:../../media.php?module=".$module);
    exit;
  }

  // Update templates
  elseif ($module=='templates' AND $act=='update'){
    require_post_csrf();

    $id             = (int)($_POST['id'] ?? 0);
    $nama_templates = $_POST['nama_templates'];
    $pembuat        = $_POST['pembuat'];
    $folder         = $_POST['folder'];

    exec_prepared(
      "UPDATE templates SET judul = ?, pembuat = ?, folder = ? WHERE id_templates = ?",
      "sssi",
      [$nama_templates, $pembuat, $folder, $id]
    );

    header("location:../../media.php?module=".$module);
    exit;
  }

  // Aktifkan templates
  elseif ($module=='templates' AND $act=='aktifkan'){
    require_post_csrf();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
      exec_prepared("UPDATE templates SET aktif = 'Y' WHERE id_templates = ?", "i", [$id]);
      // de-activate others
      exec_prepared("UPDATE templates SET aktif = 'N' WHERE id_templates != ?", "i", [$id]);
    }

    header("location:../../media.php?module=".$module);
    exit;
  }
  closedb();
}
?>
