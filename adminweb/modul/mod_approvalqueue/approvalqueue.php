<?php
// Admin only approval queue
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
	exit;
}

require_once __DIR__ . '/../../includes/bootstrap.php';

if (($_SESSION['leveluser'] ?? '') !== 'admin') {
    echo "<section class=\"content\"><div class=\"alert alert-danger\">Hanya admin yang dapat mengakses antrean persetujuan.</div></section>";
    exit;
}

$allowedTypes = array('berita','pengumuman','agenda');
$typeFilter   = isset($_GET['type']) && in_array($_GET['type'], $allowedTypes, true) ? $_GET['type'] : 'all';
$createdBy    = trim($_GET['created_by'] ?? '');
$act          = $_GET['act'] ?? '';

function load_pending_revisions(string $typeFilter, string $createdBy): array {
    $pending = array();

    if ($typeFilter === 'all' || $typeFilter === 'berita') {
        $sql = "SELECT rev_id, berita_id AS target_id, judul AS title, created_by, created_at, note
                  FROM berita_revisions
                 WHERE status = 'PENDING'";
        $types = '';
        $params = array();
        if ($createdBy !== '') {
            $sql   .= " AND created_by LIKE ?";
            $types .= 's';
            $params[] = '%'.$createdBy.'%';
        }
        $sql .= " ORDER BY rev_id DESC";
        $rows = $types === '' ? querydb($sql) : querydb_prepared($sql, $types, $params);
        while ($rows && $row = $rows->fetch_assoc()) {
            $row['type'] = 'berita';
            $pending[] = $row;
        }
    }

    if ($typeFilter === 'all' || $typeFilter === 'pengumuman') {
        $sql = "SELECT rev_id, pengumuman_id AS target_id, judul AS title, created_by, created_at, note
                  FROM pengumuman_revisions
                 WHERE status = 'PENDING'";
        $types = '';
        $params = array();
        if ($createdBy !== '') {
            $sql   .= " AND created_by LIKE ?";
            $types .= 's';
            $params[] = '%'.$createdBy.'%';
        }
        $sql .= " ORDER BY rev_id DESC";
        $rows = $types === '' ? querydb($sql) : querydb_prepared($sql, $types, $params);
        while ($rows && $row = $rows->fetch_assoc()) {
            $row['type'] = 'pengumuman';
            $pending[] = $row;
        }
    }

    if ($typeFilter === 'all' || $typeFilter === 'agenda') {
        $sql = "SELECT rev_id, agenda_id AS target_id, tema AS title, created_by, created_at, note
                  FROM agenda_revisions
                 WHERE status = 'PENDING'";
        $types = '';
        $params = array();
        if ($createdBy !== '') {
            $sql   .= " AND created_by LIKE ?";
            $types .= 's';
            $params[] = '%'.$createdBy.'%';
        }
        $sql .= " ORDER BY rev_id DESC";
        $rows = $types === '' ? querydb($sql) : querydb_prepared($sql, $types, $params);
        while ($rows && $row = $rows->fetch_assoc()) {
            $row['type'] = 'agenda';
            $pending[] = $row;
        }
    }

    usort($pending, function ($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });

    return $pending;
}

function load_revision_detail(string $type, int $revId): ?array {
    $table = '';
    switch ($type) {
        case 'berita': $table = 'berita_revisions'; break;
        case 'pengumuman': $table = 'pengumuman_revisions'; break;
        case 'agenda': $table = 'agenda_revisions'; break;
        default: return null;
    }
    $res = querydb_prepared("SELECT * FROM {$table} WHERE rev_id = ?", "i", array($revId));
    return $res && $res->num_rows > 0 ? $res->fetch_assoc() : null;
}

function load_live_row(string $type, ?int $id): ?array {
    if (!$id) return null;
    switch ($type) {
        case 'berita':
            $res = querydb_prepared("SELECT * FROM berita WHERE id_berita = ?", "i", array($id));
            return $res && $res->num_rows > 0 ? $res->fetch_assoc() : null;
        case 'pengumuman':
            $res = querydb_prepared("SELECT * FROM pengumuman WHERE id_pengumuman = ?", "i", array($id));
            return $res && $res->num_rows > 0 ? $res->fetch_assoc() : null;
        case 'agenda':
            $res = querydb_prepared("SELECT * FROM agenda WHERE id_agenda = ?", "i", array($id));
            return $res && $res->num_rows > 0 ? $res->fetch_assoc() : null;
        default:
            return null;
    }
}

