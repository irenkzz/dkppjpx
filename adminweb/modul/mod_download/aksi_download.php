<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . '/../../includes/upload_helpers.php';
  include "../../../config/library.php";
  opendb();

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus download
  if ($module === 'download' && $act === 'hapus') {
    require_post_csrf(); // enforce POST + CSRF

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        header("Location: ../../media.php?module=" . $module);
        exit;
    }

     // fetch filename securely
    $stmt = $dbconnection->prepare("SELECT nama_file FROM download WHERE id_download = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $r = $result->fetch_assoc();
    $stmt->close();

    if ($r && !empty($r['nama_file'])) {
        $namafile = basename($r['nama_file']); // prevent path traversal
        @unlink("../../../files/$namafile");
        @unlink("../../../files/small_$namafile");
    }

    // delete row securely
    $stmt = $dbconnection->prepare("DELETE FROM download WHERE id_download = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../../media.php?module=" . $module);
    exit;
  }

  // Input download
  elseif ($module === 'download' && $act === 'input') {
    require_post_csrf();

    $allowed_ext = ['jpg','jpeg','png','doc','docx','pdf'];
    $lokasi_file = $_FILES['fupload']['tmp_name'] ?? '';
    $nama_file   = $_FILES['fupload']['name'] ?? '';
    $ext         = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
    $judul       = $_POST['judul'] ?? '';

    // no file uploaded
    if (empty($lokasi_file)) {
        $stmt = $dbconnection->prepare("INSERT INTO download (judul, tgl_posting) VALUES (?, ?)");
        $stmt->bind_param("ss", $judul, $tgl_sekarang);
        $stmt->execute();
        $stmt->close();
        header("Location: ../../media.php?module=".$module);
        exit;
    }
    
		// with file
    if (!in_array($ext, $allowed_ext, true)) {
        echo "<script>window.alert('Upload Gagal! Pastikan file bertipe yang diizinkan'); location=history.back();</script>";
        exit;
    }

    $acak = random_int(1, 99);
    $nama_file_unik = $acak . $nama_file;

    $folder = "../../../files/";
    $file_upload = $folder . $nama_file_unik;
    if (!move_uploaded_file($lokasi_file, $file_upload)) {
        echo "<script>window.alert('Upload gagal disimpan'); location=history.back();</script>";
        exit;
    }

    $stmt = $dbconnection->prepare("INSERT INTO download (judul, nama_file, tgl_posting) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $judul, $nama_file_unik, $tgl_sekarang);
    $stmt->execute();
    $stmt->close();

    header("Location: ../../media.php?module=".$module);
    exit;
    }
  
  // Update donwload
  elseif ($module === 'download' && $act === 'update') {
    require_post_csrf();

    $allowed_ext = ['jpg','jpeg','png','doc','docx','pdf'];
    $lokasi_file = $_FILES['fupload']['tmp_name'] ?? '';
    $nama_file   = $_FILES['fupload']['name'] ?? '';
    $ext         = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

    $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $judul       = $_POST['judul'] ?? '';
    $old_file    = $_POST['fupload_hapus'] ?? '';

    if ($id <= 0) {
        header("Location: ../../media.php?module=".$module);
        exit;
    }
    
		// no new file: update title only
    if (empty($lokasi_file)) {
        $stmt = $dbconnection->prepare("UPDATE download SET judul = ? WHERE id_download = ?");
        $stmt->bind_param("si", $judul, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: ../../media.php?module=".$module);
        exit;
    }

    // with new file
    if (!in_array($ext, $allowed_ext, true)) {
        echo "<script>window.alert('Upload Gagal! Pastikan file bertipe yang diizinkan'); location=history.back();</script>";
        exit;
    }

    $acak = random_int(1, 99);
    $nama_file_unik = $acak . $nama_file;

    $folder = "../../../files/";
    $file_upload = $folder . $nama_file_unik;
    if (!move_uploaded_file($lokasi_file, $file_upload)) {
        echo "<script>window.alert('Upload gagal disimpan'); location=history.back();</script>";
        exit;
    }

    // remove old file if provided
    if (!empty($old_file)) {
        @unlink("../../../files/$old_file");
    }

    $stmt = $dbconnection->prepare("UPDATE download SET judul = ?, nama_file = ? WHERE id_download = ?");
    $stmt->bind_param("ssi", $judul, $nama_file_unik, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../../media.php?module=".$module);
    exit;
  }
 
  closedb();
}
?>
