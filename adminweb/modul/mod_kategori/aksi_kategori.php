<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . "/../../../config/koneksi.php";
  require_once __DIR__ . "/../../../config/fungsi_seo.php";
  require_once __DIR__ . "/../../includes/bootstrap.php"; // <-- provides require_post_csrf(), csrf_field(), e(), etc.

  opendb();
  global $dbconnection; // <-- we use $dbconnection->prepare()

  $module = $_GET['module'];
  $act    = $_GET['act'];
  
  // Input kategori
  if ($module=='kategori' AND $act=='input'){
    require_post_csrf();

    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    if ($nama_kategori === '') {
      echo "<script>alert('Nama kategori wajib diisi');history.back();</script>";
      exit;
    }

    $kategori_seo = seo_title($nama_kategori);

    $stmt = $dbconnection->prepare("INSERT INTO kategori (nama_kategori, kategori_seo) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama_kategori, $kategori_seo);
    $stmt->execute();
    $stmt->close();

    header("Location: /admin?module=".$module);
    exit;
  }

  // Update kategori
  elseif ($module=='kategori' AND $act=='update'){
    require_post_csrf();

    $id = (int)($_POST['id'] ?? 0);
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $aktif = $_POST['aktif'] ?? '';

    if ($id <= 0 || $nama_kategori === '') {
      echo "<script>alert('Data tidak valid');history.back();</script>";
      exit;
    }

    $kategori_seo = seo_title($nama_kategori);

    $stmt = $dbconnection->prepare("UPDATE kategori SET nama_kategori = ?, kategori_seo = ?, aktif = ? WHERE id_kategori = ?");
    $stmt->bind_param("sssi", $nama_kategori, $kategori_seo, $aktif, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: /admin?module=".$module);
    exit;
  }
  closedb();
}
?>
