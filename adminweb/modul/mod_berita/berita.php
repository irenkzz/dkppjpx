<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	require_once __DIR__ . '/../../includes/bootstrap.php';
	
  // fungsi untuk check box Tag (Berita Terkait) di form input dan edit berita 
  function GetCheckBox($table, $key, $Label, $Nilai='') {
    $s = "SELECT * FROM $table ORDER BY $Label";
    $u = querydb($s);
    $_arrNilai = explode(',', $Nilai);
    $str = '';
    while ($t = $u->fetch_array()) {
      $_ck = (array_search($t[$key], $_arrNilai) === false)? '' : 'checked';
      $str .= "<input type=\"checkbox\" class=\"minimal\" name='".$key."[]' value=\"$t[$key]\" $_ck> $t[$Label] &nbsp; ";
    }
    return $str;
  }

  function revision_label_class($status) {
    $s = strtoupper(trim((string)$status));
    if ($s === 'APPROVED') return 'label-success';
    if ($s === 'REJECTED') return 'label-danger';
    if ($s === 'PENDING') return 'label-warning';
    return 'label-default';
  }

  $aksi      = "/adminweb/modul/mod_berita/aksi_berita.php";
  $isAdmin   = (($_SESSION['leveluser'] ?? '') === 'admin');
  $usernameS = $_SESSION['namauser'] ?? '';

  $myRevisions = array();
  if (!$isAdmin) {
    $revRes = querydb_prepared(
      "SELECT rev_id, berita_id, judul, status, created_at, approved_at, approved_by, note
         FROM berita_revisions
        WHERE created_by = ?
        ORDER BY rev_id DESC
        LIMIT 20",
      "s",
      array($usernameS)
    );
    while ($revRes && $row = $revRes->fetch_assoc()) {
      $myRevisions[] = $row;
    }
  }

  // mengatasi variabel yang belum di definisikan (notice undefined index)
  $act = isset($_GET['act']) ? $_GET['act'] : '';  