function map_preview_fields(string $type, array $row): array {
    if ($type === 'berita') {
        return array(
            'Judul'        => $row['judul'] ?? '',
            'Kategori ID'  => $row['id_kategori'] ?? '',
            'Username'     => $row['username'] ?? '',
            'Tanggal'      => $row['tanggal'] ?? '',
            'Jam'          => $row['jam'] ?? '',
            'Tag'          => $row['tag'] ?? '',
            'Gambar'       => $row['gambar'] ?? '',
            'Catatan'      => $row['note'] ?? '',
        );
    }
    if ($type === 'pengumuman') {
        return array(
            'Judul'        => $row['judul'] ?? '',
            'Tanggal'      => $row['tgl_posting'] ?? '',
            'Username'     => $row['username'] ?? '',
            'Isi'          => $row['isi_pengumuman'] ?? '',
            'Catatan'      => $row['note'] ?? '',
        );
    }
    return array(
        'Tema'         => $row['tema'] ?? '',
        'Tempat'       => $row['tempat'] ?? '',
        'Tanggal Mulai'=> $row['tgl_mulai'] ?? '',
        'Tanggal Selesai'=> $row['tgl_selesai'] ?? '',
        'Tanggal Posting'=> $row['tgl_posting'] ?? '',
        'Jam'          => $row['jam'] ?? '',
        'Pengirim'     => $row['pengirim'] ?? '',
        'Gambar'       => $row['gambar'] ?? '',
        'Catatan'      => $row['note'] ?? '',
    );
}

?>
<section class="content-header">
    <h1>Antrean Persetujuan</h1>
    <small>Gabungan revisi pending dari Berita, Pengumuman, dan Agenda</small>
</section>

