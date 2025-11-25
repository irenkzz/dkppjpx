<?php
session_start();
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
    require_once __DIR__ . "/../../includes/bootstrap.php";      // CSRF + koneksi
    require_once __DIR__ . "/../../../config/library.php";
    require_once __DIR__ . "/../../../config/fungsi_seo.php";
    //require_once __DIR__ . "/../../../config/fungsi_thumbnail.php";

    opendb();
    global $dbconnection;

    function ubah_tgl($tglnyo){
        $fm    = explode('/',$tglnyo);
        $tahun = $fm[2];
        $bulan = $fm[1];
        $tgll  = $fm[0];
        $sekarang = $tahun."-".$bulan."-".$tgll;
        return $sekarang;
    }

    $module = $_GET['module'] ?? '';
    $act    = $_GET['act'] ?? '';

    // ---------------------------
    // HAPUS AGENDA (POST + CSRF)
    // ---------------------------
    if ($module=='agenda' && $act=='hapus') {
        require_post_csrf();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            header("location:../../media.php?module=".$module);
            exit;
        }

        /*
        // Ambil nama file gambar (jika ada)
        $stmt = $dbconnection->prepare("SELECT gambar FROM agenda WHERE id_agenda = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($gambar);
        $stmt->fetch();
        $stmt->close();*/

        // Hapus row di database
        $stmt = $dbconnection->prepare("DELETE FROM agenda WHERE id_agenda = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        /*
        // Hapus file fisik (jika kolom gambar ada dan terisi)
        if (!empty($gambar)) {
            $base = basename($gambar);
            @unlink(__DIR__ . "/../../../foto_agenda/$base");
            @unlink(__DIR__ . "/../../../foto_agenda/small_$base");
        }
        */

        header("location:../../media.php?module=".$module);
        exit;
    }

    // ---------------------------
    // INPUT AGENDA (POST + CSRF)
    // ---------------------------
    elseif ($module=='agenda' && $act=='input') {
        require_post_csrf();

        $lokasi_file = $_FILES['fupload']['tmp_name'] ?? '';
        $tipe_file   = $_FILES['fupload']['type'] ?? '';
        $nama_file   = $_FILES['fupload']['name'] ?? '';
        $acak        = rand(1,99);
        $nama_gambar = $acak.$nama_file;

        $tema        = $_POST['tema']        ?? '';
        $tema_seo    = seo_title($tema);
        $isi_agenda  = $_POST['isi_agenda']  ?? '';
        $tempat      = $_POST['tempat']      ?? '';
        $tgl_mulai   = ubah_tgl($_POST['tgl_mulai']   ?? '');
        $tgl_selesai = ubah_tgl($_POST['tgl_selesai'] ?? '');
        $jam         = $_POST['jam']         ?? '';
        $pengirim    = $_POST['pengirim']    ?? '';
        $tgl_sekarang = date("Y-m-d");
        $username     = $_SESSION['namauser'] ?? '';

        // Validasi sederhana
        if ($tema === '' || $isi_agenda === '' || $tempat === '') {
            echo "<script>alert('Tema, isi agenda, dan tempat wajib diisi');history.back();</script>";
            exit;
        }

        // Tidak ada gambar di-upload
        if (empty($lokasi_file)) {
            // Kolom mengikuti skema lama: tanpa kolom gambar
            $stmt = $dbconnection->prepare("
                INSERT INTO agenda (
                    tema,
                    tema_seo,
                    isi_agenda,
                    tempat,
                    tgl_mulai,
                    tgl_selesai,
                    tgl_posting,
                    jam,
                    pengirim,
                    username
                ) VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "ssssssssss",
                $tema,
                $tema_seo,
                $isi_agenda,
                $tempat,
                $tgl_mulai,
                $tgl_selesai,
                $tgl_sekarang,
                $jam,
                $pengirim,
                $username
            );
            $stmt->execute();
            $stmt->close();
        }
        // Ada gambar upload
        else {
            if ($tipe_file != "image/jpeg" && $tipe_file != "image/pjpeg") {
                echo "<script>window.alert('Upload Gagal! Pastikan file yang di upload bertipe *.JPG');
                      window.location=('../../media.php?module=agenda')</script>";
                exit;
            }

            // Folder & resize mengikuti kode lama
            $folder = "../../../foto_banner/"; // sesuai kode asal
            $ukuran = 600;
            UploadFoto($nama_gambar, $folder, $ukuran);

            // Insert dengan kolom gambar
            $stmt = $dbconnection->prepare("
                INSERT INTO agenda (
                    tema,
                    tema_seo,
                    isi_agenda,
                    tempat,
                    tgl_mulai,
                    tgl_selesai,
                    tgl_posting,
                    jam,
                    pengirim,
                    username,
                    gambar
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "sssssssssss",
                $tema,
                $tema_seo,
                $isi_agenda,
                $tempat,
                $tgl_mulai,
                $tgl_selesai,
                $tgl_sekarang,
                $jam,
                $pengirim,
                $username,
                $nama_gambar
            );
            $stmt->execute();
            $stmt->close();
        }

        header("location:../../media.php?module=".$module);
        exit;
    }

    // ---------------------------
    // UPDATE AGENDA (POST + CSRF)
    // ---------------------------
    elseif ($module=='agenda' && $act=='update') {
        require_post_csrf();

        $lokasi_file = $_FILES['fupload']['tmp_name'] ?? '';
        $tipe_file   = $_FILES['fupload']['type'] ?? '';
        $nama_file   = $_FILES['fupload']['name'] ?? '';
        $acak        = rand(1,99);
        $nama_gambar = $acak.$nama_file;

        $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $tema        = $_POST['tema']        ?? '';
        $tema_seo    = seo_title($tema);
        $isi_agenda  = $_POST['isi_agenda']  ?? '';
        $tempat      = $_POST['tempat']      ?? '';
        $tgl_mulai   = ubah_tgl($_POST['tgl_mulai']   ?? '');
        $tgl_selesai = ubah_tgl($_POST['tgl_selesai'] ?? '');
        $jam         = $_POST['jam']         ?? '';
        $pengirim    = $_POST['pengirim']    ?? '';

        if ($id <= 0) {
            header("location:../../media.php?module=".$module);
            exit;
        }

        // Gambar tidak diganti
        if (empty($lokasi_file)) {
            $stmt = $dbconnection->prepare("
                UPDATE agenda
                   SET tema        = ?,
                       tema_seo    = ?,
                       isi_agenda  = ?,
                       tempat      = ?,
                       tgl_mulai   = ?,
                       tgl_selesai = ?,
                       jam         = ?,
                       pengirim    = ?
                 WHERE id_agenda   = ?
            ");
            $stmt->bind_param(
                "ssssssssi",
                $tema,
                $tema_seo,
                $isi_agenda,
                $tempat,
                $tgl_mulai,
                $tgl_selesai,
                $jam,
                $pengirim,
                $id
            );
            $stmt->execute();
            $stmt->close();

            header("location:../../media.php?module=".$module);
            exit;
        }
        // Gambar diganti
        else {
            if ($tipe_file != "image/jpeg" && $tipe_file != "image/pjpeg") {
                echo "<script>window.alert('Upload Gagal! Pastikan file yang di upload bertipe *.JPG');
                      window.location=('../../media.php?module=agenda')</script>";
                exit;
            }

            $folder = "../../../foto_banner/";
            $ukuran = 600;
            UploadFoto($nama_gambar, $folder, $ukuran);

            $stmt = $dbconnection->prepare("
                UPDATE agenda
                   SET tema        = ?,
                       tema_seo    = ?,
                       isi_agenda  = ?,
                       tempat      = ?,
                       tgl_mulai   = ?,
                       tgl_selesai = ?,
                       jam         = ?,
                       pengirim    = ?,
                       gambar      = ?
                 WHERE id_agenda   = ?
            ");
            $stmt->bind_param(
                "sssssssssi",
                $tema,
                $tema_seo,
                $isi_agenda,
                $tempat,
                $tgl_mulai,
                $tgl_selesai,
                $jam,
                $pengirim,
                $nama_gambar,
                $id
            );
            $stmt->execute();
            $stmt->close();

            header("location:../../media.php?module=".$module);
            exit;
        }
    }

    closedb();
}
?>