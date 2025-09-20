<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  include "../../../config/koneksi.php";
  opendb();

  $module = $_GET['module'];
  $act    = $_GET['act'];
  $kunci = base64_decode($key);
  // Input user
  if ($module=='user' AND $act=='input'){
    $username     = $_POST['username'];
    $password     = md5($_POST['password'].$kunci);
    $nama_lengkap = $_POST['nama_lengkap']; 
    $email        = $_POST['email'];
    
    $input = "INSERT INTO users(username, 
                                password, 
                                nama_lengkap, 
                                email) 
	                       VALUES('$username', 
                                '$password', 
                                '$nama_lengkap', 
                                '$email')";
    querydb($input);
    header("location:../../media.php?module=".$module);
  }

  // Update user
  elseif ($module=='user' AND $act=='update'){
    $id           = $_POST['id'];
    $nama_lengkap = $_POST['nama_lengkap']; 
    $email        = $_POST['email'];
    $blokir       = $_POST['blokir'];
 
    // Apabila password tidak diubah (kosong)
    if (empty($_POST['password'])) {
      $update = "UPDATE users SET nama_lengkap = '$nama_lengkap',
                                         email = '$email',
                                        blokir = '$blokir'   
                              WHERE id_session = '$id'";
      querydb($update);
    }
    // Apabila password diubah
    else{
      $password = md5($_POST['password'].$kunci);
      $update = "UPDATE users SET nama_lengkap = '$nama_lengkap',
                                        email  = '$email',
                                        blokir = '$blokir',
                                      password = '$password'    
                              WHERE id_session = '$id'";
      querydb($update);

    }
    header("location:../../media.php?module=".$module);
  }
  closedb();
}
?>
