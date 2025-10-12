<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  include "../../../config/library.php";
  include "../../../config/fungsi_seo.php";
  include "../../../config/fungsi_thumb.php";
  opendb();

  $module = $_GET['module'] ?? '';
  $act    = $_GET['act'] ?? '';

  // Only allow POST for delete
  if ($module === 'hubungi' && $act === 'hapus') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed.');
    }

    require_post_csrf();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        header("Location: ../../media.php?module=".$module);
        exit;
    }

      // Delete row with prepared statement
      $stmt = $dbconnection->prepare("DELETE FROM hubungi WHERE id_hubungi = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->close();

      header("Location: ../../media.php?module=".$module);
      exit;
    }
  closedb();
}
?>
