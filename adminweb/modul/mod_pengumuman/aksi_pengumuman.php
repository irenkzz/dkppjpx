<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
    require_once __DIR__ . "/../../includes/bootstrap.php"; // CSRF + koneksi
    require_once __DIR__ . "/../../../config/fungsi_seo.php";
    require_once __DIR__ . "/../../../config/library.php";

    opendb();

    $module = $_GET['module'] ?? '';
    $act    = $_GET['act'] ?? '';

    // ---------------------------
    // HAPUS PENGUMUMAN (POST + CSRF)
    // ---------------------------
    if ($module == 'pengumuman' && $act == 'hapus') {
        require_post_csrf();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            exec_prepared("DELETE FROM pengumuman WHERE id_pengumuman = ?", "i", [$id]);
        }

        header("location:../../media.php?module=".$module);
        exit;
    }

    // ---------------------------
    // INPUT PENGUMUMAN (POST + CSRF)
    // ---------------------------
    elseif ($module == 'pengumuman' && $act == 'input') {
        require_post_csrf();

        $judul          = $_POST['judul']          ?? '';
        $isi_pengumuman = $_POST['isi_pengumuman'] ?? '';
        $judul_seo      = seo_title($judul);
        $tgl_sekarang   = date("Y-m-d");
        $username       = $_SESSION['namauser'] ?? '';

        if ($judul === '' || $isi_pengumuman === '') {
            echo "<script>alert('Judul dan isi pengumuman wajib diisi.');history.back();</script>";
            exit;
        }

        exec_prepared(
            "INSERT INTO pengumuman (judul, judul_seo, isi_pengumuman, tgl_posting, username)
             VALUES (?, ?, ?, ?, ?)",
            "sssss",
            [$judul, $judul_seo, $isi_pengumuman, $tgl_sekarang, $username]
        );

        header("location:../../media.php?module=".$module);
        exit;
    }

    // ---------------------------
    // UPDATE PENGUMUMAN (POST + CSRF)
    // ---------------------------
    elseif ($module == 'pengumuman' && $act == 'update') {
        require_post_csrf();

        $id             = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $judul          = $_POST['judul']          ?? '';
        $isi_pengumuman = $_POST['isi_pengumuman'] ?? '';
        $judul_seo      = seo_title($judul);
        $username       = $_SESSION['namauser'] ?? '';

        if ($id <= 0) {
            header("location:../../media.php?module=".$module);
            exit;
        }

        exec_prepared(
            "UPDATE pengumuman
                SET judul          = ?,
                    judul_seo      = ?,
                    isi_pengumuman = ?,
                    username       = ?
              WHERE id_pengumuman = ?",
            "ssssi",
            [$judul, $judul_seo, $isi_pengumuman, $username, $id]
        );

        header("location:../../media.php?module=".$module);
        exit;
    }

    closedb();
}
?>
