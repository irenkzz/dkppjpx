<?php
	// Agar memastikan bahwa halaman ini diakses dengan parameter POST: bulan_tanggal
	isset($_POST['bulan_tanggal']) or die('Error Parameter');
	
	include 'config/koneksi.php'; // koneksi ke database
	opendb()
	$filter = $_POST['bulan_tanggal']; // value yang ditampung berformat: 'January 2009', 'February 2009', dsb
	
	// Tampilkan semua isi berita yang pada tanggal sesuai format 'Bulan Tahun' ( '%M %Y' )
	$query = querydb("SELECT * FROM berita WHERE DATE_FORMAT(tanggal,'%M %Y') = '$filter'");
	if($query && $query->num_rows > 0){
	  while($row = $query->fetch_object()){
		echo '<p><li><a href="berita-'.$row->id_berita.'-'.$row->judul_seo.'.html">'.$row->judul.'</a></li></p>';
	  }
	}
	closedb();
?>
