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
  $fileDir = __DIR__ . '/../../../files';

  //Wajibkan CSRF untuk semua POST
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
  }

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
        @unlink($fileDir . '/' . $namafile);
        @unlink($fileDir . '/small_' . $namafile);
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

    $lokasi_file = $_FILES['fupload']['tmp_name'] ?? '';
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
    try {
        $res = upload_file_secure($_FILES['fupload'], [
            'dest_dir'         => $fileDir,
            'allow_ext'        => ['jpg','jpeg','png','doc','docx','pdf'],
            'allow_mime_by_ext'=> [
                'pdf'  => ['application/pdf'],
                'doc'  => ['application/msword'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
                'jpg'  => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png'  => ['image/png'],
            ],
            'max_bytes'        => 10 * 1024 * 1024,
            'prefix'           => 'download_',
        ]);
        $nama_file_unik = $res['filename'];
    } catch (Throwable $e) {
        echo "<script>window.alert('Upload Gagal: " . e($e->getMessage()) . "'); location=history.back();</script>";
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

    $lokasi_file = $_FILES['fupload']['tmp_name'] ?? '';

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
    try {
        $res = upload_file_secure($_FILES['fupload'], [
            'dest_dir'         => $fileDir,
            'allow_ext'        => ['jpg','jpeg','png','doc','docx','pdf'],
            'allow_mime_by_ext'=> [
                'pdf'  => ['application/pdf'],
                'doc'  => ['application/msword'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
                'jpg'  => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png'  => ['image/png'],
            ],
            'max_bytes'        => 10 * 1024 * 1024,
            'prefix'           => 'download_',
        ]);
        $nama_file_unik = $res['filename'];
    } catch (Throwable $e) {
        echo "<script>window.alert('Upload gagal disimpan: " . e($e->getMessage()) . "'); location=history.back();</script>";
        exit;
    }

    // remove old file if provided
    if (!empty($old_file)) {
        @unlink($fileDir . '/' . basename($old_file));
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
