<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
  echo "<link href=\"../../css/style_login.css\" rel=\"stylesheet\" type=\"text/css\" />
        <div id=\"login\"><h1 class=\"fail\">Untuk mengakses modul, Anda harus login dulu.</h1>
        <p class=\"fail\"><a href=\"../../index.php\">LOGIN</a></p></div>";  
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  include "../../../config/fungsi_seo.php";
  require_once __DIR__ . '/../../includes/upload_helpers.php';
  opendb();

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus slider
  if ($module === 'slider' && $act === 'hapus') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $stmt = $dbconnection->prepare("SELECT gmb_slider FROM slider WHERE id_slider = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $hapus = $stmt->get_result();
    $r = $hapus->fetch_array();
    $stmt->close();

    if ($r && !empty($r['gmb_slider'])) {
      $namafile = basename($r['gmb_slider']);
      @unlink("../../../foto_slider/$namafile");
      @unlink("../../../foto_slider/small_$namafile");
    }

    $stmt = $dbconnection->prepare("DELETE FROM slider WHERE id_slider = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("location:../../media.php?module=".$module);
    exit;
  }

  // Input slider
  elseif ($module === 'slider' && $act === 'input') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
    require_post_csrf();

    $link = trim($_POST['link'] ?? '');

    if (empty($_FILES['fupload']['tmp_name'])) {
        echo "<script>window.alert('Gambar belum dipilih');
              window.location=('../../media.php?module=".$module."')</script>";
        exit;
    }

    try {
        $res = upload_image_secure($_FILES['fupload'], [
            'dest_dir'     => __DIR__ . '/../../../foto_slider',
            'thumb_max_w'  => 600,
            'thumb_max_h'  => 300,
            'jpeg_quality' => 85,
            'prefix'       => 'slider_',
            'max_size'     => 2 * 1024 * 1024,
            'allowed_types'=> ['image/jpeg','image/png']
        ]);
        $nama_gambar = $res['filename'];
    } catch (Throwable $e) {
        echo "<script>window.alert('Upload Gagal: ". e($e->getMessage()) ."'); history.back();</script>";
        exit;
    }

    exec_prepared(
        "INSERT INTO slider (gmb_slider, link) VALUES (?, ?)",
        "ss",
        [$nama_gambar, $link]
    );

    header("location:../../media.php?module=".$module);
    exit;
  }

  // Update slider
  elseif ($module === 'slider' && $act === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
        http_response_code(405); 
        exit; 
    }
    require_post_csrf();

    $id   = (int)($_POST['id'] ?? 0);
    $link = trim($_POST['link'] ?? '');
    if ($id <= 0) { header("location:../../media.php?module=".$module); exit; }

    $has_new = !empty($_FILES['fupload']['tmp_name']);

    // no new image → update text only (prepared)
    if (!$has_new) {
        exec_prepared("UPDATE slider SET link = ? WHERE id_slider = ?", "si", [$link, $id]);
        header("location:../../media.php?module=".$module);
        exit;
    }

    // fetch old image name (for cleanup after successful update)
    $old = null;
    $stmt = $dbconnection->prepare("SELECT gmb_slider FROM slider WHERE id_slider = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_gmb);
    if ($stmt->fetch()) { $old = $old_gmb; }
    $stmt->close();

    // secure upload (size/type enforced)
    try {
        $res = upload_image_secure($_FILES['fupload'], [
            'dest_dir'      => __DIR__ . '/../../../foto_slider',
            'thumb_max_w'   => 600,
            'thumb_max_h'   => 300,
            'jpeg_quality'  => 85,
            'prefix'        => 'slider_',
            'max_size'      => 2 * 1024 * 1024,
            'allowed_types' => ['image/jpeg', 'image/png'],
        ]);
        $nama_gambar = $res['filename'];
    } catch (Throwable $e) {
        echo "<script>window.alert('Upload Gagal: ". e($e->getMessage()) ."'); history.back();</script>";
        exit;
    }

    // update with prepared statement
    exec_prepared(
        "UPDATE slider SET gmb_slider = ?, link = ? WHERE id_slider = ?",
        "ssi",
        [$nama_gambar, $link, $id]
    );

    // cleanup old files safely (after DB success)
    if (!empty($old)) {
        $base = basename($old);
        @unlink(__DIR__ . "/../../../foto_slider/" . $base);
        @unlink(__DIR__ . "/../../../foto_slider/small_" . $base);
    }

    header("location:../../media.php?module=".$module);
    exit;
  }

  closedb();
}
?>
