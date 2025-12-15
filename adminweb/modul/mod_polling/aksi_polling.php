<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  if (!isset($_SESSION['leveluser']) || $_SESSION['leveluser'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
  }

  require_once __DIR__ . "/../../includes/bootstrap.php"; // provides require_post_csrf(), csrf_check(), e(), etc.

  opendb();
  global $dbconnection;

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus polling
  if ($module=='polling' AND $act=='hapus'){
    require_post_csrf();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      echo "<script>alert('Data tidak valid');history.back();</script>";
      exit;
    }

    $stmt = $dbconnection->prepare("DELETE FROM poling WHERE id_poling = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: /admin?module=".$module);
    exit;
  }

  // Input polling
  elseif ($module=='polling' AND $act=='input'){
    require_post_csrf();

    $pilihan = trim($_POST['pilihan'] ?? '');
    $status  = $_POST['status'] ?? '';

    if ($pilihan === '' || $status === '') {
      echo "<script>alert('Pilihan dan status wajib diisi');history.back();</script>";
      exit;
    }

    $stmt = $dbconnection->prepare("INSERT INTO poling (pilihan, status) VALUES (?, ?)");
    $stmt->bind_param("ss", $pilihan, $status);
    $stmt->execute();
    $stmt->close();

    header("Location: /admin?module=".$module);
    exit;
  }

  // Update polling
  elseif ($module=='polling' AND $act=='update'){
    require_post_csrf();

    $id      = (int)($_POST['id'] ?? 0);
    $pilihan = trim($_POST['pilihan'] ?? '');
    $status  = $_POST['status'] ?? '';
    $aktif   = $_POST['aktif'] ?? '';

    if ($id <= 0 || $pilihan === '' || $status === '') {
      echo "<script>alert('Data tidak valid');history.back();</script>";
      exit;
    }

    $stmt = $dbconnection->prepare("UPDATE poling SET pilihan = ?, status = ?, aktif = ? WHERE id_poling = ?");
    $stmt->bind_param("sssi", $pilihan, $status, $aktif, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: /admin?module=".$module);
    exit;
  }

  closedb();
}
?>
