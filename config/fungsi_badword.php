<?php
function sensor($teks){
    $w = querydb("SELECT * FROM katajelek");
    while ($r = $w->fetch_array()){
        $teks = str_replace($r['kata'], $r['ganti'], $teks);       
    }
    return $teks;
}  
?>
