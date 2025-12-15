<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  require_once __DIR__ . '/../../includes/bootstrap.php';
  $aksi = "/adminweb/modul/mod_templates/aksi_templates.php";

  // mengatasi variabel yang belum di definisikan (notice undefined index)
  $act = isset($_GET['act']) ? $_GET['act'] : '';  
?>
	<section class="content-header">
		<h1>Manajemen Templates</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=templates&act=tambahtemplates"><i class="fa fa-plus"></i>Tambah Templates</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php
	switch($act){
		// Tampil Templates
		default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="datatemplates" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Nama Templates</th>
                        <th>Pembuat</th>
                        <th>Folder</th>
                        <th>Aktif</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					$query  = "SELECT * FROM templates ORDER BY id_templates DESC";
					$tampil = querydb($query);
					$no=1;
					while ($r=$tampil->fetch_array()){  
						$judul   = e($r['judul'] ?? '');
						$pembuat = e($r['pembuat'] ?? '');
						$folder  = e($r['folder'] ?? '');
						$aktif   = e($r['aktif'] ?? '');
						$idTpl   = (int)($r['id_templates'] ?? 0);
						echo "<tr>
								<td>$no</td>
								<td>{$judul}</td>
								<td>{$pembuat}</td>
								<td>{$folder}</td>
								<td align=\"center\">{$aktif}</td>
								<td align=\"center\">
								<a href=\"?module=templates&act=edittemplates&id={$idTpl}\" title=\"Edit Data\">
									<i class=\"fa fa-pencil\"></i>
								</a> &nbsp;

								<form method=\"POST\" action=\"$aksi?module=templates&act=hapus\" style=\"display:inline;\">
									" . csrf_field() . "
									<input type=\"hidden\" name=\"id\" value=\"{$idTpl}\">
									<button type=\"submit\" onclick=\"return confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS TEMPLATES INI ?')\" 
											title=\"Hapus Data\" style=\"border:none;background:none;padding:0;cursor:pointer;\">
									<i class=\"fa fa-trash text-red\"></i>
									</button>
								</form> &nbsp;

								<form method=\"POST\" action=\"$aksi?module=templates&act=aktifkan\" style=\"display:inline;\">
									" . csrf_field() . "
									<input type=\"hidden\" name=\"id\" value=\"{$idTpl}\">
									<button type=\"submit\" title=\"Aktifkan\" style=\"border:none;background:none;padding:0;cursor:pointer;\">
									<i class=\"fa fa-check text-green\"></i>
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
<?php
		break;
		
		case "tambahtemplates":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Templates</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=templates&act=input" class="form-horizontal">
					<div class="box-body">
						<div class="form-group">
							<label for="nama_templates" class="col-sm-2 control-label">Nama Templates</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="nama_templates" name="nama_templates" />
							</div>
						</div>
						<div class="form-group">
							<label for="pembuat" class="col-sm-2 control-label">Pembuat</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="pembuat" name="pembuat" />
							</div>
						</div>
						<div class="form-group">
							<label for="folder" class="col-sm-2 control-label">Folder</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="folder" name="folder" />
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
		
		case "edittemplates":
			$id_templates = isset($_GET['id']) ? (int)$_GET['id'] : 0;
			$hasil = querydb_prepared("SELECT * FROM templates WHERE id_templates = ?", "i", [$id_templates]);
			$r     = $hasil->fetch_array();

?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Templates</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=templates&act=update" class="form-horizontal">
					<input type="hidden" name="id" value="<?php echo (int)($r['id_templates'] ?? 0); ?>">
					<div class="box-body">
						<div class="form-group">
							<label for="nama_templates" class="col-sm-2 control-label">Nama Templates</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="nama_templates" name="nama_templates" value="<?php echo e($r['nama_templates'] ?? ''); ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="pembuat" class="col-sm-2 control-label">Pembuat</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="pembuat" name="pembuat" value="<?php echo e($r['pembuat'] ?? ''); ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="folder" class="col-sm-2 control-label">Folder</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="folder" name="folder" value="<?php echo e($r['folder'] ?? ''); ?>" />
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
