<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . "/../../../config/koneksi.php";
  require_once __DIR__ . "/../../includes/bootstrap.php"; // require_post_csrf(), csrf_field(), e(), etc.
  opendb();
  global $dbconnection;

  $module = $_GET['module'] ?? '';
  $act    = $_GET['act'] ?? '';
  $kunci  = base64_decode($key ?? '');

  // Input user
  if ($module=='user' && $act=='input'){
    require_post_csrf();

    $username     = trim($_POST['username'] ?? '');
    $rawpass      = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');

    if ($username === '' || $rawpass === '' || $nama_lengkap === '' || $email === '') {
      echo "<script>alert('Semua field wajib diisi');history.back();</script>";
      exit;
    }

    $password = md5($rawpass.$kunci); // keep for compatibility

    $stmt = $dbconnection->prepare("INSERT INTO users (username, password, nama_lengkap, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $nama_lengkap, $email);
    $stmt->execute();
    $stmt->close();

    header("location:../../media.php?module=".$module);
    exit;
  }

    // Update user
  elseif ($module=='user' && $act=='update'){
    require_post_csrf();

    $id           = (int)($_POST['id'] ?? 0);
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $blokir       = $_POST['blokir'] ?? '';

    if ($id <= 0 || $nama_lengkap === '' || $email === '') {
      echo "<script>alert('Data tidak valid');history.back();</script>";
      exit;
    }

    // password diubah?
    $rawpass = $_POST['password'] ?? '';
    if ($rawpass === '') {
      $stmt = $dbconnection->prepare("
        UPDATE users 
          SET nama_lengkap = ?, email = ?, blokir = ?
        WHERE id_session = ?
      ");
      $stmt->bind_param("sssi", $nama_lengkap, $email, $blokir, $id);
      $stmt->execute();
      $stmt->close();
    } else {
      $password = md5($rawpass.$kunci); // keep for compatibility
      $stmt = $dbconnection->prepare("
        UPDATE users 
          SET nama_lengkap = ?, email = ?, blokir = ?, password = ?
        WHERE id_session = ?
      ");
      $stmt->bind_param("ssssi", $nama_lengkap, $email, $blokir, $password, $id);
      $stmt->execute();
      $stmt->close();
    }

    header("location:../../media.php?module=".$module);
    exit;
  }
  closedb();
}
?>
