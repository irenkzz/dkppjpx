<?php
// Dapatkan link menu utama (beranda)
$beranda = querydb("SELECT link FROM mainmenu WHERE id_main='2'");
$b       = $beranda->fetch_array();

if($_GET['module']=='home'){
	echo "<span class=judul_head><a href='$b[link]'>Beranda</a></span>";
}
elseif($_GET['module']=='detailberita'){
	$detail	=querydb("SELECT * FROM berita,users,kategori    
            	          WHERE users.username=berita.username 
                	      AND kategori.id_kategori=berita.id_kategori 
                     	  AND id_berita='$_GET[id]'");
	$d		= $detail->fetch_array();
	echo "<span class=judul_head><a href='$b[link]'>Beranda</a> &#187; <a href=kategori-$d[id_kategori]-$d[kategori_seo].html>$d[nama_kategori]</a> &#187; $d[judul]</span>";
}
elseif($_GET['module']=='detailkategori'){
	$detail	=querydb("SELECT nama_kategori from kategori where id_kategori='$_GET[id]'");
	$d		= $detail->fetch_array();
	echo "<span class=judul_head><a href='$b[link]'>Beranda</a> &#187; $d[nama_kategori]</span>";
}
?>
