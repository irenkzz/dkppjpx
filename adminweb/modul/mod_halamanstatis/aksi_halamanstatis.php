<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
  exit;
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . '/../../includes/upload_helpers.php';
  include "../../../config/library.php";
  include "../../../config/fungsi_seo.php";
  include "../../../config/fungsi_thumb.php";
  opendb();

  $module = $_GET['module'] ?? '';
  $act    = $_GET['act'] ?? '';

  //Wajibkan CSRF untuk semua POST
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
  }

  // Ambil id sekali (POST untuk aksi mutasi)
  $id = isset($_POST['id']) ? (int)$_POST['id'] : (int)($_GET['id'] ?? 0);

  // Hapus halaman statis
  if ($module=='halamanstatis' AND $act=='hapus'){
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
      header("location:../../media.php?module=".$module);
    exit;
    }

    // 1) Ambil nama file gambar (prepared)
    $stmt = $dbconnection->prepare("SELECT gambar FROM halamanstatis WHERE id_halaman = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($gambar);
    $stmt->fetch();
    $stmt->close();

    // 2) Hapus file fisik (aman)
    if (!empty($gambar)) {
      $base = basename($gambar); // cegah path traversal
      @unlink("../../../foto_banner/$base");
      @unlink("../../../foto_banner/small_$base");
    }

    // 3) Hapus row di DB (prepared)
    $stmt = $dbconnection->prepare("DELETE FROM halamanstatis WHERE id_halaman = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("location:../../media.php?module=".$module);

    exit;
  }

  // Input halaman statis
  elseif ($module=='halamanstatis' AND $act=='input'){
    $lokasi_file = $_FILES['fupload']['tmp_name'];
    $tipe_file   = $_FILES['fupload']['type'];
    $nama_file   = $_FILES['fupload']['name'];
    $acak        = rand(1,99);
    $nama_gambar = $acak.$nama_file; 
    
    $judul       = $_POST['judul'];
    $judul_seo   = seo_title($_POST['judul']);
    $isi_halaman = $_POST['isi_halaman'];

    // Apabila tidak ada gambar yang di upload
    if (empty($lokasi_file)){
      $stmt = $dbconnection->prepare("INSERT INTO halamanstatis (judul, judul_seo, tgl_posting, isi_halaman) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $judul, $judul_seo, $tgl_sekarang, $isi_halaman);
      $stmt->execute();
      $stmt->close();
      header("location:../../media.php?module=".$module);
      exit;
    }
    // Apabila ada gambar yang di upload
    else{
     /*
      if ($tipe_file != "image/jpeg" AND $tipe_file != "image/pjpeg"){
        echo "<script>window.alert('Upload Gagal! Pastikan file yang di upload bertipe *.JPG');
              window.location=('../../media.php?module=halamanstatis')</script>";
      }
      else{
        //$folder = "../../../foto_banner/"; // folder untuk gambar halaman statis
        //$ukuran = 200;                     // gambar diperkecil jadi 200px (thumb)
        //UploadFoto($nama_gambar, $folder, $ukuran);
		    
        UploadBanner($nama_gambar);
        */
      try {
          $res = upload_image_secure($_FILES['fupload'], [
              'dest_dir'     => __DIR__ . '/../../../foto_banner', // keep your existing dir
              'thumb_max_w'  => 480,
              'thumb_max_h'  => 320,
              'jpeg_quality' => 85,
              'prefix'       => 'banner_',
              // 'preserve_alpha' => false, // uncomment to always convert to JPEG
          ]);
          $nama_gambar = $res['filename'];
      } catch (Throwable $e) {
          echo "<script>window.alert('Upload gagal: " . e($e->getMessage()) . "'); location=history.back();</script>";
          exit;
      }
        // Prepared INSERT + gambar
        $stmt = $dbconnection->prepare("
          INSERT INTO halamanstatis (judul, judul_seo, tgl_posting, isi_halaman, gambar)
          VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $judul, $judul_seo, $tgl_sekarang, $isi_halaman, $nama_gambar);
        $stmt->execute();
        $stmt->close();

        header("location:../../media.php?module=".$module);
      }
    }
  

  // Update halaman statis
  elseif ($module=='halamanstatis' AND $act=='update'){
    $lokasi_file = $_FILES['fupload']['tmp_name'];
    $tipe_file   = $_FILES['fupload']['type'];
    $nama_file   = $_FILES['fupload']['name'];
    $acak        = rand(1,99);
    $nama_gambar = $acak.$nama_file;
     
    $id          = $_POST['id'];    
    $judul       = $_POST['judul'];
    $judul_seo   = seo_title($_POST['judul']);
    $isi_halaman = $_POST['isi_halaman'];

    // Apabila gambar tidak diganti
    if (empty($lokasi_file)){
      // Prepared UPDATE tanpa mengubah gambar
      $stmt = $dbconnection->prepare("
        UPDATE halamanstatis
        SET judul = ?, judul_seo = ?, isi_halaman = ?
        WHERE id_halaman = ?
      ");
      $id_i = (int)$id;
      $stmt->bind_param("sssi", $judul, $judul_seo, $isi_halaman, $id_i);
      $stmt->execute();
      $stmt->close();
      
      header("location:../../media.php?module=".$module);
    }
    else{
      //Update dengan mengubah gambar
      try {
          $res = upload_image_secure($_FILES['fupload'], [
              'dest_dir'     => __DIR__ . '/../../../foto_banner',
              'thumb_max_w'  => 480,
              'thumb_max_h'  => 320,
              'jpeg_quality' => 85,
              'prefix'       => 'banner_',
          ]);
          $nama_gambar = $res['filename'];
      } catch (Throwable $e) {
          echo "<script>window.alert('Upload gagal: " . e($e->getMessage()) . "'); location=history.back();</script>";
          exit;
      }

       // Fetch OLD filename first (so we can delete it AFTER successful UPDATE)
        $old = null;
        $stmt = $dbconnection->prepare("SELECT gambar FROM halamanstatis WHERE id_halaman = ?");
        if (!$stmt) { die("Prepare failed: " . $dbconnection->error); }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($old_gambar);
        if ($stmt->fetch()) {
            $old = $old_gambar;
        }
        $stmt->close();

        // Prepared UPDATE including new gambar
        $stmt = $dbconnection->prepare("
            UPDATE halamanstatis
              SET judul = ?, judul_seo = ?, isi_halaman = ?, gambar = ?
            WHERE id_halaman = ?
        ");
        if (!$stmt) { die("Prepare failed: " . $dbconnection->error); }
        $stmt->bind_param("ssssi", $judul, $judul_seo, $isi_halaman, $nama_gambar, $id);
        $stmt->execute();
        $stmt->close();

        // Remove old files (after DB succeeds)
        if (!empty($old)) {
            $base = basename($old);
            @unlink(__DIR__ . '/../../../foto_banner/' . $base);
            @unlink(__DIR__ . '/../../../foto_banner/small_' . $base);
        }
        header("location:../../media.php?module=".$module);
     
    }
  }
  closedb();
}
?>
