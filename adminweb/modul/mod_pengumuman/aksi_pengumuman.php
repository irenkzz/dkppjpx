<?php
require_once __DIR__ . "/../../includes/bootstrap.php"; // CSRF + koneksi
require_once __DIR__ . "/../../../config/fungsi_seo.php";
require_once __DIR__ . "/../../../config/library.php";
require_once __DIR__ . "/../../inc/audit_log.php";

// Apabila user belum login
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])) {
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
    exit;
}

opendb();

$module   = $_GET['module'] ?? '';
$act      = $_GET['act'] ?? '';
$isAdmin  = (($_SESSION['leveluser'] ?? '') === 'admin');
$username = $_SESSION['namauser'] ?? '';

function alert_pengumuman(string $message, ?string $redirect = null): void {
    $safe = addslashes($message);
    if ($redirect) {
        echo "<script>alert('{$safe}');window.location='{$redirect}';</script>";
    } else {
        echo "<script>alert('{$safe}');history.back();</script>";
    }
    exit;
}

function trim_note_pengumuman(?string $note): ?string {
    if ($note === null) return null;
    $note = trim($note);
    if ($note === '') return null;
    if (strlen($note) > 255) {
        $note = substr($note, 0, 255);
    }
    return $note;
}

function fetch_live_pengumuman(int $id, string $username, bool $isAdmin): ?array {
    global $dbconnection;
    if ($id <= 0) {
        return null;
    }

    if ($isAdmin) {
        $stmt = $dbconnection->prepare("SELECT * FROM pengumuman WHERE id_pengumuman = ?");
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $dbconnection->prepare("SELECT * FROM pengumuman WHERE id_pengumuman = ? AND username = ?");
        if (!$stmt) return null;
        $stmt->bind_param("is", $id, $username);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function publish_pengumuman_revision(array $rev): int {
    global $dbconnection;

    $isNew    = empty($rev['pengumuman_id']);
    $judul    = $rev['judul'] ?? '';
    $judulSeo = $rev['judul_seo'] ?? seo_title($judul);
    $isi      = $rev['isi_pengumuman'] ?? '';
    $tgl      = $rev['tgl_posting'] ?? date('Y-m-d');
    $userRow  = $rev['username'] ?? '';

    if ($isNew) {
        $stmt = $dbconnection->prepare("
            INSERT INTO pengumuman (judul, judul_seo, isi_pengumuman, tgl_posting, username)
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query insert pengumuman.');
        }
        $stmt->bind_param("sssss", $judul, $judulSeo, $isi, $tgl, $userRow);
        $stmt->execute();
        $newId = $dbconnection->insert_id;
        $stmt->close();
        return (int)$newId;
    }

    $contentId = (int)$rev['pengumuman_id'];
    $stmt = $dbconnection->prepare("
        UPDATE pengumuman
           SET judul = ?,
               judul_seo = ?,
               isi_pengumuman = ?,
               tgl_posting = ?,
               username = ?
         WHERE id_pengumuman = ?
    ");
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan query update pengumuman.');
    }
    $stmt->bind_param("sssssi", $judul, $judulSeo, $isi, $tgl, $userRow, $contentId);
    $stmt->execute();
    $stmt->close();

    return $contentId;
}

function log_pengumuman_event(string $action, int $revId, ?int $targetId, string $createdBy, string $note = ''): void {
    $meta = array(
        'rev_id'       => $revId,
        'target_table' => 'pengumuman',
        'target_id'    => $targetId,
        'created_by'   => $createdBy,
    );
    if ($note !== '') {
        $meta['note'] = substr($note, 0, 120);
    }
    audit_event('approval', $action, 'pengumuman', $targetId, 'Pengumuman revision '.$action, null, null, $meta);
}

// ---------------------------
// HAPUS PENGUMUMAN (POST + CSRF)
// ---------------------------
if ($module == 'pengumuman' && $act == 'hapus') {
    require_post_csrf();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        exec_prepared("DELETE FROM pengumuman WHERE id_pengumuman = ?", "i", [$id]);
    }

    header("Location: /admin?module=".$module);
    exit;
}

// ---------------------------
// INPUT PENGUMUMAN (POST + CSRF) -> buat revisi
// ---------------------------
elseif ($module == 'pengumuman' && $act == 'input') {
    require_post_csrf();

    $judul          = $_POST['judul']          ?? '';
    $isi_pengumuman = $_POST['isi_pengumuman'] ?? '';
    $judul_seo      = seo_title($judul);
    $tgl_posting    = date("Y-m-d");

    if ($judul === '' || $isi_pengumuman === '') {
        alert_pengumuman('Judul dan isi pengumuman wajib diisi.');
    }

    $status     = $isAdmin ? 'APPROVED' : 'PENDING';
    $createdAt  = date('Y-m-d H:i:s');
    $approvedBy = $isAdmin ? $username : null;
    $approvedAt = $isAdmin ? $createdAt : null;
    $noteVal    = $isAdmin ? 'Auto-approved by admin' : null;

    $stmt = $dbconnection->prepare("
        INSERT INTO pengumuman_revisions
            (pengumuman_id, status, created_by, created_at, approved_by, approved_at, note, judul, judul_seo, isi_pengumuman, tgl_posting, username)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        alert_pengumuman('Gagal menyimpan revisi pengumuman.');
    }

    $pengumumanId = null;
    $stmt->bind_param(
        "isssssssssss",
        $pengumumanId,
        $status,
        $username,
        $createdAt,
        $approvedBy,
        $approvedAt,
        $noteVal,
        $judul,
        $judul_seo,
        $isi_pengumuman,
        $tgl_posting,
        $username
    );
    $stmt->execute();
    $revId = $dbconnection->insert_id;
    $stmt->close();

    $revRow = array(
        'pengumuman_id'  => null,
        'judul'          => $judul,
        'judul_seo'      => $judul_seo,
        'isi_pengumuman' => $isi_pengumuman,
        'tgl_posting'    => $tgl_posting,
        'username'       => $username,
    );

    log_pengumuman_event('CREATE_REVISION', (int)$revId, null, $username, (string)$noteVal);

    if ($isAdmin) {
        try {
            $liveId = publish_pengumuman_revision($revRow);
            exec_prepared("UPDATE pengumuman_revisions SET pengumuman_id = ?, status = 'APPROVED', approved_by = ?, approved_at = ? WHERE rev_id = ?", "issi", [$liveId, $approvedBy, $approvedAt, $revId]);
            log_pengumuman_event('APPROVE', (int)$revId, (int)$liveId, $username, (string)$noteVal);
            header("Location: /admin?module=".$module);
            exit;
        } catch (Throwable $e) {
            alert_pengumuman('Gagal mempublikasikan pengumuman: '.$e->getMessage(), '/admin?module='.$module);
        }
    }

    echo "<script>alert('Revisi pengumuman dikirim untuk persetujuan admin.');window.location='/admin?module=pengumuman';</script>";
    exit;
}

// ---------------------------
// UPDATE PENGUMUMAN (POST + CSRF) -> buat revisi
// ---------------------------
elseif ($module == 'pengumuman' && $act == 'update') {
    require_post_csrf();

    $id             = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $judul          = $_POST['judul']          ?? '';
    $isi_pengumuman = $_POST['isi_pengumuman'] ?? '';
    $judul_seo      = seo_title($judul);

    if ($id <= 0) {
        alert_pengumuman('ID pengumuman tidak valid.', '/admin?module='.$module);
    }

    $existing = fetch_live_pengumuman($id, $username, $isAdmin);
    if (!$existing) {
        alert_pengumuman('Data pengumuman tidak ditemukan atau akses ditolak.', '/admin?module='.$module);
    }

    $status     = $isAdmin ? 'APPROVED' : 'PENDING';
    $createdAt  = date('Y-m-d H:i:s');
    $approvedBy = $isAdmin ? $username : null;
    $approvedAt = $isAdmin ? $createdAt : null;
    $noteVal    = $isAdmin ? 'Auto-approved by admin (edit)' : null;

    $stmt = $dbconnection->prepare("
        INSERT INTO pengumuman_revisions
            (pengumuman_id, status, created_by, created_at, approved_by, approved_at, note, judul, judul_seo, isi_pengumuman, tgl_posting, username)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        alert_pengumuman('Gagal menyimpan revisi pengumuman.');
    }

    $stmt->bind_param(
        "isssssssssss",
        $id,
        $status,
        $username,
        $createdAt,
        $approvedBy,
        $approvedAt,
        $noteVal,
        $judul,
        $judul_seo,
        $isi_pengumuman,
        $existing['tgl_posting'],
        $existing['username']
    );
    $stmt->execute();
    $revId = $dbconnection->insert_id;
    $stmt->close();

    $revRow = array(
        'pengumuman_id'  => $id,
        'judul'          => $judul,
        'judul_seo'      => $judul_seo,
        'isi_pengumuman' => $isi_pengumuman,
        'tgl_posting'    => $existing['tgl_posting'],
        'username'       => $existing['username'],
    );

    log_pengumuman_event('CREATE_REVISION', (int)$revId, $id, $username, (string)$noteVal);

    if ($isAdmin) {
        try {
            $liveId = publish_pengumuman_revision($revRow);
            exec_prepared("UPDATE pengumuman_revisions SET status = 'APPROVED', approved_by = ?, approved_at = ? WHERE rev_id = ?", "ssi", [$approvedBy, $approvedAt, $revId]);
            log_pengumuman_event('APPROVE', (int)$revId, (int)$liveId, $username, (string)$noteVal);
            header("Location: /admin?module=".$module);
            exit;
        } catch (Throwable $e) {
            alert_pengumuman('Gagal menerapkan revisi: '.$e->getMessage(), '/admin?module='.$module);
        }
    }

    echo "<script>alert('Revisi pengumuman dikirim untuk persetujuan admin.');window.location='/admin?module=pengumuman';</script>";
    exit;
}

// ---------------------------
// APPROVE revisi (POST + CSRF)
// ---------------------------
elseif ($module == 'pengumuman' && $act == 'approve') {
    if (!$isAdmin) {
        http_response_code(403);
        exit('Forbidden');
    }
    require_post_csrf();

    $revId = isset($_POST['rev_id']) ? (int)$_POST['rev_id'] : 0;
    $note  = trim_note_pengumuman($_POST['note'] ?? '');
    if ($revId <= 0) {
        alert_pengumuman('ID revisi tidak valid.', '/admin?module=approvalqueue');
    }

    $stmt = $dbconnection->prepare("SELECT * FROM pengumuman_revisions WHERE rev_id = ?");
    if (!$stmt) {
        alert_pengumuman('Gagal mengambil revisi.', '/admin?module=approvalqueue');
    }
    $stmt->bind_param("i", $revId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rev = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$rev || strtoupper($rev['status']) !== 'PENDING') {
        alert_pengumuman('Revisi tidak ditemukan atau sudah diproses.', '/admin?module=approvalqueue');
    }

    $publishRow = array(
        'pengumuman_id'  => $rev['pengumuman_id'],
        'judul'          => $rev['judul'],
        'judul_seo'      => $rev['judul_seo'],
        'isi_pengumuman' => $rev['isi_pengumuman'],
        'tgl_posting'    => $rev['tgl_posting'],
        'username'       => $rev['username'],
    );

    try {
        $liveId     = publish_pengumuman_revision($publishRow);
        $approvedAt = date('Y-m-d H:i:s');
        exec_prepared(
            "UPDATE pengumuman_revisions SET status = 'APPROVED', approved_by = ?, approved_at = ?, note = ?, pengumuman_id = ? WHERE rev_id = ?",
            "sssii",
            [$username, $approvedAt, $note, $liveId, $revId]
        );
        log_pengumuman_event('APPROVE', (int)$revId, (int)$liveId, $rev['created_by'] ?? $username, (string)$note);
        header("Location: /admin?module=approvalqueue");
        exit;
    } catch (Throwable $e) {
        alert_pengumuman('Gagal menerapkan revisi: '.$e->getMessage(), '/admin?module=approvalqueue');
    }
}

// ---------------------------
// REJECT revisi (POST + CSRF)
// ---------------------------
elseif ($module == 'pengumuman' && $act == 'reject') {
    if (!$isAdmin) {
        http_response_code(403);
        exit('Forbidden');
    }
    require_post_csrf();

    $revId = isset($_POST['rev_id']) ? (int)$_POST['rev_id'] : 0;
    $note  = trim_note_pengumuman($_POST['note'] ?? '');
    if ($revId <= 0) {
        alert_pengumuman('ID revisi tidak valid.', '/admin?module=approvalqueue');
    }

    $stmt = $dbconnection->prepare("SELECT * FROM pengumuman_revisions WHERE rev_id = ?");
    if (!$stmt) {
        alert_pengumuman('Gagal mengambil revisi.', '/admin?module=approvalqueue');
    }
    $stmt->bind_param("i", $revId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rev = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$rev || strtoupper($rev['status']) !== 'PENDING') {
        alert_pengumuman('Revisi tidak ditemukan atau sudah diproses.', '/admin?module=approvalqueue');
    }

    $approvedAt = date('Y-m-d H:i:s');
    exec_prepared(
        "UPDATE pengumuman_revisions SET status = 'REJECTED', approved_by = ?, approved_at = ?, note = ? WHERE rev_id = ?",
        "sssi",
        [$username, $approvedAt, $note, $revId]
    );
    log_pengumuman_event('REJECT', (int)$revId, $rev['pengumuman_id'] ?? null, $rev['created_by'] ?? $username, (string)$note);

    header("Location: /admin?module=approvalqueue");
    exit;
}

closedb();
?>
