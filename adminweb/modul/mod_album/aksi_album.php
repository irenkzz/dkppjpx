<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  include "../../../config/fungsi_seo.php";
  require_once __DIR__ . '/../../includes/upload_helpers.php';
  opendb();

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Input album
  if ($module=='album' AND $act=='input'){
    require_post_csrf();
    $nama_album = $_POST['nama_album'];
    $album_seo  = seo_title($_POST['nama_album']);

    try {
      $res = upload_image_secure($_FILES['fupload'], [
        'dest_dir'     => __DIR__ . '/../../../img_album',
        'thumb_max_w'  => 180,
        'thumb_max_h'  => 180,
        'jpeg_quality' => 85,
        'prefix'       => 'album_',
      ]);
      $nama_gambar = $res['filename'];
    } catch (Throwable $e) {
      echo "<script>window.alert('Upload gagal: " . e($e->getMessage()) . "'); location=history.back();</script>";
      exit;
    }

    $stmt = $dbconnection->prepare("INSERT INTO album (jdl_album, album_seo, gbr_album) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nama_album, $album_seo, $nama_gambar);
    $stmt->execute();
    $stmt->close();

    header("location:../../media.php?module=".$module);
  }
 
    // Update album
  elseif ($module=='album' AND $act=='update'){
    require_post_csrf();
    $id         = (int)$_POST['id'];
    $nama_album = $_POST['nama_album'];
    $album_seo  = seo_title($_POST['nama_album']);
    $aktif      = $_POST['aktif'];

    if (empty($_FILES['fupload']['tmp_name'])){
      $stmt = $dbconnection->prepare("UPDATE album SET jdl_album = ?, album_seo = ?, aktif = ? WHERE id_album = ?");
      $stmt->bind_param("sssi", $nama_album, $album_seo, $aktif, $id);
      $stmt->execute();
      $stmt->close();
      header("location:../../media.php?module=".$module);
    } else {
      try {
        $res = upload_image_secure($_FILES['fupload'], [
          'dest_dir'     => __DIR__ . '/../../../img_album',
          'thumb_max_w'  => 180,
          'thumb_max_h'  => 180,
          'jpeg_quality' => 85,
          'prefix'       => 'album_',
        ]);
        $nama_gambar = $res['filename'];
      } catch (Throwable $e) {
        echo "<script>window.alert('Upload gagal: " . e($e->getMessage()) . "'); location=history.back();</script>";
        exit;
      }

      $old = null;
      $stmt = $dbconnection->prepare("SELECT gbr_album FROM album WHERE id_album = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($old_gbr);
      if ($stmt->fetch()) $old = $old_gbr;
      $stmt->close();

      $stmt = $dbconnection->prepare("UPDATE album SET jdl_album = ?, album_seo = ?, aktif = ?, gbr_album = ? WHERE id_album = ?");
      $stmt->bind_param("ssssi", $nama_album, $album_seo, $aktif, $nama_gambar, $id);
      $stmt->execute();
      $stmt->close();

      if (!empty($old)) {
        $base = basename($old);
        @unlink(__DIR__ . "/../../../img_album/$base");
        @unlink(__DIR__ . "/../../../img_album/small_$base");
        @unlink(__DIR__ . "/../../../img_album/medium_$base");
      }

      header("location:../../media.php?module=".$module);
    }
  }

  closedb();
}
?>
