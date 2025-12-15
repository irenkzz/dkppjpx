<?php
require_once __DIR__ . "/../../includes/bootstrap.php";      // CSRF + koneksi
require_once __DIR__ . "/../../../config/library.php";
require_once __DIR__ . "/../../../config/fungsi_seo.php";
require_once __DIR__ . "/../../includes/upload_helpers.php";
require_once __DIR__ . "/../../inc/audit_log.php";

// Apabila user belum login
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])) {
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
    exit;
}

opendb();
global $dbconnection;
$fileDir  = __DIR__ . "/../../../foto_banner";
$agendaHasImageCol = db_column_exists('agenda', 'gambar');

function ubah_tgl($tglnyo){
    $fm    = explode('/',$tglnyo);
    $tahun = $fm[2] ?? '';
    $bulan = $fm[1] ?? '';
    $tgll  = $fm[0] ?? '';
    $sekarang = $tahun."-".$bulan."-".$tgll;
    return $sekarang;
}

function alert_agenda(string $message, ?string $redirect = null): void {
    $safe = addslashes($message);
    if ($redirect) {
        echo "<script>alert('{$safe}');window.location='{$redirect}';</script>";
    } else {
        echo "<script>alert('{$safe}');history.back();</script>";
    }
    exit;
}

function trim_note_agenda(?string $note): ?string {
    if ($note === null) return null;
    $note = trim($note);
    if ($note === '') return null;
    if (strlen($note) > 255) {
        $note = substr($note, 0, 255);
    }
    return $note;
}

