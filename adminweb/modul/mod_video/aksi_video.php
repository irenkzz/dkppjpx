<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  include "../../../config/koneksi.php";
  include "../../../config/fungsi_seo.php";  
  include "../../../config/fungsi_thumbnail.php";
  opendb();

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus video
  if ($module=='video' AND $act=='hapus'){
    require_once __DIR__ . '/../../includes/bootstrap.php';
    require_post_csrf();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) { header("location:../../media.php?module=".$module); exit; }

    // fetch filename (prepared)
    $stmt = $dbconnection->prepare("SELECT gambar FROM video WHERE id_video = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($gambar);
    $has = $stmt->fetch();
    $stmt->close();

    // delete row (prepared)
    $stmt = $dbconnection->prepare("DELETE FROM video WHERE id_video = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // unlink files safely
    if ($has && !empty($gambar)) {
      $base = basename($gambar);
      @unlink(__DIR__ . "/../../../foto_video/$base");
      @unlink(__DIR__ . "/../../../foto_video/small_$base");
    }

    header("location:../../media.php?module=".$module);
  }


  // Input video
  elseif ($module=='video' AND $act=='input'){
    require_once __DIR__ . '/../../includes/bootstrap.php';
    require_once __DIR__ . '/../../includes/upload_helpers.php';
    require_post_csrf();

    $judul_video  = $_POST['judul_video'];
    $video_seo    = seo_title($_POST['judul_video']);
    $deskripsi    = $_POST['deskripsi'];
    $yt_in        = trim($_POST['link_youtube']);
    $parts        = explode('watch?v=', $yt_in, 2);
    $yt_code      = isset($parts[1]) ? $parts[1] : $yt_in;

    $has_file = !empty($_FILES['fupload']['tmp_name']);

    if (!$has_file){
      $stmt = $dbconnection->prepare("INSERT INTO video (judul_video, video_seo, link_youtube, deskripsi) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $judul_video, $video_seo, $yt_code, $deskripsi);
      $stmt->execute();
      $stmt->close();
      header("location:../../media.php?module=".$module);
    } else {
      try {
        $res = upload_image_secure($_FILES['fupload'], [
          'dest_dir'     => __DIR__ . '/../../../foto_video',
          'thumb_max_w'  => 180,
          'thumb_max_h'  => 180,
          'jpeg_quality' => 85,
          'prefix'       => 'video_',
        ]);
        $nama_gambar = $res['filename'];
      } catch (Throwable $e) {
        echo "<script>window.alert('Upload Gagal: " . e($e->getMessage()) . "'); window.location='../../media.php?module=video';</script>";
        exit;
      }

      $stmt = $dbconnection->prepare("INSERT INTO video (judul_video, video_seo, link_youtube, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("sssss", $judul_video, $video_seo, $yt_code, $deskripsi, $nama_gambar);
      $stmt->execute();
      $stmt->close();
      header("location:../../media.php?module=".$module);
    }
  }


  // Update video
  elseif ($module=='video' AND $act=='update'){
    require_once __DIR__ . '/../../includes/bootstrap.php';
    require_once __DIR__ . '/../../includes/upload_helpers.php';
    require_post_csrf();

    $id           = (int)$_POST['id'];
    $judul_video  = $_POST['judul_video'];
    $video_seo    = seo_title($_POST['judul_video']);
    $deskripsi    = $_POST['deskripsi'];
    $yt_in        = trim($_POST['link_youtube']);
    $parts        = explode('watch?v=', $yt_in, 2);
    $yt_code      = isset($parts[1]) ? $parts[1] : $yt_in;

    $has_new = !empty($_FILES['fupload']['tmp_name']);
    if (!$has_new) {
      $stmt = $dbconnection->prepare("UPDATE video SET judul_video = ?, video_seo = ?, link_youtube = ?, deskripsi = ? WHERE id_video = ?");
      $stmt->bind_param("ssssi", $judul_video, $video_seo, $yt_code, $deskripsi, $id);
      $stmt->execute();
      $stmt->close();
      header("location:../../media.php?module=".$module);
    } else {
      try {
        $res = upload_image_secure($_FILES['fupload'], [
          'dest_dir'     => __DIR__ . '/../../../foto_video',
          'thumb_max_w'  => 180,
          'thumb_max_h'  => 180,
          'jpeg_quality' => 85,
          'prefix'       => 'video_',
        ]);
        $nama_gambar = $res['filename'];
      } catch (Throwable $e) {
        echo "<script>window.alert('Upload Gagal: " . e($e->getMessage()) . "'); window.location='../../media.php?module=video';</script>";
        exit;
      }

      // fetch old filename
      $old = null;
      $stmt = $dbconnection->prepare("SELECT gambar FROM video WHERE id_video = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($old_gbr);
      if ($stmt->fetch()) $old = $old_gbr;
      $stmt->close();

      // update with new file
      $stmt = $dbconnection->prepare("UPDATE video SET judul_video = ?, video_seo = ?, link_youtube = ?, deskripsi = ?, gambar = ? WHERE id_video = ?");
      $stmt->bind_param("sssssi", $judul_video, $video_seo, $yt_code, $deskripsi, $nama_gambar, $id);
      $stmt->execute();
      $stmt->close();

      // cleanup old files
      if (!empty($old)) {
        $base = basename($old);
        @unlink(__DIR__ . "/../../../foto_video/$base");
        @unlink(__DIR__ . "/../../../foto_video/small_$base");
      }

      header("location:../../media.php?module=".$module);
    }
  }

  closedb();
}
?>
