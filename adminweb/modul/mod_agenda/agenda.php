<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	require_once __DIR__ . "/../../includes/bootstrap.php";
  	$aksi     = "/adminweb/modul/mod_agenda/aksi_agenda.php";
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

    function revision_label_class_agenda($status) {
      $s = strtoupper(trim((string)$status));
      if ($s === 'APPROVED') return 'label-success';
      if ($s === 'REJECTED') return 'label-danger';
      if ($s === 'PENDING') return 'label-warning';
      return 'label-default';
    }

    $myRevisions = array();
    if (!$isAdmin) {
      $revRes = querydb_prepared(
        "SELECT rev_id, agenda_id, tema, status, created_at, approved_at, approved_by, note
           FROM agenda_revisions
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
		<h1>Agenda</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=agenda&act=tambahagenda"><i class="fa fa-plus"></i>Tambah Agenda</a></li>
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
                        <th>Tema Acara</th>
                        <th>Status</th>
                        <th>Tgl. Acara</th>
                        <th>Tgl. Posting</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					if ($isAdmin){
						$query  = "
              SELECT a.*,
                     (SELECT COUNT(*) FROM agenda_revisions ar WHERE ar.agenda_id = a.id_agenda AND ar.status = 'PENDING') AS pending_rev
                FROM agenda a
               ORDER BY a.id_agenda DESC";
						$tampil = querydb($query);
					}
					else{
						 $tampil   = querydb_prepared(
						 	"SELECT a.*,
                    (SELECT COUNT(*) FROM agenda_revisions ar WHERE ar.agenda_id = a.id_agenda AND ar.status = 'PENDING') AS pending_rev
               FROM agenda a
              WHERE a.username = ?
              ORDER BY a.id_agenda DESC",
							 "s",
						 	array($username)
						 );
					}
					$no=1;
					while ($tampil && ($r=$tampil->fetch_array())){  
						$tgl_mulai   = tgl_indo($r['tgl_mulai']);
						$tgl_selesai = tgl_indo($r['tgl_selesai']);
						$tgl_posting = tgl_indo($r['tgl_posting']);
            $pendingCount = isset($r['pending_rev']) ? (int)$r['pending_rev'] : 0;
            $statusLabel  = '<span class="label label-success">Live</span>';
            if ($isAdmin) {
              $statusLabel .= ' <span class="badge '.($pendingCount > 0 ? 'bg-yellow' : 'bg-green').'">'.$pendingCount.' pending</span>';
            }
						echo "<tr><td>$no</td>
							<td width=\"350\">".e($r['tema'])."</td>";
						if ($tgl_mulai==$tgl_selesai){
							echo "<td>$statusLabel</td><td>$tgl_mulai</td>";
						} 
						else{
							echo "<td>$statusLabel</td><td>$tgl_mulai s/d $tgl_selesai</td>";
						}
						echo "<td align=\"center\">$tgl_posting</td>
								<td align=\"center\">
								<a href=\"?module=agenda&act=editagenda&id=$r[id_agenda]\" title=\"Edit Data\"><i class=\"fa fa-pencil\"></i></a> &nbsp;

								<form method=\"POST\" action=\"$aksi?module=agenda&act=hapus\" style=\"display:inline;\">";
						csrf_field();
						echo	"<input type=\"hidden\" name=\"id\" value=\"$r[id_agenda]\">
								<button type=\"submit\" onclick=\"return confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS AGENDA INI ?')\" title=\"Hapus Data\" style=\"border:none;background:none;padding:0;cursor:pointer;\">
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
                    <tr><th>ID Revisi</th><th>Tema</th><th>Status</th><th>Target</th><th>Catatan</th><th>Dibuat</th><th>Diproses</th></tr>
                  </thead>
                  <tbody>
                    <?php if (empty($myRevisions)): ?>
                      <tr><td colspan="7">Belum ada pengajuan revisi.</td></tr>
                    <?php else: foreach ($myRevisions as $rev): ?>
                      <tr>
                        <td>#<?php echo (int)$rev['rev_id']; ?></td>
                        <td><?php echo e($rev['tema']); ?></td>
                        <td><span class="label <?php echo revision_label_class_agenda($rev['status']); ?>"><?php echo e($rev['status']); ?></span></td>
                        <td><?php echo $rev['agenda_id'] ? 'Edit ID '.$rev['agenda_id'] : 'Agenda Baru'; ?></td>
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
	
	case "tambahagenda":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Agenda</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=agenda&act=input" class="form-horizontal" enctype="multipart/form-data">
					<?php csrf_field(); ?>
					<div class="box-body">
            <div class="alert alert-info">
              Agenda baru akan dikirim sebagai revisi dan menunggu persetujuan admin.
            </div>
						<div class="form-group">
							<label for="tema" class="col-sm-2 control-label">Tema Acara</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="tema" name="tema" />
							</div>
						</div>
						<div class="form-group">
							<label for="isi_agenda" class="col-sm-2 control-label">Isi Agenda</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="isi_agenda" name="isi_agenda"></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="tempat" class="col-sm-2 control-label">Tempat Acara</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="tempat" name="tempat" />
							</div>
						</div>
						<div class="form-group">
							<label for="tgl_mulai" class="col-sm-2 control-label">Tgl. Mulai</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="tgl_mulai" name="tgl_mulai" />
							</div>
						</div>
						<div class="form-group">
							<label for="tgl_selesai" class="col-sm-2 control-label">Tgl. Selesai</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="tgl_selesai" name="tgl_selesai" />
								<small>- Apabila acara hanya sehari, isikan tanggal yang sama dengan Tgl. Mulai.</small>
							</div>
						</div>
						<div class="form-group">
							<label for="jam" class="col-sm-2 control-label">Pukul</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="jam" name="jam" />
							</div>
						</div>
						<div class="form-group">
							<label for="pengirim" class="col-sm-2 control-label">Pengirim</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="pengirim" name="pengirim" />
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
	
	case "editagenda":
      if ($isAdmin){
        $id_agenda = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$hasil     = querydb_prepared("SELECT * FROM agenda WHERE id_agenda = ?", "i", [$id_agenda]);
      }
      else{
        $id_agenda = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$hasil     = querydb_prepared(
		"SELECT * FROM agenda WHERE id_agenda = ? AND username = ?",
		"is",
		[$id_agenda, $_SESSION['namauser']]
		);
      }

      $r = $hasil ? $hasil->fetch_array() : null;
      if (!$r) {
        echo "<p>Data agenda tidak ditemukan atau akses ditolak.</p>";
        break;
      }
	  $tgl_mulai   = ubah_tgl2($r['tgl_mulai']);
      $tgl_selesai = ubah_tgl2($r['tgl_selesai']);
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Agenda</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=agenda&act=update" class="form-horizontal" enctype="multipart/form-data">
					<?php csrf_field(); ?>
					<input type="hidden" name="id" value="<?php echo $r['id_agenda']; ?>" />
					<div class="box-body">
            <div class="alert alert-info">
              Perubahan akan dikirim sebagai revisi dan perlu disetujui admin sebelum menggantikan konten live.
            </div>
						<div class="form-group">
							<label for="tema" class="col-sm-2 control-label">Tema Acara</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="tema" name="tema" value="<?php echo $r['tema']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="isi_agenda" class="col-sm-2 control-label">Isi Agenda</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="isi_agenda" name="isi_agenda"><?php echo $r['isi_agenda']; ?></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="tempat" class="col-sm-2 control-label">Tempat Acara</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="tempat" name="tempat" value="<?php echo $r['tempat']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="tgl_mulai" class="col-sm-2 control-label">Tgl. Mulai</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="tgl_mulai" name="tgl_mulai" value="<?php echo $tgl_mulai; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="tgl_selesai" class="col-sm-2 control-label">Tgl. Selesai</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="tgl_selesai" name="tgl_selesai" value="<?php echo $tgl_selesai; ?>" />
								<small>- Apabila acara hanya sehari, isikan tanggal yang sama dengan Tgl. Mulai.</small>
							</div>
						</div>
						<div class="form-group">
							<label for="jam" class="col-sm-2 control-label">Pukul</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="jam" name="jam" value="<?php echo $r['jam']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="pengirim" class="col-sm-2 control-label">Pengirim</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="pengirim" name="pengirim" value="<?php echo $r['pengirim']; ?>" />
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
