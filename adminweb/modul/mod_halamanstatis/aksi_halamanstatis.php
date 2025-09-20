<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
  exit;
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
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
    $stmt = $koneksi->prepare("SELECT gambar FROM halamanstatis WHERE id_halaman = ?");
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
    $stmt = $koneksi->prepare("DELETE FROM halamanstatis WHERE id_halaman = ?");
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
      $stmt = $koneksi->prepare("INSERT INTO halamanstatis (judul, judul_seo, tgl_posting, isi_halaman) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $judul, $judul_seo, $tgl_sekarang, $isi_halaman);
      $stmt->execute();
      $stmt->close();
      header("location:../../media.php?module=".$module);
      exit;
    }
    // Apabila ada gambar yang di upload
    else{
      if ($tipe_file != "image/jpeg" AND $tipe_file != "image/pjpeg"){
        echo "<script>window.alert('Upload Gagal! Pastikan file yang di upload bertipe *.JPG');
              window.location=('../../media.php?module=halamanstatis')</script>";
      }
      else{
        //$folder = "../../../foto_banner/"; // folder untuk gambar halaman statis
        //$ukuran = 200;                     // gambar diperkecil jadi 200px (thumb)
        //UploadFoto($nama_gambar, $folder, $ukuran);
		    
        UploadBanner($nama_gambar);
        // Prepared INSERT + gambar
        $stmt = $koneksi->prepare("
          INSERT INTO halamanstatis (judul, judul_seo, tgl_posting, isi_halaman, gambar)
          VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $judul, $judul_seo, $tgl_skrg, $isi_halaman, $nama_gambar);
        $stmt->execute();
        $stmt->close();

        header("location:../../media.php?module=".$module);
      }
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
      $stmt = $koneksi->prepare("
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
      if ($tipe_file != "image/jpeg" AND $tipe_file != "image/pjpeg"){
        echo "<script>window.alert('Upload Gagal! Pastikan file yang di upload bertipe *.JPG');
              window.location=('../../media.php?module=halamanstatis')</script>";
      }
      else{
        //$folder = "../../../foto_banner/"; // folder untuk gambar halaman statis
        //$ukuran = 200;                    // gambar diperkecil jadi 200px (thumb)
        //UploadFoto($nama_gambar, $folder, $ukuran);
		UploadBanner($nama_gambar);

        $update = "UPDATE halamanstatis SET judul       = '$judul',
                                            judul_seo   = '$judul_seo', 
                                            isi_halaman = '$isi_halaman',
                                            gambar      = '$nama_gambar'
                                      WHERE id_halaman  = '$id'";
        querydb($update);
      
        header("location:../../media.php?module=".$module);
      }
    }
  }
  closedb();
}
?>