?>
	<section class="content-header">
		<h1>Berita</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=berita&act=tambahberita"><i class="fa fa-plus"></i>Tambah Berita</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php

  switch($act){
    // Tampil Berita
    default:
		// daftar berita live + hitung revisi pending
		if ($isAdmin){
			$sqlLive = "
        SELECT b.*, k.nama_kategori,
               (SELECT COUNT(*) FROM berita_revisions br WHERE br.berita_id = b.id_berita AND br.status = 'PENDING') AS pending_rev
          FROM berita b
          JOIN kategori k ON b.id_kategori = k.id_kategori
         ORDER BY b.id_berita DESC";
			$tampil = querydb($sqlLive);
		}
		else{
			$sqlLive = "
        SELECT b.*, k.nama_kategori,
               (SELECT COUNT(*) FROM berita_revisions br WHERE br.berita_id = b.id_berita AND br.status = 'PENDING') AS pending_rev
          FROM berita b
          JOIN kategori k ON b.id_kategori = k.id_kategori
         WHERE b.username = ?
         ORDER BY b.id_berita DESC";
			$tampil = querydb_prepared($sqlLive, "s", array($usernameS));
		}
?>
              <div class="box">
                <div class="box-body">
                  <table id="databerita" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Tgl. Posting</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					$no=1;
					while ($tampil && ($r=$tampil->fetch_array())){  
						$tgl_posting=tgl_indo($r['tanggal']);
            $pendingCount = isset($r['pending_rev']) ? (int)$r['pending_rev'] : 0;
            $statusLabel  = '<span class="label label-success">Live</span>';
            if ($isAdmin) {
              $statusLabel .= ' <span class="badge '.($pendingCount > 0 ? 'bg-yellow' : 'bg-green').'">'.($pendingCount).' pending</span>';
            }
						echo '<tr>
								<td>'.$no.'</td>
								<td width="350">'.e($r['judul']).'</td>
								<td>'.e($r['nama_kategori']).'</td>
                <td>'.$statusLabel.'</td>
								<td>'.$tgl_posting.'</td>
								<td align="center">
									<a href="?module=berita&act=editberita&id='.$r['id_berita'].'" title="Edit Berita">
										<i class="fa fa-pencil"></i>
									</a> &nbsp;
									<form method="POST" action="'.$aksi.'?module=berita&act=hapus" style="display:inline" onsubmit="return confirm(\'APAKAH ANDA YAKIN AKAN MENGHAPUS BERITA INI ?\')">
										<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8').'">
										<input type="hidden" name="id" value="'.(int)$r['id_berita'].'">
										<button type="submit" class="btn btn-link" title="Hapus Berita" style="padding:0; border:none;">
											<i class="fa fa-trash text-red"></i>
										</button>
									</form>
								</td>
							</tr>';
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
                        <td><span class="label <?php echo revision_label_class($rev['status']); ?>"><?php echo e($rev['status']); ?></span></td>
                        <td><?php echo $rev['berita_id'] ? 'Edit ID '.$rev['berita_id'] : 'Berita Baru'; ?></td>
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
	
	case "tambahberita":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Berita</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=berita&act=input" class="form-horizontal" enctype="multipart/form-data">
					<?php csrf_field(); ?>
          <div class="box-body">
            <div class="alert alert-info">
              Pengajuan baru akan masuk antrean persetujuan admin sebelum tampil di situs.
            </div>
						<div class="form-group">
							<label for="judul" class="col-sm-2 control-label">Judul</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="judul" name="judul" />
							</div>
						</div>

						<div class="form-group">
							<label for="kategori" class="col-sm-2 control-label">Kategori</label>
							<div class="col-sm-10">
								<select class="form-control select2" id="kategori" name="kategori" required>
									<option value="0" selected>- Pilih Kategori -</option>
									<?php
									$query  = "SELECT * FROM kategori ORDER BY nama_kategori";
									$tampil = querydb($query);
									while($r=$tampil->fetch_array()){
										echo "<option value=\"$r[id_kategori]\">$r[nama_kategori]</option>";
									}
									?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label for="tag" class="col-sm-2 control-label">Tag</label>
							<div class="col-sm-10">
								<?php echo GetCheckBox("tag", "tag_seo", "nama_tag"); ?>
							</div>
						</div>
								
						<div class="form-group">
							<label for="isi_berita" class="col-sm-2 control-label">Isi Berita</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="isi_berita" name="isi_berita"></textarea>
							</div>
						</div>
						
						<div class="form-group">
							<label for="fupload" class="col-sm-2 control-label">Gambar</label>
							<div class="col-sm-10">
								<input type="file" class="form-control" id="fupload" name="fupload" />
								<small>- Tipe gambar harus JPG (disarankan lebar gambar 350 px).</small>
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
	
	case "editberita":
		// sanitasi id dari URL
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		if ($id <= 0) {
			echo "<p>ID berita tidak valid.</p>";
			break;
		}

		if ($isAdmin) {
			// admin boleh edit semua berita
			$stmt = $dbconnection->prepare("SELECT * FROM berita WHERE id_berita = ?");
			$stmt->bind_param("i", $id);
		} else {
			// user hanya boleh edit berita miliknya sendiri
			$user = $_SESSION['namauser'] ?? '';
			$stmt = $dbconnection->prepare("SELECT * FROM berita WHERE id_berita = ? AND username = ?");
			$stmt->bind_param("is", $id, $user);
		}

		$stmt->execute();
		$hasil = $stmt->get_result();
		$r     = $hasil->fetch_array();
		$stmt->close();

		if (!$r) {
			echo "<p>Data berita tidak ditemukan atau Anda tidak berhak mengedit data ini.</p>";
			break;
		}
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Berita</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=berita&act=update" class="form-horizontal" enctype="multipart/form-data">
					<?php csrf_field(); ?>
					<input type="hidden" name="id" value="<?php echo $r['id_berita']; ?>" />
					<div class="box-body">
            <div class="alert alert-info">
              Perubahan akan masuk antrean revisi dan harus disetujui admin sebelum menggantikan konten live.
            </div>
						<div class="form-group">
							<label for="judul" class="col-sm-2 control-label">Judul</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="judul" name="judul" value="<?php echo $r['judul']; ?>" />
							</div>
						</div>

						<div class="form-group">
							<label for="kategori" class="col-sm-2 control-label">Kategori</label>
							<div class="col-sm-10">
								<select class="form-control select2" id="kategori" name="kategori" required>
									<?php if ($r['id_kategori']==0){ ?>
										<option value="0" selected>- Pilih Kategori -</option>
									<?php 
									}
									$query2  = "SELECT * FROM kategori ORDER BY nama_kategori";
									$tampil2 = querydb($query2);
									while($w=$tampil2->fetch_array()){
										if ($r['id_kategori']==$w['id_kategori']){
											echo "<option value=\"$w[id_kategori]\" selected>$w[nama_kategori]</option>";
										}
										else{
											echo "<option value=\"$w[id_kategori]\">$w[nama_kategori]</option>";
										}
									}
									?>
								</select>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-2 control-label">Tag</label>
							<div class="col-sm-10">
								<?php echo GetCheckBox("tag", "tag_seo", "nama_tag", $r['tag']); ?>
							</div>
						</div>
						
						<div class="form-group">
							<label for="isi_berita" class="col-sm-2 control-label">Isi Berita</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="isi_berita" name="isi_berita"><?php echo $r['isi_berita']; ?></textarea>
							</div>
						</div>
						
						<div class="form-group">
							<label for="fupload" class="col-sm-2 control-label">Gambar</label>
							<div class="col-sm-10">
								<?php
								if ($r['gambar']!=''){
									echo "<img src=\"../foto_berita/small_$r[gambar]\">";  
								}
								else{
									echo "Berita tidak ada gambarnya";
								}
								?>
							</div>
						</div>

						<div class="form-group">
							<label for="fupload" class="col-sm-2 control-label">Ganti Gambar</label>
							<div class="col-sm-10">
								<input type="file" class="form-control" id="fupload" name="fupload" />
								<small>- Apabila gambar tidak diganti, dikosongkan saja.<br />- Tipe gambar harus JPG (disarankan lebar gambar 350 px).</small>
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
