<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/upload_helpers.php';
include "../../../config/library.php";
include "../../../config/fungsi_seo.php";

// auth gate (same behavior as before)
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])) {
  echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location='../../index.php'</script>";
  exit;
}

opendb();
global $dbconnection;

$module = $_GET['module'] ?? '';
$act    = $_GET['act'] ?? '';

// CSRF required for any POST mutation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
}

/**
 * Helpers
 */
function berita_delete_file_if_any(int $id): void {
  global $dbconnection;
  $stmt = $dbconnection->prepare("SELECT gambar FROM berita WHERE id_berita = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($gambar);
  $stmt->fetch();
  $stmt->close();

  if (!empty($gambar)) {
    $base = basename($gambar);
    @unlink(__DIR__ . "/../../../foto_berita/$base");
    @unlink(__DIR__ . "/../../../foto_berita/small_$base");
    @unlink(__DIR__ . "/../../../foto_berita/medium_$base"); // if you had this size previously
  }
}

/**
 * DELETE (POST only)
 * UI should submit: POST to aksi_berita.php?module=berita&act=hapus with {csrf,id}
 */
if ($module === 'berita' && $act === 'hapus') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed.');
  }
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id <= 0) {
    header("Location: ../../media.php?module=".$module);
    exit;
  }

  // remove files then row
  berita_delete_file_if_any($id);

  $stmt = $dbconnection->prepare("DELETE FROM berita WHERE id_berita = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();

  header("Location: ../../media.php?module=".$module);
  exit;
}

/**
 * INSERT (POST)
 */
if ($module === 'berita' && $act === 'input') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed.');
  }

  $judul       = $_POST['judul'] ?? '';
  $judul_seo   = seo_title($judul);
  $kategori    = $_POST['kategori'] ?? '';
  $isi_berita  = $_POST['isi_berita'] ?? '';
  if (!empty($_POST['tag_seo']) && is_array($_POST['tag_seo'])) {
    $tag = implode(',', $_POST['tag_seo']);
  } else {
      $tag = '';
  }

  $nama_gambar = ''; // default no image

  // if file uploaded, validate + re-encode via helper
  if (!empty($_FILES['fupload']['tmp_name'])) {
    try {
      $res = upload_image_secure($_FILES['fupload'], [
        'dest_dir'     => __DIR__ . '/../../../foto_berita',
        'thumb_max_w'  => 390,  // keeps your legacy "medium" size roughly
        'thumb_max_h'  => 390,
        'jpeg_quality' => 85,
        'prefix'       => 'berita_',
      ]);
      $nama_gambar = $res['filename']; // store this
      // our helper also creates small_* thumb by default
    } catch (Throwable $e) {
      echo "<script>window.alert('Upload gagal: " . e($e->getMessage()) . "'); location=history.back();</script>";
      exit;
    }
  }

  // prepared INSERT
  $stmt = $dbconnection->prepare("
    INSERT INTO berita (judul, judul_seo, id_kategori, username, isi_berita, hari, tanggal, jam, tag, gambar)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $username = $_SESSION['namauser'] ?? '';
  $hari     = $hari_ini;
  $tanggal  = $tgl_sekarang;
  $jam      = $jam_sekarang;

  $stmt->bind_param(
    "ssisssssss",
    $judul, $judul_seo, $kategori, $username, $isi_berita, $hari, $tanggal, $jam, $tag, $nama_gambar
  );
  $stmt->execute();
  $stmt->close();

  header("Location: ../../media.php?module=".$module);
  exit;
}

/**
 * UPDATE (POST)
 */
if ($module === 'berita' && $act === 'update') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed.');
  }

  $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $judul       = $_POST['judul'] ?? '';
  $judul_seo   = seo_title($judul);
  $kategori    = $_POST['kategori'] ?? '';
  $isi_berita  = $_POST['isi_berita'] ?? '';
  //$tag         = '';
  if (!empty($_POST['tag_seo']) && is_array($_POST['tag_seo'])) {
    $tag = implode(',', $_POST['tag_seo']);
  }
  else{
    $tag = '';
  }

  if ($id <= 0) {
    header("Location: ../../media.php?module=".$module);
    exit;
  }

  // new image?
  $has_new = !empty($_FILES['fupload']['tmp_name']);
  if (!$has_new) {
    // update text-only
    $stmt = $dbconnection->prepare("
      UPDATE berita
         SET judul = ?, judul_seo = ?, id_kategori = ?, isi_berita = ?, tag = ?
       WHERE id_berita = ?
    ");
    $stmt->bind_param("ssissi", $judul, $judul_seo, $kategori, $isi_berita, $tag, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../../media.php?module=".$module);
    exit;
  }

  // handle new image upload
  try {
    $res = upload_image_secure($_FILES['fupload'], [
      'dest_dir'     => __DIR__ . '/../../../foto_berita',
      'thumb_max_w'  => 390,
      'thumb_max_h'  => 390,
      'jpeg_quality' => 85,
      'prefix'       => 'berita_',
    ]);
    $nama_gambar = $res['filename'];
  } catch (Throwable $e) {
    echo "<script>window.alert('Upload gagal: " . e($e->getMessage()) . "'); location=history.back();</script>";
    exit;
  }

  // fetch old to delete after successful update
  $old = null;
  $stmt = $dbconnection->prepare("SELECT gambar FROM berita WHERE id_berita = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($old_gambar);
  if ($stmt->fetch()) $old = $old_gambar;
  $stmt->close();

  // update including image
  $stmt = $dbconnection->prepare("
    UPDATE berita
       SET judul = ?, judul_seo = ?, id_kategori = ?, isi_berita = ?, tag = ?, gambar = ?
     WHERE id_berita = ?
  ");
  $stmt->bind_param("ssisssi", $judul, $judul_seo, $kategori, $isi_berita, $tag, $nama_gambar, $id);
  $stmt->execute();
  $stmt->close();

  // remove old files
  if (!empty($old)) {
    $base = basename($old);
    @unlink(__DIR__ . "/../../../foto_berita/$base");
    @unlink(__DIR__ . "/../../../foto_berita/small_$base");
    @unlink(__DIR__ . "/../../../foto_berita/medium_$base");
  }

  header("Location: ../../media.php?module=".$module);
  exit;
}

closedb();
