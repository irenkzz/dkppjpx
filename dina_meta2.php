<?php
if (isset($_GET['id'])){
  $sql = querydb("select tag from berita where id_berita='$_GET[id]'");
  $j   = $sql->fetch_array();
	echo "$j[tag]";
}
else{
      $sql2 = querydb("select meta_keyword from identitas LIMIT 1");
      $j2   = $sql2->fetch_array();
		  echo "$j2[meta_keyword]";
}
?>
