<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . "/../../includes/bootstrap.php"; // require_post_csrf(), csrf_field(), e(), etc.
  opendb();
 
  $module = $_GET['module'] ?? '';
  $act    = $_GET['act'] ?? '';
  $kunci  = base64_decode($key ?? '');

  // Input user
  if ($module=='user' && $act=='input'){
    require_post_csrf();

    // Hanya admin yang boleh menambah user
    if (($_SESSION['leveluser'] ?? '') !== 'admin') {
        echo "<script>alert('Anda tidak berhak menambah user.');history.back();</script>";
        closedb();
        exit;
    }

    $username     = trim($_POST['username'] ?? '');
    $rawpass      = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');

    if ($username === '' || $rawpass === '' || $nama_lengkap === '' || $email === '') {
      echo "<script>alert('Semua field wajib diisi');history.back();</script>";
      closedb();
      exit;
    }

    $password = md5($rawpass.$kunci); // keep for compatibility with cek_login.php

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

    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $blokir_post  = $_POST['blokir'] ?? '';

    if ($nama_lengkap === '' || $email === '') {
      echo "<script>alert('Data tidak valid');history.back();</script>";
      closedb();
      exit;
    }

    $is_admin   = (($_SESSION['leveluser'] ?? '') === 'admin');
    $username_s = $_SESSION['namauser'] ?? '';

    // Tentukan target row yang boleh diubah
    if ($is_admin) {
        // Admin: boleh gunakan id dari POST (id_session)
        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            echo "<script>alert('ID tidak valid');history.back();</script>";
            closedb();
            exit;
        }

        // Admin boleh ubah blokir
        $blokir = $blokir_post;
    } else {
        // Operator: abaikan ID dari POST, pakai username session
        if ($username_s === '') {
            echo "<script>alert('Session tidak valid');history.back();</script>";
            closedb();
            exit;
        }

        // Cari id_session + blokir berdasarkan username
        $stmt = $dbconnection->prepare("SELECT id_session, blokir FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username_s);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            echo "<script>alert('User tidak ditemukan');history.back();</script>";
            closedb();
            exit;
        }

        $id     = $row['id_session']; // paksa id ke milik sendiri
        $blokir = $row['blokir'];     // operator tidak bisa mengubah blokir
    }

    // password diubah?
    $rawpass = $_POST['password'] ?? '';
    if ($rawpass === '') {
      $stmt = $dbconnection->prepare("
        UPDATE users 
           SET nama_lengkap = ?, email = ?, blokir = ?
         WHERE id_session = ?
      ");
      $stmt->bind_param("ssss", $nama_lengkap, $email, $blokir, $id);
      $stmt->execute();
      $stmt->close();
    } else {
      $password = md5($rawpass.$kunci); // keep for compatibility
      $stmt = $dbconnection->prepare("
        UPDATE users 
           SET nama_lengkap = ?, email = ?, blokir = ?, password = ?
         WHERE id_session = ?
      ");
      $stmt->bind_param("sssss", $nama_lengkap, $email, $blokir, $password, $id);
      $stmt->execute();
      $stmt->close();
    }

    header("location:../../media.php?module=".$module);
    exit;
  }

  closedb();
}
?>
