<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	if (!isset($_SESSION['leveluser']) || $_SESSION['leveluser'] !== 'admin') {
		echo "<script>alert('Anda tidak memiliki izin untuk mengakses modul ini.'); window.location = '/admin';</script>";
		exit;
	}

	require_once __DIR__ . "/../../includes/bootstrap.php";

	$aksi = "/adminweb/modul/mod_polling/aksi_polling.php";

	// mengatasi variabel yang belum di definisikan (notice undefined index)
	$act = isset($_GET['act']) ? $_GET['act'] : ''; 
?>
	<section class="content-header">
		<h1>Polling</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=polling&act=tambahpolling"><i class="fa fa-plus"></i>Tambah Polling</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php
	switch($act){
		// Tampil Polling
		default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="datapolling" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Pilihan</th>
                        <th>Status</th>
                        <th>Aktif</th>
                        <th>Rating</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					$query  = "SELECT * FROM poling ORDER BY id_poling DESC";
					$tampil = querydb($query);
					                    $no=1;
                    while ($r=$tampil->fetch_array()){
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $no; ?></td>
                            <td><?php echo e($r['pilihan']); ?></td>
                            <td>
                                <?php
                                $statusVal = isset($r['status']) ? $r['status'] : '';
                                if ($statusVal === 'Jawaban') {
                                    echo '<span class="label label-success">Jawaban</span>';
                                } elseif ($statusVal === 'Pertanyaan') {
                                    echo '<span class="label label-primary">Pertanyaan</span>';
                                } else {
                                    echo '<span class="label label-default">'.e($statusVal).'</span>';
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $aktifVal = isset($r['aktif']) ? $r['aktif'] : '';
                                if ($aktifVal === 'Y') {
                                    echo '<span class="label label-success">Aktif</span>';
                                } elseif ($aktifVal === 'N') {
                                    echo '<span class="label label-default">Tidak</span>';
                                } else {
                                    echo '<span class="label label-default">'.e($aktifVal).'</span>';
                                }
                                ?>
                            </td>
                            <td class="text-center"><?php echo e($r['rating']); ?></td>
                            <td class="text-center">
                                <form action="<?php echo $aksi; ?>?module=polling&act=hapus" method="POST" style="display:inline;">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$r['id_poling']; ?>">
                                    <button type="submit"
                                            onclick="return confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS DATA INI ?');"
                                            title="Hapus Data"
                                            style="background:none;border:none;padding:0;">
                                        <i class="fa fa-trash text-red"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php
                        $no++;
                    }
					?>
                    </tbody>
                  </table>
                </div><!-- /.box-body -->
              </div><!-- /.box -->
<?php
		break;
		
		case "tambahpolling":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Polling</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=polling&act=input" class="form-horizontal">
				<?php csrf_field(); ?>	
					<div class="box-body">
						<div class="form-group">
							<label for="pilihan" class="col-sm-2 control-label">Pilihan</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="pilihan" name="pilihan" />
							</div>
						</div>
						<div class="form-group">
							<label for="status" class="col-sm-2 control-label">Status</label>
							<div class="col-sm-10">
								<label><input type="radio" class="minimal" id="status" name="status" value="Jawaban" checked /> Jawaban &nbsp; </label>
								<label><input type="radio" class="minimal" id="status" name="status" value="Pertanyaan" /> Pertanyaan </label>
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
	
	case "editpolling":
			$id_poling = isset($_GET['id']) ? (int)$_GET['id'] : 0;
			$hasil = querydb_prepared("SELECT * FROM poling WHERE id_poling = ?", "i", [$id_poling]);
			$r     = $hasil->fetch_array();
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Polling</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=polling&act=update" class="form-horizontal">
					<?php csrf_field(); ?>
					<input type="hidden" name="id" value="<?php echo $r['id_poling']; ?>">
					<div class="box-body">
						<div class="form-group">
							<label for="pilihan" class="col-sm-2 control-label">Pilihan</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="pilihan" name="pilihan" value="<?php echo $r['pilihan']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="status" class="col-sm-2 control-label">Status</label>
							<div class="col-sm-10">
								<?php if($r['status']=="Jawaban") { ?>
								<label><input type="radio" class="minimal" id="status" name="status" value="Jawaban" checked /> Jawaban &nbsp; </label>
								<label><input type="radio" class="minimal" id="status" name="status" value="Pertanyaan" /> Pertanyaan </label>
								<?php } else { ?>
								<label><input type="radio" class="minimal" id="status" name="status" value="Jawaban" /> Jawaban &nbsp; </label>
								<label><input type="radio" class="minimal" id="status" name="status" value="Pertanyaan" checked /> Pertanyaan </label>
								<?php } ?>
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
