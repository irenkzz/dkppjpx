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
    require_once __DIR__ . "/../../includes/upload_helpers.php";

    opendb();
    global $dbconnection;
    $fileDir = __DIR__ . "/../../../foto_banner";

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

        // Ambil nama file gambar (jika ada) untuk dibersihkan
        $stmt = $dbconnection->prepare("SELECT gambar FROM agenda WHERE id_agenda = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($gambar);
        $stmt->fetch();
        $stmt->close();

        // Hapus row di database
        $stmt = $dbconnection->prepare("DELETE FROM agenda WHERE id_agenda = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Hapus file fisik (jika kolom gambar ada dan terisi)
        if (!empty($gambar)) {
            $base = basename($gambar);
            @unlink($fileDir . "/$base");
            @unlink($fileDir . "/small_$base");
        }

        header("location:../../media.php?module=".$module);
        exit;
    }

    // ---------------------------
    // INPUT AGENDA (POST + CSRF)
    // ---------------------------
    elseif ($module=='agenda' && $act=='input') {
        require_post_csrf();

        $lokasi_file = $_FILES['fupload']['tmp_name'] ?? '';

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
            try {
                $res = upload_image_secure($_FILES['fupload'], [
                    'dest_dir'     => $fileDir,
                    'thumb_max_w'  => 600,
                    'thumb_max_h'  => 600,
                    'jpeg_quality' => 85,
                    'prefix'       => 'agenda_',
                ]);
                $nama_gambar = $res['filename'];
            } catch (Throwable $e) {
                echo "<script>window.alert('Upload Gagal: " . e($e->getMessage()) . "');history.back();</script>";
                closedb();
                exit;
            }

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
            try {
                $res = upload_image_secure($_FILES['fupload'], [
                    'dest_dir'     => $fileDir,
                    'thumb_max_w'  => 600,
                    'thumb_max_h'  => 600,
                    'jpeg_quality' => 85,
                    'prefix'       => 'agenda_',
                ]);
                $nama_gambar = $res['filename'];
            } catch (Throwable $e) {
                echo "<script>window.alert('Upload Gagal: " . e($e->getMessage()) . "');history.back();</script>";
                closedb();
                exit;
            }

            // ambil file lama untuk dibersihkan
            $stmt = $dbconnection->prepare("SELECT gambar FROM agenda WHERE id_agenda = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($old_gambar);
            $stmt->fetch();
            $stmt->close();

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

            if (!empty($old_gambar)) {
                $base = basename($old_gambar);
                @unlink($fileDir . "/$base");
                @unlink($fileDir . "/small_$base");
            }

            header("location:../../media.php?module=".$module);
            exit;
        }
    }

    closedb();
}
?>
