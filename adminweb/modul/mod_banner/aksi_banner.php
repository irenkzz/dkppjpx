<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
  echo "<link href=\"../../css/style_login.css\" rel=\"stylesheet\" type=\"text/css\" />
        <div id=\"login\"><h1 class=\"fail\">Untuk mengakses modul, Anda harus login dulu.</h1>
        <p class=\"fail\"><a href=\"../../index.php\">LOGIN</a></p></div>";  
  exit;
}
// Apabila user sudah login dengan benar, maka terbentuklah session
// Harden: central bootstrap (sessions/CSRF/db helpers)
else{

  require_once __DIR__ . "/../../../config/koneksi.php";
  require_once __DIR__ . "/../../includes/upload_helpers.php";
  require_once __DIR__ . "/../../includes/bootstrap.php"; // provides require_post_csrf()
  opendb();
  global $dbconnection;

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus banner
  if ($module=='banner' AND $act=='hapus'){    
    // enforce POST and CSRF token
    require_post_csrf();
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
      header("location:../../media.php?module=".$module);
      exit;
    }

    // 1) Fetch current filename (if any)
    $stmt = $dbconnection->prepare("SELECT gambar FROM banner WHERE id_banner = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($gambar);
    $hasRow = $stmt->fetch();
    $stmt->close();

    // 2) Delete row
    $stmt = $dbconnection->prepare("DELETE FROM banner WHERE id_banner = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // 3) After DB delete, remove files (if they existed)
    if ($hasRow && !empty($gambar)) {
      $base = basename($gambar);
      @unlink(__DIR__ . "/../../../foto_banner/" . $base);
      @unlink(__DIR__ . "/../../../foto_banner/small_" . $base);
    }

    // 4) Redirect back to module
    header("location:../../media.php?module=".$module);
    exit;
  }

  // Input banner
  elseif ($module=='banner' AND $act=='input'){
      require_post_csrf();

      // Collect input
      $judul = $_POST['judul'] ?? '';
      $link  = $_POST['link'] ?? '';

      $nama_gambar = '';
      if (!empty($_FILES['fupload']['tmp_name'])) {
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
          echo "<script>window.alert('Upload gagal: ".e($e->getMessage())."');history.back();</script>";
          exit;
        }
      }

      // Insert new record
      $stmt = $dbconnection->prepare("INSERT INTO banner (judul, link, gambar) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $judul, $link, $nama_gambar);
      $stmt->execute();
      $stmt->close();

      header("location:../../media.php?module=".$module);
      exit; 
  }

  // Update banner
  elseif ($module === 'banner' && $act === 'update') {
    // enforce POST and CSRF
    require_post_csrf();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
      header("location:../../media.php?module=".$module);
      exit;
    }

    $judul = $_POST['judul'] ?? '';
    $link  = $_POST['link'] ?? '';

    $has_new = !empty($_FILES['fupload']['tmp_name']);

    // ✅ No new image → just update text fields
    if (!$has_new) {
      $stmt = $dbconnection->prepare("UPDATE banner SET judul = ?, link = ? WHERE id_banner = ?");
      $stmt->bind_param("ssi", $judul, $link, $id);
      $stmt->execute();
      $stmt->close();

      header("location:../../media.php?module=".$module);
      exit;
    }

    // 📸 New image uploaded
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
      echo "<script>window.alert('Upload gagal: ".e($e->getMessage())."');history.back();</script>";
      exit;
    }

    // 🧾 Get old image name
    $old = null;
    $stmt = $dbconnection->prepare("SELECT gambar FROM banner WHERE id_banner = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_gambar);
    if ($stmt->fetch()) $old = $old_gambar;
    $stmt->close();

    // 📝 Update with new image filename
    $stmt = $dbconnection->prepare("UPDATE banner SET judul = ?, link = ?, gambar = ? WHERE id_banner = ?");
    $stmt->bind_param("sssi", $judul, $link, $nama_gambar, $id);
    $stmt->execute();
    $stmt->close();

    // 🧹 Clean up old image files after successful update
    if (!empty($old)) {
      $base = basename($old);
      @unlink(__DIR__ . '/../../../foto_banner/' . $base);
      @unlink(__DIR__ . '/../../../foto_banner/small_' . $base);
    }

    header("location:../../media.php?module=".$module);
    exit;
  }
  closedb();
}
?>
