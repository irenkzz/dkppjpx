<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	require_once __DIR__ . "/../../includes/bootstrap.php";
  	$aksi     = "/adminweb/modul/mod_pengumuman/aksi_pengumuman.php";
    $isAdmin  = (($_SESSION['leveluser'] ?? '') === 'admin');
    $username = $_SESSION['namauser'] ?? '';

    function ubah_tgl2($tglnyo){
		$fm=explode('-',$tglnyo);
		$tahun=$fm[0];
		$bulan=$fm[1];
		$tgll=$fm[2];
		
		$sekarang=$tgll."/".$bulan."/".$tahun;
		return $sekarang;
	}

    function revision_label_class_peng($status) {
      $s = strtoupper(trim((string)$status));
      if ($s === 'APPROVED') return 'label-success';
      if ($s === 'REJECTED') return 'label-danger';
      if ($s === 'PENDING') return 'label-warning';
      return 'label-default';
    }

    $myRevisions = array();
    if (!$isAdmin) {
      $revRes = querydb_prepared(
        "SELECT rev_id, pengumuman_id, judul, status, created_at, approved_at, approved_by, note
           FROM pengumuman_revisions
          WHERE created_by = ?
          ORDER BY rev_id DESC
          LIMIT 20",
        "s",
        array($username)
      );
      while ($revRes && $row = $revRes->fetch_assoc()) {
        $myRevisions[] = $row;
      }
    }

  // mengatasi variabel yang belum di definisikan (notice undefined index)
  $act = isset($_GET['act']) ? $_GET['act'] : '';  