function fetch_live_agenda(int $id, string $username, bool $isAdmin): ?array {
    global $dbconnection;
    if ($id <= 0) {
        return null;
    }

    if ($isAdmin) {
        $stmt = $dbconnection->prepare("SELECT * FROM agenda WHERE id_agenda = ?");
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $dbconnection->prepare("SELECT * FROM agenda WHERE id_agenda = ? AND username = ?");
        if (!$stmt) return null;
        $stmt->bind_param("is", $id, $username);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function publish_agenda_revision(array $rev, string $fileDir, bool $hasImageCol): int {
    global $dbconnection;

    $isNew     = empty($rev['agenda_id']);
    $tema      = $rev['tema'] ?? '';
    $temaSeo   = $rev['tema_seo'] ?? seo_title($tema);
    $isi       = $rev['isi_agenda'] ?? '';
    $tempat    = $rev['tempat'] ?? '';
    $tglMulai  = $rev['tgl_mulai'] ?? '';
    $tglSelesai= $rev['tgl_selesai'] ?? '';
    $tglPost   = $rev['tgl_posting'] ?? date('Y-m-d');
    $jam       = $rev['jam'] ?? '';
    $pengirim  = $rev['pengirim'] ?? '';
    $userRow   = $rev['username'] ?? '';
    $gambar    = $rev['gambar'] ?? '';

    if ($isNew) {
        if ($hasImageCol) {
            $stmt = $dbconnection->prepare("
                INSERT INTO agenda (
                    tema, tema_seo, isi_agenda, tempat, tgl_mulai, tgl_selesai, tgl_posting, jam, pengirim, username, gambar
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            if (!$stmt) {
                throw new RuntimeException('Gagal menyiapkan query insert agenda.');
            }
            $stmt->bind_param(
                "sssssssssss",
                $tema,
                $temaSeo,
                $isi,
                $tempat,
                $tglMulai,
                $tglSelesai,
                $tglPost,
                $jam,
                $pengirim,
                $userRow,
                $gambar
            );
        } else {
            $stmt = $dbconnection->prepare("
                INSERT INTO agenda (
                    tema, tema_seo, isi_agenda, tempat, tgl_mulai, tgl_selesai, tgl_posting, jam, pengirim, username
                ) VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            if (!$stmt) {
                throw new RuntimeException('Gagal menyiapkan query insert agenda (tanpa gambar).');
            }
            $stmt->bind_param(
                "ssssssssss",
                $tema,
                $temaSeo,
                $isi,
                $tempat,
                $tglMulai,
                $tglSelesai,
                $tglPost,
                $jam,
                $pengirim,
                $userRow
            );
        }
        $stmt->execute();
        $newId = $dbconnection->insert_id;
        $stmt->close();
        return (int)$newId;
    }

    $contentId = (int)$rev['agenda_id'];
    $oldImage  = '';
    if ($hasImageCol) {
        $stmtOld   = $dbconnection->prepare("SELECT gambar FROM agenda WHERE id_agenda = ?");
        if ($stmtOld) {
            $stmtOld->bind_param("i", $contentId);
            $stmtOld->execute();
            $stmtOld->bind_result($oldImage);
            $stmtOld->fetch();
            $stmtOld->close();
        }
    }

    if ($hasImageCol) {
        $stmt = $dbconnection->prepare("
            UPDATE agenda
               SET tema = ?,
                   tema_seo = ?,
                   isi_agenda = ?,
                   tempat = ?,
                   tgl_mulai = ?,
                   tgl_selesai = ?,
                   tgl_posting = ?,
                   jam = ?,
                   pengirim = ?,
                   username = ?,
                   gambar = ?
             WHERE id_agenda = ?
        ");
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query update agenda.');
        }
        $stmt->bind_param(
            "sssssssssssi",
            $tema,
            $temaSeo,
            $isi,
            $tempat,
            $tglMulai,
            $tglSelesai,
            $tglPost,
            $jam,
            $pengirim,
            $userRow,
            $gambar,
            $contentId
        );
    } else {
        $stmt = $dbconnection->prepare("
            UPDATE agenda
               SET tema = ?,
                   tema_seo = ?,
                   isi_agenda = ?,
                   tempat = ?,
                   tgl_mulai = ?,
                   tgl_selesai = ?,
                   tgl_posting = ?,
                   jam = ?,
                   pengirim = ?,
                   username = ?
             WHERE id_agenda = ?
        ");
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query update agenda (tanpa gambar).');
        }
        $stmt->bind_param(
            "ssssssssssi",
            $tema,
            $temaSeo,
            $isi,
            $tempat,
            $tglMulai,
            $tglSelesai,
            $tglPost,
            $jam,
            $pengirim,
            $userRow,
            $contentId
        );
    }
    $stmt->execute();
    $stmt->close();

    if ($hasImageCol && !empty($oldImage) && $oldImage !== $gambar) {
        $base = basename($oldImage);
        @unlink($fileDir . "/$base");
        @unlink($fileDir . "/small_$base");
    }

    return $contentId;
}

function log_agenda_event(string $action, int $revId, ?int $targetId, string $createdBy, string $note = ''): void {
    $meta = array(
        'rev_id'       => $revId,
        'target_table' => 'agenda',
        'target_id'    => $targetId,
        'created_by'   => $createdBy,
    );
    if ($note !== '') {
        $meta['note'] = substr($note, 0, 120);
    }
    audit_event('approval', $action, 'agenda', $targetId, 'Agenda revision '.$action, null, null, $meta);
}

$module   = $_GET['module'] ?? '';
$act      = $_GET['act'] ?? '';
$isAdmin  = (($_SESSION['leveluser'] ?? '') === 'admin');
$username = $_SESSION['namauser'] ?? '';

// ---------------------------
// HAPUS AGENDA (POST + CSRF)
// ---------------------------
if ($module=='agenda' && $act=='hapus') {
    require_post_csrf();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        header("Location: /admin?module=".$module);
        exit;
    }

    // Ambil nama file gambar (jika ada dan kolom tersedia) untuk dibersihkan
    if ($agendaHasImageCol) {
        $stmt = $dbconnection->prepare("SELECT gambar FROM agenda WHERE id_agenda = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($gambar);
            $stmt->fetch();
            $stmt->close();

            if (!empty($gambar)) {
                $base = basename($gambar);
                @unlink($fileDir . "/$base");
                @unlink($fileDir . "/small_$base");
            }
        }
    }

    $stmt = $dbconnection->prepare("DELETE FROM agenda WHERE id_agenda = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: /admin?module=".$module);
    exit;
}

// ---------------------------
// INPUT AGENDA (POST + CSRF) -> buat revisi
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

    // Validasi sederhana
    if ($tema === '' || $isi_agenda === '' || $tempat === '') {
        alert_agenda('Tema, isi agenda, dan tempat wajib diisi');
    }

    $gambar = '';
    if ($agendaHasImageCol && !empty($lokasi_file)) {
        try {
            $res = upload_image_secure($_FILES['fupload'], [
                'dest_dir'     => $fileDir,
                'thumb_max_w'  => 600,
                'thumb_max_h'  => 600,
                'jpeg_quality' => 85,
                'prefix'       => 'agenda_',
            ]);
            $gambar = $res['filename'];
        } catch (Throwable $e) {
            alert_agenda('Upload Gagal: '.$e->getMessage());
        }
    }

    $status     = $isAdmin ? 'APPROVED' : 'PENDING';
    $createdAt  = date('Y-m-d H:i:s');
    $approvedBy = $isAdmin ? $username : null;
    $approvedAt = $isAdmin ? $createdAt : null;
    $noteVal    = $isAdmin ? 'Auto-approved by admin' : null;

    $stmt = $dbconnection->prepare("
        INSERT INTO agenda_revisions
            (agenda_id, status, created_by, created_at, approved_by, approved_at, note, tema, tema_seo, isi_agenda, tempat, tgl_mulai, tgl_selesai, tgl_posting, jam, pengirim, username, gambar)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        alert_agenda('Gagal menyimpan revisi agenda.');
    }

    $agendaId = null;
    $stmt->bind_param(
        "isssssssssssssssss",
        $agendaId,
        $status,
        $username,
        $createdAt,
        $approvedBy,
        $approvedAt,
        $noteVal,
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
        $gambar
    );
    $stmt->execute();
    $revId = $dbconnection->insert_id;
    $stmt->close();

    $revRow = array(
        'agenda_id'   => null,
        'tema'        => $tema,
        'tema_seo'    => $tema_seo,
        'isi_agenda'  => $isi_agenda,
        'tempat'      => $tempat,
        'tgl_mulai'   => $tgl_mulai,
        'tgl_selesai' => $tgl_selesai,
        'tgl_posting' => $tgl_sekarang,
        'jam'         => $jam,
        'pengirim'    => $pengirim,
        'username'    => $username,
        'gambar'      => $gambar,
    );

    log_agenda_event('CREATE_REVISION', (int)$revId, null, $username, (string)$noteVal);

    if ($isAdmin) {
        try {
            $liveId = publish_agenda_revision($revRow, $fileDir, $agendaHasImageCol);
            exec_prepared("UPDATE agenda_revisions SET agenda_id = ?, status = 'APPROVED', approved_by = ?, approved_at = ? WHERE rev_id = ?", "issi", [$liveId, $approvedBy, $approvedAt, $revId]);
            log_agenda_event('APPROVE', (int)$revId, (int)$liveId, $username, (string)$noteVal);
            header("Location: /admin?module=".$module);
            exit;
        } catch (Throwable $e) {
            alert_agenda('Gagal mempublikasikan agenda: '.$e->getMessage(), '/admin?module='.$module);
        }
    }

    echo "<script>alert('Revisi agenda dikirim untuk persetujuan admin.');window.location='/admin?module=agenda';</script>";
    exit;
}

// ---------------------------
// UPDATE AGENDA (POST + CSRF) -> buat revisi
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
        alert_agenda('ID agenda tidak valid.', '/admin?module='.$module);
    }

    $existing = fetch_live_agenda($id, $username, $isAdmin);
    if (!$existing) {
        alert_agenda('Data agenda tidak ditemukan atau akses ditolak.', '/admin?module='.$module);
    }

    $gambarBaru = $existing['gambar'] ?? '';
    if ($agendaHasImageCol && !empty($lokasi_file)) {
        try {
            $res = upload_image_secure($_FILES['fupload'], [
                'dest_dir'     => $fileDir,
                'thumb_max_w'  => 600,
                'thumb_max_h'  => 600,
                'jpeg_quality' => 85,
                'prefix'       => 'agenda_',
            ]);
            $gambarBaru = $res['filename'];
        } catch (Throwable $e) {
            alert_agenda('Upload Gagal: '.$e->getMessage());
        }
    }

    $status     = $isAdmin ? 'APPROVED' : 'PENDING';
    $createdAt  = date('Y-m-d H:i:s');
    $approvedBy = $isAdmin ? $username : null;
    $approvedAt = $isAdmin ? $createdAt : null;
    $noteVal    = $isAdmin ? 'Auto-approved by admin (edit)' : null;

    $stmt = $dbconnection->prepare("
        INSERT INTO agenda_revisions
            (agenda_id, status, created_by, created_at, approved_by, approved_at, note, tema, tema_seo, isi_agenda, tempat, tgl_mulai, tgl_selesai, tgl_posting, jam, pengirim, username, gambar)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        alert_agenda('Gagal menyimpan revisi agenda.');
    }

    $stmt->bind_param(
        "isssssssssssssssss",
        $id,
        $status,
        $username,
        $createdAt,
        $approvedBy,
        $approvedAt,
        $noteVal,
        $tema,
        $tema_seo,
        $isi_agenda,
        $tempat,
        $tgl_mulai,
        $tgl_selesai,
        $existing['tgl_posting'],
        $jam,
        $pengirim,
        $existing['username'],
        $gambarBaru
    );
    $stmt->execute();
    $revId = $dbconnection->insert_id;
    $stmt->close();

    $revRow = array(
        'agenda_id'   => $id,
        'tema'        => $tema,
        'tema_seo'    => $tema_seo,
        'isi_agenda'  => $isi_agenda,
        'tempat'      => $tempat,
        'tgl_mulai'   => $tgl_mulai,
        'tgl_selesai' => $tgl_selesai,
        'tgl_posting' => $existing['tgl_posting'],
        'jam'         => $jam,
        'pengirim'    => $pengirim,
        'username'    => $existing['username'],
        'gambar'      => $gambarBaru,
    );

    log_agenda_event('CREATE_REVISION', (int)$revId, $id, $username, (string)$noteVal);

    if ($isAdmin) {
        try {
            $liveId = publish_agenda_revision($revRow, $fileDir, $agendaHasImageCol);
            exec_prepared("UPDATE agenda_revisions SET status = 'APPROVED', approved_by = ?, approved_at = ? WHERE rev_id = ?", "ssi", [$approvedBy, $approvedAt, $revId]);
            log_agenda_event('APPROVE', (int)$revId, (int)$liveId, $username, (string)$noteVal);
            header("Location: /admin?module=".$module);
            exit;
        } catch (Throwable $e) {
            alert_agenda('Gagal menerapkan revisi: '.$e->getMessage(), '/admin?module='.$module);
        }
    }

    echo "<script>alert('Revisi agenda dikirim untuk persetujuan admin.');window.location='/admin?module=agenda';</script>";
    exit;
}

// ---------------------------
// APPROVE revisi (POST + CSRF)
// ---------------------------
elseif ($module=='agenda' && $act=='approve') {
    if (!$isAdmin) {
        http_response_code(403);
        exit('Forbidden');
    }
    require_post_csrf();

    $revId = isset($_POST['rev_id']) ? (int)$_POST['rev_id'] : 0;
    $note  = trim_note_agenda($_POST['note'] ?? '');
    if ($revId <= 0) {
        alert_agenda('ID revisi tidak valid.', '/admin?module=approvalqueue');
    }

    $stmt = $dbconnection->prepare("SELECT * FROM agenda_revisions WHERE rev_id = ?");
    if (!$stmt) {
        alert_agenda('Gagal mengambil revisi.', '/admin?module=approvalqueue');
    }
    $stmt->bind_param("i", $revId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rev = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$rev || strtoupper($rev['status']) !== 'PENDING') {
        alert_agenda('Revisi tidak ditemukan atau sudah diproses.', '/admin?module=approvalqueue');
    }

    $publishRow = array(
        'agenda_id'   => $rev['agenda_id'],
        'tema'        => $rev['tema'],
        'tema_seo'    => $rev['tema_seo'],
        'isi_agenda'  => $rev['isi_agenda'],
        'tempat'      => $rev['tempat'],
        'tgl_mulai'   => $rev['tgl_mulai'],
        'tgl_selesai' => $rev['tgl_selesai'],
        'tgl_posting' => $rev['tgl_posting'],
        'jam'         => $rev['jam'],
        'pengirim'    => $rev['pengirim'],
        'username'    => $rev['username'],
        'gambar'      => $rev['gambar'],
    );

    try {
        $liveId     = publish_agenda_revision($publishRow, $fileDir, $agendaHasImageCol);
        $approvedAt = date('Y-m-d H:i:s');
        exec_prepared(
            "UPDATE agenda_revisions SET status = 'APPROVED', approved_by = ?, approved_at = ?, note = ?, agenda_id = ? WHERE rev_id = ?",
            "sssii",
            [$username, $approvedAt, $note, $liveId, $revId]
        );
        log_agenda_event('APPROVE', (int)$revId, (int)$liveId, $rev['created_by'] ?? $username, (string)$note);
        header("Location: /admin?module=approvalqueue");
        exit;
    } catch (Throwable $e) {
        alert_agenda('Gagal menerapkan revisi: '.$e->getMessage(), '/admin?module=approvalqueue');
    }
}

// ---------------------------
// REJECT revisi (POST + CSRF)
// ---------------------------
elseif ($module=='agenda' && $act=='reject') {
    if (!$isAdmin) {
        http_response_code(403);
        exit('Forbidden');
    }
    require_post_csrf();

    $revId = isset($_POST['rev_id']) ? (int)$_POST['rev_id'] : 0;
    $note  = trim_note_agenda($_POST['note'] ?? '');
    if ($revId <= 0) {
        alert_agenda('ID revisi tidak valid.', '/admin?module=approvalqueue');
    }

    $stmt = $dbconnection->prepare("SELECT * FROM agenda_revisions WHERE rev_id = ?");
    if (!$stmt) {
        alert_agenda('Gagal mengambil revisi.', '/admin?module=approvalqueue');
    }
    $stmt->bind_param("i", $revId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rev = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$rev || strtoupper($rev['status']) !== 'PENDING') {
        alert_agenda('Revisi tidak ditemukan atau sudah diproses.', '/admin?module=approvalqueue');
    }

    $approvedAt = date('Y-m-d H:i:s');
    exec_prepared(
        "UPDATE agenda_revisions SET status = 'REJECTED', approved_by = ?, approved_at = ?, note = ? WHERE rev_id = ?",
        "sssi",
        [$username, $approvedAt, $note, $revId]
    );
    log_agenda_event('REJECT', (int)$revId, $rev['agenda_id'] ?? null, $rev['created_by'] ?? $username, (string)$note);

    header("Location: /admin?module=approvalqueue");
    exit;
}

closedb();
?>
