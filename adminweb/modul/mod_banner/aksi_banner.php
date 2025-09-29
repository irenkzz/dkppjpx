<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
  echo "<link href=\"../../css/style_login.css\" rel=\"stylesheet\" type=\"text/css\" />
        <div id=\"login\"><h1 class=\"fail\">Untuk mengakses modul, Anda harus login dulu.</h1>
        <p class=\"fail\"><a href=\"../../index.php\">LOGIN</a></p></div>";  
}
// Apabila user sudah login dengan benar, maka terbentuklah session
// Harden: central bootstrap (sessions/CSRF/db helpers)
else{
  include "../../../config/koneksi.php";
  include "../../../config/fungsi_thumb.php";
  include "../../../config/fungsi_thumb.php"; // kept for BC
  require_once __DIR__ . "/../../includes/upload_helpers.php";
  require_once __DIR__ . "/../../includes/bootstrap.php"; // provides require_post_csrf()
  opendb();

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus banner
  if ($module=='banner' AND $act=='hapus'){    
    header("location:../../media.php?module=".$module);
  }

  // Input banner
  elseif ($module=='banner' AND $act=='input'){
        header("location:../../media.php?module=".$module); 
  }

  // Update banner
  elseif ($module=='banner' AND $act=='update'){
    //Enforce POST + CSRF, pakai prepared statement dan upload helper
    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
      htpp_response_code(405);
      exit('Method not Allowed');
    }
    require_post_csrf();
    
    $id     = $isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $judul  = $_POST['judul'] ?? '';
    $link   = $_POST['link'] ?? '';
    if (id <= 0) {
      header("location:../../media.php?module=".$module);
      exit;
    }

    // Require a new image for this flow (old UI expected it)
    if (empty($_FILES['fupload']['tmp_name'])) {
      echo "<script>window.alert('Anda belum memilih gambar...!'); window.location='../../media.php?module=banner';</script>";
      exit;
    }

    try {
      $res = upload_image_secure($_FILES['fupload'], [
        'dest_dir'     => __DIR__ . '/../../../foto_banner',
        'thumb_max_w'  => 480,
        'thumb_max_h'  => 320,
        'jpeg_quality' => 85,
        'prefix'       => 'banner_',
      ]);
      $nama_gambar = $res['filename'];
    } catch (Throwable $e) {
      echo "<script>window.alert('Upload gagal: ' + ". " " . ");</script>"; // keep simple
      echo "<script>history.back();</script>";
      exit;
    }

    // Fetch old filename to clean up after successful update
    $stmt = $dbconnection->prepare("SELECT gambar FROM banner WHERE id_banner = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_gambar);
    $old = $stmt->fetch() ? $old_gambar : null;
    $stmt->close();

    // Update safely
    $stmt = $dbconnection->prepare("UPDATE banner SET judul = ?, link = ?, gambar = ? WHERE id_banner = ?");
    $stmt->bind_param("sssi", $judul, $link, $nama_gambar, $id);
    $stmt->execute();
    $stmt->close();

    // Remove old files (after DB success)
    if (!empty($old)) {
      $base = basename($old);
      @unlink(__DIR__ . '/../../../foto_banner/' . $base);
      @unlink(__DIR__ . '/../../../foto_banner/small_' . $base);
    }

    header("location:../../media.php?module=".$module);
  }
  closedb();
}
?>
