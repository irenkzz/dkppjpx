<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  include "../../../config/koneksi.php";
  include "../../../config/library.php";
  opendb();

  $module = $_GET['module'];
  $act    = $_GET['act'];

  // Hapus download
  if ($module=='download' AND $act=='hapus'){
    $query = "SELECT nama_file FROM download WHERE id_download='$_GET[id]'";
    $hapus = querydb($query);
    $r     = $hapus->fetch_array();
    
    if ($r['nama_file']!=''){
      $namafile = $r['nama_file'];
      
      // hapus filenya
      unlink("../../../files/$namafile");   
      unlink("../../../files/small_$namafile");   

      // hapus data download di database 
      querydb("DELETE FROM download WHERE id_download='$_GET[id]'");      
    }
    else{
      querydb("DELETE FROM download WHERE id_download='$_GET[id]'");      
    }
    header("location:../../media.php?module=".$module);
  }

  // Input download
  elseif ($module=='download' AND $act=='input'){
    $ekstensi =  array('jpg','jpeg','png','doc','docx','pdf');
    $lokasi_file    = $_FILES['fupload']['tmp_name'];
    $nama_file      = $_FILES['fupload']['name'];    
    $acak           = rand(1,99);
    $nama_file_unik = $acak.$nama_file; 
    $ext = pathinfo($nama_file, PATHINFO_EXTENSION);

    $judul = $_POST['judul'];
    
    // Apabila tidak ada file yang diupload
    if (empty($lokasi_file)){
      $input = "INSERT INTO download(judul, tgl_posting) VALUES('$judul', '$tgl_sekarang')";
      querydb($input);

      header("location:../../media.php?module=".$module);
    }
    else{
		if(!in_array($ext,$ekstensi) ) {
        echo "<script>window.alert('Upload Gagal! Pastikan file yang di upload bertipe yang tidak aneh');
              location=history.back();</script>";
		}else{
		  // folder untuk menyimpan file yang di upload
		  $folder = "../../../files/";
		  $file_upload = $folder . $nama_file_unik;
		  // upload file
		  move_uploaded_file($_FILES["fupload"]["tmp_name"], $file_upload);
		
		  $input = "INSERT INTO download(judul, nama_file, tgl_posting) VALUES('$judul', '$nama_file_unik', '$tgl_sekarang')";
		  querydb($input);

		  header("location:../../media.php?module=".$module);
		}
    }
  }

  // Update donwload
  elseif ($module=='download' AND $act=='update'){
    $ekstensi =  array('jpg','jpeg','png','doc','docx','pdf');
    $lokasi_file    = $_FILES['fupload']['tmp_name'];
    $nama_file      = $_FILES['fupload']['name'];
    $acak           = rand(1,99);
    $nama_file_unik = $acak.$nama_file; 
	$ext = pathinfo($nama_file, PATHINFO_EXTENSION);

    $id    = $_POST['id'];
    $judul = $_POST['judul'];

    // Apabila file tidak diganti
    if (empty($lokasi_file)){
      $update = "UPDATE download SET judul='$judul' WHERE id_download='$id'";
      querydb($update);
      
      header("location:../../media.php?module=".$module);
    }
    else{
		if(!in_array($ext,$ekstensi) ) {
        echo "<script>window.alert('Upload Gagal! Pastikan file yang di upload bertipe yang tidak aneh');
              location=history.back();</script>";
		}else{
		  // folder untuk menyimpan file yang di upload
		  $folder = "../../../files/";
		  $file_upload = $folder . $nama_file_unik;
		  // upload file
		  move_uploaded_file($_FILES["fupload"]["tmp_name"], $file_upload);
		  unlink("../../../files/$_POST[fupload_hapus]");
		  $update = "UPDATE download SET judul='$judul', nama_file='$nama_file_unik' WHERE id_download='$id'";
		  querydb($update);
		  
		  header("location:../../media.php?module=".$module);
		}
    }
  }
  closedb();
}
?>