<section class="content">
    <?php if ($act === 'preview'): ?>
        <?php
        $revType = $_GET['revtype'] ?? '';
        $revId   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!in_array($revType, $allowedTypes, true) || $revId <= 0) {
            echo "<div class=\"alert alert-danger\">Parameter pratinjau tidak valid.</div>";
        } else {
            $rev = load_revision_detail($revType, $revId);
            if (!$rev) {
                echo "<div class=\"alert alert-warning\">Revisi tidak ditemukan.</div>";
            } elseif (strtoupper($rev['status']) !== 'PENDING') {
                echo "<div class=\"alert alert-info\">Revisi sudah diproses.</div>";
            } else {
                $live = load_live_row($revType, isset($rev[$revType.'_id']) ? (int)$rev[$revType.'_id'] : null);
                $revFields  = map_preview_fields($revType, $rev);
                $liveFields = $live ? map_preview_fields($revType, $live) : array();
                ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="box box-primary">
                            <div class="box-header with-border"><h3 class="box-title">Snapshot Revisi</h3></div>
                            <div class="box-body no-padding">
                                <table class="table table-striped">
                                    <?php foreach ($revFields as $k => $v): ?>
                                        <tr><th><?php echo e($k); ?></th><td><?php echo nl2br(e($v)); ?></td></tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="box box-default">
                            <div class="box-header with-border"><h3 class="box-title">Data Live Saat Ini</h3></div>
                            <div class="box-body no-padding">
                                <?php if (empty($liveFields)): ?>
                                    <p class="text-muted" style="padding:10px;">Belum ada data live (konten baru).</p>
                                <?php else: ?>
                                    <table class="table table-striped">
                                        <?php foreach ($liveFields as $k => $v): ?>
                                            <tr><th><?php echo e($k); ?></th><td><?php echo nl2br(e($v)); ?></td></tr>
                                        <?php endforeach; ?>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box box-warning">
                    <div class="box-header with-border"><h3 class="box-title">Tindak Lanjut</h3></div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <form method="POST" action="/adminweb/modul/mod_<?php echo $revType; ?>/aksi_<?php echo $revType; ?>.php?module=<?php echo $revType; ?>&act=approve" class="form-inline">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="rev_id" value="<?php echo (int)$revId; ?>">
                                    <div class="form-group">
                                        <label for="note-approve">Catatan</label>
                                        <input type="text" name="note" id="note-approve" class="form-control" placeholder="Opsional">
                                    </div>
                                    <button type="submit" class="btn btn-success">Setujui &amp; Terapkan</button>
                                </form>
                            </div>
                            <div class="col-sm-6">
                                <form method="POST" action="/adminweb/modul/mod_<?php echo $revType; ?>/aksi_<?php echo $revType; ?>.php?module=<?php echo $revType; ?>&act=reject" class="form-inline">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="rev_id" value="<?php echo (int)$revId; ?>">
                                    <div class="form-group">
                                        <label for="note-reject">Catatan</label>
                                        <input type="text" name="note" id="note-reject" class="form-control" placeholder="Alasan penolakan">
                                    </div>
                                    <button type="submit" class="btn btn-danger">Tolak</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
        <a class="btn btn-default" href="?module=approvalqueue">&laquo; Kembali ke antrean</a>
    <?php else: ?>
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Filter</h3>
            </div>
            <div class="box-body">
                <form method="GET" class="form-inline">
                    <input type="hidden" name="module" value="approvalqueue">
                    <div class="form-group">
                        <label for="type">Tipe</label>
                        <select name="type" id="type" class="form-control">
                            <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>Semua</option>
                            <option value="berita" <?php echo $typeFilter === 'berita' ? 'selected' : ''; ?>>Berita</option>
                            <option value="pengumuman" <?php echo $typeFilter === 'pengumuman' ? 'selected' : ''; ?>>Pengumuman</option>
                            <option value="agenda" <?php echo $typeFilter === 'agenda' ? 'selected' : ''; ?>>Agenda</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-left:10px;">
                        <label for="created_by">Dibuat oleh</label>
                        <input type="text" id="created_by" name="created_by" value="<?php echo e($createdBy); ?>" class="form-control" placeholder="username">
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-left:10px;">Terapkan</button>
                </form>
            </div>
        </div>

        <?php $pending = load_pending_revisions($typeFilter, $createdBy); ?>

        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Revisi Pending</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr><th>ID</th><th>Tipe</th><th>Judul/Tema</th><th>Dibuat Oleh</th><th>Dibuat Pada</th><th>Target</th><th>Catatan</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending)): ?>
                            <tr><td colspan="8">Tidak ada revisi pending.</td></tr>
                        <?php else: foreach ($pending as $row): ?>
                            <tr>
                                <td>#<?php echo (int)$row['rev_id']; ?></td>
                                <td><?php echo ucfirst($row['type']); ?></td>
                                <td><?php echo e($row['title']); ?></td>
                                <td><?php echo e($row['created_by']); ?></td>
                                <td><?php echo e($row['created_at']); ?></td>
                                <td><?php echo $row['target_id'] ? 'Edit ID '.$row['target_id'] : 'Entri Baru'; ?></td>
                                <td><?php echo e($row['note'] ?? ''); ?></td>
                                <td>
                                    <a class="btn btn-xs btn-default" href="?module=approvalqueue&act=preview&revtype=<?php echo $row['type']; ?>&id=<?php echo (int)$row['rev_id']; ?>">Preview</a>
                                    <form method="POST" action="/adminweb/modul/mod_<?php echo $row['type']; ?>/aksi_<?php echo $row['type']; ?>.php?module=<?php echo $row['type']; ?>&act=approve" style="display:inline-block; margin:0 2px;">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="rev_id" value="<?php echo (int)$row['rev_id']; ?>">
                                        <button type="submit" class="btn btn-xs btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="/adminweb/modul/mod_<?php echo $row['type']; ?>/aksi_<?php echo $row['type']; ?>.php?module=<?php echo $row['type']; ?>&act=reject" style="display:inline-block; margin:0 2px;">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="rev_id" value="<?php echo (int)$row['rev_id']; ?>">
                                        <button type="submit" class="btn btn-xs btn-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
