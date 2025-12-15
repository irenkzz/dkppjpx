<?php
date_default_timezone_set('Asia/Jakarta'); // PHP 6 mengharuskan penyebutan timezone.
$seminggu = array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu");
$hari = date("w");
$hari_ini = $seminggu[$hari];

$tgl_sekarang = date("Ymd");
$tgl_skrg     = date("d");
$bln_sekarang = date("m");
$thn_sekarang = date("Y");
$jam_sekarang = date("H:i:s");

$nama_bln=array(1=> "Januari", "Februari", "Maret", "April", "Mei", 
                    "Juni", "Juli", "Agustus", "September", 
                    "Oktober", "November", "Desember");

function render_share_buttons(string $url, string $title = ''): string {
    // escape untuk output HTML
    $safeUrl   = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    // share URL encoded
    $urlEnc   = urlencode($url);
    $titleEnc = urlencode($title);

    return "
    <ul class='rrssb-buttons margin-bottom-15'>

        <li class='rrssb-facebook'>
            <a href='https://www.facebook.com/sharer/sharer.php?u={$urlEnc}' class='popup'>
                <span class='rrssb-icon'><i class='fa fa-facebook'></i></span>
                <span class='rrssb-text'>Facebook</span>
            </a>
        </li>

        <li class='rrssb-linkedin'>
            <a href='https://www.linkedin.com/shareArticle?mini=true&url={$urlEnc}&title={$titleEnc}' class='popup'>
                <span class='rrssb-icon'><i class='fa fa-linkedin'></i></span>
                <span class='rrssb-text'>LinkedIn</span>
            </a>
        </li>

        <li class='rrssb-twitter'>
            <a href='https://twitter.com/home?status={$urlEnc}' class='popup'>
                <span class='rrssb-icon'><i class='fa fa-twitter'></i></span>
                <span class='rrssb-text'>Twitter</span>
            </a>
        </li>

        

        <li class='rrssb-whatsapp'>
            <a href='whatsapp://send?text={$urlEnc}' data-action='share/whatsapp/share'>
                <span class='rrssb-icon'><i class='fa fa-whatsapp'></i></span>
                <span class='rrssb-text'>Whatsapp</span>
            </a>
        </li>

    </ul>
    ";
}
?>