?>
	<section class="content-header">
		<h1>Pengumuman</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=pengumuman&act=tambahpengumuman"><i class="fa fa-plus"></i>Tambah Pengumuman</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php

  switch($act){
    // Tampil Agenda
    default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="dataagenda" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Status</th>
                        <th>Tanggal Posting</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					if ($isAdmin){
						$query  = "
              SELECT p.*,
                     (SELECT COUNT(*) FROM pengumuman_revisions pr WHERE pr.pengumuman_id = p.id_pengumuman AND pr.status = 'PENDING') AS pending_rev
                FROM pengumuman p
               ORDER BY p.id_pengumuman DESC";
						$tampil = querydb($query);
					}
					else{
						$query  = "
              SELECT p.*,
                     (SELECT COUNT(*) FROM pengumuman_revisions pr WHERE pr.pengumuman_id = p.id_pengumuman AND pr.status = 'PENDING') AS pending_rev
                FROM pengumuman p
               WHERE p.username= ?
               ORDER BY p.id_pengumuman DESC";
						$tampil = querydb_prepared($query, "s", array($username));
					}
					$no=1;
					while ($tampil && ($r = $tampil->fetch_array())) {
						$tgl_posting = tgl_indo($r['tgl_posting']);
            $pendingCount = isset($r['pending_rev']) ? (int)$r['pending_rev'] : 0;
            $statusLabel  = '<span class="label label-success">Live</span>';
            if ($isAdmin) {
              $statusLabel .= ' <span class="badge '.($pendingCount > 0 ? 'bg-yellow' : 'bg-green').'">'.$pendingCount.' pending</span>';
            }

						echo "<tr><td>$no</td>";
						echo "<td width=\"350\">" . e($r['judul']) . "</td>";
            echo "<td>$statusLabel</td>";
						echo "<td align=\"center\">$tgl_posting</td>";
						echo "<td align=\"center\">
								<a href=\"?module=pengumuman&act=editpengumuman&id=".(int)$r['id_pengumuman']."\" title=\"Edit Data\">
									<i class=\"fa fa-pencil\"></i>
								</a> &nbsp;
								<form method=\"POST\" action=\"$aksi?module=pengumuman&act=hapus\" style=\"display:inline;\">";
									csrf_field();
						echo       "<input type=\"hidden\" name=\"id\" value=\"".(int)$r['id_pengumuman']."\">
									<button type=\"submit\"
											onclick=\"return confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS DATA INI ?')\"
											title=\"Hapus Data\"
											style=\"border:none;background:none;padding:0;cursor:pointer;\">
										<i class=\"fa fa-trash text-red\"></i>
									</button>
								</form>
							</td>
							</tr>";
						$no++;
					}
					?>
                    </tbody>
                  </table>
                </div><!-- /.box-body -->
              </div><!-- /.box -->

          <?php if (!$isAdmin): ?>
            <div class="box box-info">
              <div class="box-header with-border">
                <h3 class="box-title">Pengajuan Saya</h3>
              </div>
              <div class="box-body table-responsive no-padding">
                <table class="table table-striped">
                  <thead>
                    <tr><th>ID Revisi</th><th>Judul</th><th>Status</th><th>Target</th><th>Catatan</th><th>Dibuat</th><th>Diproses</th></tr>
                  </thead>
                  <tbody>
                    <?php if (empty($myRevisions)): ?>
                      <tr><td colspan="7">Belum ada pengajuan revisi.</td></tr>
                    <?php else: foreach ($myRevisions as $rev): ?>
                      <tr>
                        <td>#<?php echo (int)$rev['rev_id']; ?></td>
                        <td><?php echo e($rev['judul']); ?></td>
                        <td><span class="label <?php echo revision_label_class_peng($rev['status']); ?>"><?php echo e($rev['status']); ?></span></td>
                        <td><?php echo $rev['pengumuman_id'] ? 'Edit ID '.$rev['pengumuman_id'] : 'Pengumuman Baru'; ?></td>
                        <td><?php echo e($rev['note'] ?? ''); ?></td>
                        <td><?php echo e($rev['created_at'] ?? ''); ?></td>
                        <td><?php echo e($rev['approved_at'] ?? '-'); ?><?php echo !empty($rev['approved_by']) ? ' oleh '.e($rev['approved_by']) : ''; ?></td>
                      </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endif; ?>
<?php
	break;
	
	case "tambahpengumuman":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Pengumuman</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=pengumuman&act=input" class="form-horizontal" enctype="multipart/form-data">
					<?php csrf_field(); ?>
					<div class="box-body">
            <div class="alert alert-info">
              Pengumuman baru akan menunggu persetujuan admin sebelum tampil ke publik.
            </div>
						<div class="form-group">
							<label for="tema" class="col-sm-2 control-label">Judul</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="judul" name="judul" />
							</div>
						</div>
						<div class="form-group">
							<label for="isi_agenda" class="col-sm-2 control-label">Isi Pengumuman</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="isi_pengumuman" name="isi_pengumuman"></textarea>
							</div>
						</div>
					</div><!-- /.box-body -->
					<div class="box-footer">
						<button type="submit" class="btn btn-primary">Simpan</button> <button type="button" onclick="self.history.back()" class="btn">Batal</button>
					</div><!-- /.box-footer -->
				</form>
            </div><!-- /.box -->
<?php
	break;
	
	case "editpengumuman":
		$id = (int)($_GET['id'] ?? 0);

		if ($isAdmin) {
			$stmt = $dbconnection->prepare("SELECT * FROM pengumuman WHERE id_pengumuman = ?");
			$stmt->bind_param("i", $id);
		} else {
			$user = $_SESSION['namauser'];
			$stmt = $dbconnection->prepare("SELECT * FROM pengumuman WHERE id_pengumuman = ? AND username = ?");
			$stmt->bind_param("is", $id, $user);
		}

		$stmt->execute();
		$hasil = $stmt->get_result();
		$r = $hasil->fetch_array();
    $stmt->close();

    if (!$r) {
      echo "<p>Data pengumuman tidak ditemukan atau akses ditolak.</p>";
      break;
    }

?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Pengumuman</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=pengumuman&act=update" class="form-horizontal" enctype="multipart/form-data">
					<?php csrf_field(); ?>
					<input type="hidden" name="id" value="<?php echo $r['id_pengumuman']; ?>" />
					<div class="box-body">
            <div class="alert alert-info">
              Perubahan akan diproses sebagai revisi dan membutuhkan persetujuan admin.
            </div>
						<div class="form-group">
							<label for="tema" class="col-sm-2 control-label">Judul</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="judul" name="judul" value="<?php echo $r['judul']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="isi_agenda" class="col-sm-2 control-label">Isi Pengumuman</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="isi_pengumuman" name="isi_pengumuman"><?php echo $r['isi_pengumuman']; ?></textarea>
							</div>
						</div>
					</div><!-- /.box-body -->
					<div class="box-footer">
						<button type="submit" class="btn btn-primary">Update</button> <button type="button" onclick="self.history.back()" class="btn">Batal</button>
					</div><!-- /.box-footer -->
				</form>
              </div><!-- /.box -->
<?php
	break;
  }
?>
            </div><!-- /.col -->
		</div><!-- /.row -->
	</section><!-- /.section -->
<?php
}
?>
