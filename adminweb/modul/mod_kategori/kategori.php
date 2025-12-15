<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	require_once __DIR__ . '/../../includes/bootstrap.php';
	$aksi = "/adminweb/modul/mod_kategori/aksi_kategori.php";

	// mengatasi variabel yang belum di definisikan (notice undefined index)
	$act = isset($_GET['act']) ? $_GET['act'] : ''; 
?>
	<section class="content-header">
		<h1>Kategori</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=kategori&act=tambahkategori"><i class="fa fa-plus"></i>Tambah Kategori</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php
	switch($act){
		// Tampil Kategori
		default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="datakategori" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th>Link</th>
                        <th>Aktif</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					$query  = "SELECT * FROM kategori ORDER BY id_kategori DESC";
					$tampil = querydb($query);
					$no=1;
					while ($r=$tampil->fetch_array()){  
						$idKat  = (int)($r['id_kategori'] ?? 0);
						$nama   = e($r['nama_kategori'] ?? '');
						$seo    = e($r['kategori_seo'] ?? '');
						$link   = e("kategori-{$idKat}-{$seo}.html");
						$aktif  = e($r['aktif'] ?? '');
						$aktifBadge = ($aktif === 'Y')
							? '<span class="label label-success">Aktif</span>'
							: ($aktif === 'N'
								? '<span class="label label-default">Tidak</span>'
								: '<span class="label label-default">'.($aktif === '' ? '-' : $aktif).'</span>');
						echo "<tr><td class=\"text-center\">{$no}</td>
								<td>{$nama}</td>
								<td>{$link}</td>
								<td class=\"text-center\">{$aktifBadge}</td>
								<td class=\"text-center\"><a href=\"?module=kategori&act=editkategori&id={$idKat}\" title=\"Edit Data\"><i class=\"fa fa-pencil\"></i></a></td>
								</tr>";
						$no++;
					}
					?>
                    </tbody>
                  </table>
                </div><!-- /.box-body -->
				<div class="box-footer">
					<i>*) Data pada Kategori tidak bisa dihapus, tapi bisa di non-aktifkan melalui Edit Kategori.</i>
                </div><!-- /.box-footer -->
              </div><!-- /.box -->
<?php
		break;
		
		case "tambahkategori":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Kategori</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=kategori&act=input" class="form-horizontal">
					<?php csrf_field(); ?>
					<div class="box-body">
						<div class="form-group">
							<label for="nama_kategori" class="col-sm-2 control-label">Nama Kategori</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="nama_kategori" name="nama_kategori" />
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
		
		case "editkategori":
			$id_kategori = isset($_GET['id']) ? (int)$_GET['id'] : 0;
			$hasil = querydb_prepared("SELECT * FROM kategori WHERE id_kategori = ?", "i", [$id_kategori]);
			$r     = $hasil ? $hasil->fetch_array() : [];
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Kategori</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=kategori&act=update" class="form-horizontal">
					<?php csrf_field(); ?>
					<input type="hidden" name="id" value="<?php echo (int)($r['id_kategori'] ?? 0); ?>">
					<div class="box-body">
						<div class="form-group">
							<label for="nama_kategori" class="col-sm-2 control-label">Nama Kategori</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="nama_kategori" name="nama_kategori" value="<?php echo e($r['nama_kategori'] ?? ''); ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="aktif" class="col-sm-2 control-label">Aktif</label>
							<div class="col-sm-6">
								<?php $aktifVal = (isset($r['aktif']) && $r['aktif'] === 'Y') ? 'Y' : 'N'; ?>
								<div class="yn-toggle" data-name="aktif" data-yes="Y" data-no="N">
									<input type="hidden" name="aktif" value="<?php echo $aktifVal; ?>">
									<button type="button" class="btn btn-default btn-xs yn-yes">Aktif</button>
									<button type="button" class="btn btn-default btn-xs yn-no">Tidak</button>
								</div>
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
		</div>
	</section>
<?php
}
?>
