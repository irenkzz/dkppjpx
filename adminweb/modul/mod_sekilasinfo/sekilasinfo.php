<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . '/../../includes/bootstrap.php';
  $aksi = "modul/mod_sekilasinfo/aksi_sekilasinfo.php";
 
  // mengatasi variabel yang belum di definisikan (notice undefined index)
  $act = isset($_GET['act']) ? $_GET['act'] : '';  
?>
	<section class="content-header">
		<h1>Sekilas Info</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=sekilasinfo&act=tambahsekilasinfo"><i class="fa fa-plus"></i>Tambah Info</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php

  switch($act){
    // Tampil Sekilas Info
    default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="dataagenda" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Info</th>
                        <th>Tanggal Posting</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					
						$query  = "SELECT * FROM sekilasinfo ORDER BY id_sekilas DESC";
						$tampil = querydb($query);
					
					$no=1;
					while ($r=$tampil->fetch_array()){

					        $tgl_posting = e(tgl_indo($r['tgl_posting']));
					        $info_text   = e($r['info'] ?? '');
					        $idSekilas   = (int)($r['id_sekilas'] ?? 0);

					?><tr>

					        <td><?php echo $no; ?></td>

					        <td><?php echo $info_text; ?></td>

					        <td><?php echo $tgl_posting; ?></td>

					        <td align="center">

					                <a href="?module=sekilasinfo&amp;act=editsekilasinfo&amp;id=<?php echo $idSekilas; ?>" title="Edit Data"><i class="fa fa-pencil"></i></a> &nbsp;

					                <form action="<?php echo $aksi; ?>?module=sekilasinfo&amp;act=hapus" method="POST" style="display:inline;">

					                        <?php csrf_field(); ?>

					                        <input type="hidden" name="id" value="<?php echo $idSekilas; ?>">

					                        <button type="submit" onclick="return confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS INFO INI ?')" title="Hapus Data" style="background:none;border:none;padding:0;">

					                                <i class="fa fa-trash text-red"></i>

					                        </button>

					                </form>

					        </td>

					</tr><?php

					        $no++;

					}

					?>
                    </tbody>
                  </table>
                </div><!-- /.box-body -->
              </div><!-- /.box -->
<?php
	break;
	
	case "tambahsekilasinfo":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Info</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=sekilasinfo&act=input" class="form-horizontal" enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
					<div class="box-body">
						<div class="form-group">
							<label for="isi_agenda" class="col-sm-2 control-label">Info</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="info" name="info"></textarea>
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
	
	case "editsekilasinfo":

        $id_sekilas = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $hasil = querydb_prepared("SELECT * FROM sekilasinfo WHERE id_sekilas = ?", "i", [$id_sekilas]);

      $r = $hasil->fetch_array();

?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Info</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=sekilasinfo&act=update" class="form-horizontal" enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
					<input type="hidden" name="id" value="<?php echo (int)($r['id_sekilas'] ?? 0); ?>" />
					<div class="box-body">
						<div class="form-group">
							<label for="isi_agenda" class="col-sm-2 control-label">Info</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="info" name="info"><?php echo e($r['info'] ?? ''); ?></textarea>
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
