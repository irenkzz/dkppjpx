<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	require_once __DIR__ . "/../../includes/bootstrap.php";
  $aksi = "modul/mod_listslider/aksi_listslider.php";
  function ubah_tgl2($tglnyo){
		$fm=explode('-',$tglnyo);
		$tahun=$fm[0];
		$bulan=$fm[1];
		$tgll=$fm[2];
		
		$sekarang=$tgll."/".$bulan."/".$tahun;
		return $sekarang;
	}
  // mengatasi variabel yang belum di definisikan (notice undefined index)
  $act = isset($_GET['act']) ? $_GET['act'] : '';  
?>
	<section class="content-header">
		<h1>List Slider</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=listslider&act=tambahlistslider"><i class="fa fa-plus"></i>Tambah List Slider</a></li>
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
                        <th>Nama Menu</th>
                        <th>Keterangan</th>
                        <th>Link</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					
						$query  = "SELECT * FROM listslider ORDER BY id_list DESC";
						$tampil = querydb($query);
					
					$no=1;
					while ($r=$tampil->fetch_array()){  
						?>
						<tr>
							<td><?php echo $no; ?></td>
							<td width="350"><?php echo e($r['nama_menu']); ?></td>
							<td><?php echo e($r['keterangan']); ?></td>
							<td><?php echo e($r['link']); ?></td>
							<td align="center">
								<a href="?module=listslider&act=editlistslider&id=<?php echo (int)$r['id_list']; ?>" title="Edit Data">
									<i class="fa fa-pencil"></i>
								</a> &nbsp;
								<form method="POST" action="<?php echo $aksi; ?>?module=listslider&act=hapus" style="display:inline;">
									<?php csrf_field(); ?>
									<input type="hidden" name="id" value="<?php echo (int)$r['id_list']; ?>">
									<button type="submit" onclick="return confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS LIST MENU INI ?')" title="Hapus Data" style="border:none;background:none;padding:0;cursor:pointer;">
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
	
	case "tambahlistslider":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah List Slider</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=listslider&act=input" class="form-horizontal" enctype="multipart/form-data">
					<?php csrf_field(); ?>
					<div class="box-body">
						<div class="form-group">
							<label for="tema" class="col-sm-2 control-label">Nama Menu</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="nama_menu" name="nama_menu" />
							</div>
						</div>
						<div class="form-group">
							<label for="isi_agenda" class="col-sm-2 control-label">Keterangan</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="keterangan" name="keterangan"></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="tema" class="col-sm-2 control-label">Link Menu</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="link_menu" name="link_menu" />
								<small>Format URL Link harus seperti contoh (Menggunakan http): http://namadinas.lomboktimurkab.go.id</small>
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
	
	case "editlistslider":
      $id_list = isset($_GET['id']) ? (int)$_GET['id'] : 0;
      $hasil = querydb_prepared("SELECT * FROM listslider WHERE id_list = ?", "i", [$id_list]);
      $r = $hasil->fetch_array();

?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit List Slider</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=listslider&act=update" class="form-horizontal" enctype="multipart/form-data">
					<?php csrf_field(); ?>
					<input type="hidden" name="id" value="<?php echo $r['id_list']; ?>" />
					<div class="box-body">
						<div class="form-group">
							<label for="tema" class="col-sm-2 control-label">Nama Menu</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="nama_menu" name="nama_menu" value="<?php echo $r['nama_menu']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="isi_agenda" class="col-sm-2 control-label">Keterangan</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="keterangan" name="keterangan"><?php echo $r['keterangan']; ?></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="tema" class="col-sm-2 control-label">Link Menu</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="link_menu" name="link_menu" value="<?php echo $r['link']; ?>" />
								<small>Format URL Link harus seperti contoh (Menggunakan http): http://namadinas.lomboktimurkab.go.id</small>
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
