<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = 'index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	include "../config/koneksi.php";
	include "../config/fungsi_indotgl.php";
	opendb();
	include "header.php";
	
	include "menu.php";
?>

      <!-- =============================================== -->

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
		<?php include "content.php"; ?>
      </div><!-- /.content-wrapper -->

<?php
include "footer.php";
closedb();
}
?>