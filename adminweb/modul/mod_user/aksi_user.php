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

  // Input user baru
if ($module=='user' && $act=='input') {

    // Amankan input
    $username     = trim($_POST['username'] ?? '');
    $password_raw = trim($_POST['password'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');

    // Validasi dasar
    if ($username === '' || $password_raw === '' || $nama_lengkap === '' || $email === '') {
        echo "<script>alert('Semua field wajib diisi.');history.back();</script>";
        closedb();
        exit;
    }

    // Hash password sesuai mekanisme login: md5($password.$kunci)
    
    include "../../../config/library.php"; // jika $kunci disimpan di sini

    $password = md5($password_raw . $kunci);

    // Generate id_session unik
    if (function_exists('random_bytes')) {
        $id_session = bin2hex(random_bytes(16));
    } else {
        $id_session = md5(uniqid('', true));
    }

    // User baru seharusnya tidak diblokir
    $blokir = 'N';

    // INSERT user baru
    $stmt = $dbconnection->prepare("
        INSERT INTO users (username, password, nama_lengkap, email, blokir, id_session)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if ($stmt) {
        $stmt->bind_param(
            "ssssss",
            $username,
            $password,
            $nama_lengkap,
            $email,
            $blokir,
            $id_session
        );
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('User baru berhasil ditambahkan.');window.location='../../media.php?module=user';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan user (prepare error).');history.back();</script>";
    }

    closedb();
}


    // Update user
  elseif ($module=='user' && $act=='update'){
    require_post_csrf();

    // Ambil input dasar
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $blokir_post  = $_POST['blokir'] ?? '';

    // Validasi isi
    if ($nama_lengkap === '' || $email === '') {
      echo "<script>alert('Nama lengkap dan email wajib diisi');history.back();</script>";
      closedb();
      exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      echo "<script>alert('Alamat email tidak valid');history.back();</script>";
      closedb();
      exit;
    }

    $is_admin   = (($_SESSION['leveluser'] ?? '') === 'admin');
    $username_s = $_SESSION['namauser'] ?? '';

    // Tentukan row user yang boleh diubah
    if ($is_admin) {
        // Admin: pakai id_session dari POST (hidden input)
        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            echo "<script>alert('ID tidak valid');history.back();</script>";
            closedb();
            exit;
        }

        // Admin boleh mengubah status blokir, clamp ke Y/N
        $blokir = ($blokir_post === 'Y') ? 'Y' : 'N';
    } else {
        // Operator: abaikan ID dari POST, pakai username session
        if ($username_s === '') {
            echo "<script>alert('Session tidak valid');history.back();</script>";
            closedb();
            exit;
        }

        // Cari id_session + blokir berdasarkan username (hanya miliknya sendiri)
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

        $id     = $row['id_session']; // paksa hanya milik sendiri
        $blokir = $row['blokir'];     // operator tidak bisa mengubah blokir
    }

    // Apakah password diubah?
    $rawpass = $_POST['password'] ?? '';

    if ($rawpass === '') {
      // Tanpa ubah password
      $stmt = $dbconnection->prepare("
        UPDATE users 
           SET nama_lengkap = ?, email = ?, blokir = ?
         WHERE id_session = ?
      ");
      $stmt->bind_param("ssss", $nama_lengkap, $email, $blokir, $id);
      $stmt->execute();
      $stmt->close();
    } else {
      // Ubah password juga – hash harus sama seperti login: md5(password.$kunci)
      $password = md5($rawpass.$kunci);

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
