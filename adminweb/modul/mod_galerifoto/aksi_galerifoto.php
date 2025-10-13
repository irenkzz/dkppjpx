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

  // Hapus galeri foto
  if ($module=='galerifoto' AND $act=='hapus'){
    require_post_csrf();
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) { 
      header("location:../../media.php?module=".$module); 
      exit; 
    }

    // fetch filename (prepared)
    $stmt = $dbconnection->prepare("SELECT gbr_gallery FROM gallery WHERE id_gallery = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($gbr);
    $has = $stmt->fetch();
    $stmt->close();

    // delete row (prepared)
    $stmt = $dbconnection->prepare("DELETE FROM gallery WHERE id_gallery = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // unlink files safely
    if ($has && !empty($gbr)) {
      $base = basename($gbr);
      @unlink(__DIR__ . "/../../../img_galeri/" . $base);
      @unlink(__DIR__ . "/../../../img_galeri/kecil_" . $base);
    }

    header("location:../../media.php?module=".$module);
    exit;
  }

  // Input galeri foto
  elseif ($module=='galerifoto' AND $act=='input'){
    require_post_csrf();

    $judul_galeri = $_POST['judul_galeri'];
    $galeri_seo   = seo_title($_POST['judul_galeri']);
    $album        = $_POST['album'];
    $keterangan   = $_POST['keterangan'];

    $has_file = !empty($_FILES['fupload']['tmp_name']);

    if (!$has_file){
      // prepared insert (no image)
      $stmt = $dbconnection->prepare("
        INSERT INTO gallery (jdl_gallery, gallery_seo, id_album, keterangan)
        VALUES (?, ?, ?, ?)
      ");
      $stmt->bind_param("ssis", $judul_galeri, $galeri_seo, $album, $keterangan);
      $stmt->execute();
      $stmt->close();
      header("location:../../media.php?module=".$module);
    } else {
      // secure upload → img_galeri + kecil_ thumbnail
      try {
        $res = upload_image_secure($_FILES['fupload'], [
          'dest_dir'     => __DIR__ . '/../../../img_galeri',
          'thumb_max_w'  => 180,
          'thumb_max_h'  => 180,
          'jpeg_quality' => 85,
          'prefix'       => 'galeri_',
        ]);
        $nama_foto = $res['filename'];
      } catch (Throwable $e) {
        echo "<script>window.alert('Upload Gagal: " . e($e->getMessage()) . "'); window.location=('../../media.php?module=galerifoto')</script>";
        exit;
      }

      $stmt = $dbconnection->prepare("
        INSERT INTO gallery (jdl_gallery, gallery_seo, id_album, keterangan, gbr_gallery)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("ssiss", $judul_galeri, $galeri_seo, $album, $keterangan, $nama_foto);
      $stmt->execute();
      $stmt->close();
      header("location:../../media.php?module=".$module);
    }
  }

  // Update galeri foto
  elseif ($module=='galerifoto' AND $act=='update'){
    require_post_csrf();

    $id           = (int)$_POST['id'];
    $judul_galeri = $_POST['judul_galeri'];
    $galeri_seo   = seo_title($_POST['judul_galeri']);
    $album        = $_POST['album'];
    $keterangan   = $_POST['keterangan'];

    $has_new = !empty($_FILES['fupload']['tmp_name']);
    if (!$has_new) {
      // prepared update (no image change)
      $stmt = $dbconnection->prepare("
        UPDATE gallery SET jdl_gallery = ?, gallery_seo = ?, id_album = ?, keterangan = ? WHERE id_gallery = ?
      ");
      $stmt->bind_param("ssisi", $judul_galeri, $galeri_seo, $album, $keterangan, $id);
      $stmt->execute();
      $stmt->close();
      header("location:../../media.php?module=".$module);
    } else {
      // upload new image securely
      try {
        $res = upload_image_secure($_FILES['fupload'], [
          'dest_dir'     => __DIR__ . '/../../../img_galeri',
          'thumb_max_w'  => 180,
          'thumb_max_h'  => 180,
          'jpeg_quality' => 85,
          'prefix'       => 'galeri_',
        ]);
        $nama_foto = $res['filename'];
      } catch (Throwable $e) {
        echo "<script>window.alert('Upload Gagal: " . e($e->getMessage()) . "'); window.location=('../../media.php?module=galerifoto')</script>";
        exit;
      }

      // fetch old file for cleanup post-update
      $old = null;
      $stmt = $dbconnection->prepare("SELECT gbr_gallery FROM gallery WHERE id_gallery = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($old_gbr);
      if ($stmt->fetch()) $old = $old_gbr;
      $stmt->close();

      // prepared update with new filename
      $stmt = $dbconnection->prepare("
        UPDATE gallery SET jdl_gallery = ?, gallery_seo = ?, id_album = ?, keterangan = ?, gbr_gallery = ? WHERE id_gallery = ?
      ");
      $stmt->bind_param("ssissi", $judul_galeri, $galeri_seo, $album, $keterangan, $nama_foto, $id);
      $stmt->execute();
      $stmt->close();

      // cleanup old files (best-effort)
      if (!empty($old)) {
        $base = basename($old);
        @unlink(__DIR__ . "/../../../img_galeri/" . $base);
        @unlink(__DIR__ . "/../../../img_galeri/kecil_" . $base);
      }

      header("location:../../media.php?module=".$module);
    }
  }

  closedb();
}
?>
