<?php
session_start();
include_once __DIR__ . "/../../../config/koneksi.php";
include_once __DIR__ . "/../../../config/library.php"; 
include "../../../config/fungsi_seo.php";// for seo_title()
opendb();

$module  = $_GET['module'] ?? '';
$act     = $_GET['act'] ?? '';

if ($module == 'tag' && $act == 'input') {
    $nama_tag = trim($_POST['nama_tag'] ?? '');
    $tag_seo  = seo_title($nama_tag);
    $pilihan  = trim($_POST['pilihan'] ?? 'N');

    if ($nama_tag !== '') {
        exec_prepared(
            "INSERT INTO tag (nama_tag, tag_seo, pilihan) VALUES (?, ?, ?)",
            "sss",
            [$nama_tag, $tag_seo, $pilihan]
        );
    }
    header('location:../../media.php?module=tag');
    exit;
}

if ($module == 'tag' && $act == 'update') {
    $id        = (int) ($_POST['id'] ?? 0);
    $nama_tag  = trim($_POST['nama_tag'] ?? '');
    $tag_seo   = seo_title($nama_tag);
    $pilihan   = trim($_POST['pilihan'] ?? 'N');

    if ($id > 0 && $nama_tag !== '') {
        exec_prepared(
            "UPDATE tag SET nama_tag = ?, tag_seo = ?, pilihan = ? WHERE id_tag = ?",
            "sssi",
            [$nama_tag, $tag_seo, $pilihan, $id]
        );
    }
    header('location:../../media.php?module=tag');
    exit;
}

if ($module == 'tag' && $act == 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0) {
        exec_prepared("DELETE FROM tag WHERE id_tag = ?", "i", [$id]);
    }
    header('location:../../media.php?module=tag');
    exit;
}

closedb();
?>
