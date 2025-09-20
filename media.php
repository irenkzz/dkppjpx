<?php 
  ob_start();	
  session_start();
  include "config/koneksi.php";
  opendb();
  // Panggil semua fungsi yang dibutuhkan (semuanya ada di folder config)
  include "config/fungsi_indotgl.php";
  include "config/class_paging.php";
  include "config/fungsi_combobox.php";
  include "config/library.php";
  include "config/fungsi_autolink.php";
  include "config/fungsi_badword.php";
  include "config/fungsi_kalender.php";

  // Memilih template yang aktif saat ini
  $pilih_template=querydb("SELECT folder FROM templates WHERE aktif='Y'");
  $f=$pilih_template->fetch_array();
  include "$f[folder]/template.php"; 
  closedb();
?>
