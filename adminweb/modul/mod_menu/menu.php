<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
        require_once __DIR__ . '/../../includes/bootstrap.php';
  $aksi = "modul/mod_menu/aksi_menu.php";

  // mengatasi variabel yang belum di definisikan (notice undefined index)
  $act = isset($_GET['act']) ? $_GET['act'] : '';  
?>
	<section class="content-header">
		<h1>Menu Website</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=menu&act=tambahmenu"><i class="fa fa-plus"></i>Tambah Menu</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php

	switch($act){  
		// Tampil Menu 
		default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="datamenu" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Nama Menu</th>
                        <th>Link</th>
                        <th>Level Menu</th>
                        <th>Aktif</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					$query  = "SELECT * FROM menu";
					$tampil = querydb($query);
					$no=1;
					while ($r=$tampil->fetch_array()){

					        $parent = 'Menu Utama';

					        $query2 = "SELECT nama_menu FROM menu WHERE id_menu=?";

					        $hasil  = querydb_prepared($query2, "i", [(int)$r['id_parent']]);

					        if ($hasil && $hasil->num_rows > 0){

					                $s = $hasil->fetch_array();

					                if ($s && isset($s['nama_menu'])){

					                        $parent = $s['nama_menu'];

					                }

					        }

					        $namaMenu = e($r['nama_menu'] ?? '');
					        $link     = e($r['link'] ?? '');
					        $parent   = e($parent ?? '');
					        $aktif    = e($r['aktif'] ?? '');
					        $idMenu   = (int)($r['id_menu'] ?? 0);
					?><tr>

					        <td><?php echo $no; ?></td>

					        <td><?php echo $namaMenu; ?></td>

					        <td><?php echo $link; ?></td>

					        <td><?php echo $parent; ?></td>

					        <td align="center"><?php echo $aktif; ?></td>

					        <td align="center">

					                <a href="?module=menu&amp;act=editmenu&amp;id=<?php echo $idMenu; ?>" title="Edit Data"><i class="fa fa-pencil"></i></a> &nbsp;

					                <form action="<?php echo $aksi; ?>?module=menu&amp;act=hapus" method="POST" style="display:inline;">

					                        <?php csrf_field(); ?>

					                        <input type="hidden" name="id" value="<?php echo $idMenu; ?>">

					                        <button type="submit" onclick="return confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS MENU INI ?')" title="Hapus Data" style="background:none;border:none;padding:0;">

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
		
		case "tambahmenu":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Menu</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=menu&act=input" class="form-horizontal">
                                        <?php echo csrf_field(); ?>
					<div class="box-body">
						<div class="form-group">
							<label for="nama_menu" class="col-sm-2 control-label">Nama Menu</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="nama_menu" name="nama_menu" />
							</div>
						</div>
						<div class="form-group">
							<label for="link" class="col-sm-2 control-label">Link</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="link" name="link" />
							</div>
						</div>
						<div class="form-group">
							<label for="id_parent" class="col-sm-2 control-label">Level Menu</label>
							<div class="col-sm-6">
								<select class="form-control select2" id="id_parent" name="id_parent">
									<option value="0" selected>Menu Utama</option>
									<?php
									$query  = "SELECT * FROM menu WHERE id_parent=0 ORDER BY id_menu";
									$tampil = querydb($query);
									while($r=$tampil->fetch_array()){
										$menuIdOption = (int)($r['id_menu'] ?? 0);
										$menuTitleOpt = e($r['nama_menu'] ?? '');
										echo "<option value=\"{$menuIdOption}\">{$menuTitleOpt}</option>";
									}
									?>
								</select>
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
		
		case "editmenu":
                        $id_menu = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                        $hasil   = querydb_prepared("SELECT * FROM menu WHERE id_menu = ?", "i", [$id_menu]);
                        $r       = $hasil->fetch_array();
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Menu</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=menu&act=update" class="form-horizontal">
                                        <?php echo csrf_field(); ?>
					<input type="hidden" name="id" value="<?php echo (int)($r['id_menu'] ?? 0); ?>">
					<div class="box-body">
						<div class="form-group">
							<label for="nama_menu" class="col-sm-2 control-label">Nama Menu</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="nama_menu" name="nama_menu" value="<?php echo e($r['nama_menu'] ?? ''); ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="link" class="col-sm-2 control-label">Link</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="link" name="link" value="<?php echo e($r['link'] ?? ''); ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="id_parent" class="col-sm-2 control-label">Level Menu</label>
							<div class="col-sm-6">
								<select class="form-control select2" id="id_parent" name="id_parent">
									<?php if ($r['parent_id']==0){ ?>
										<option value="0" selected>Menu Utama</option>
									<?php } else { ?>
										<option value="0">Menu Utama</option>
									<?php
									}
									$query2  = "SELECT * FROM menu WHERE id_parent=0 ORDER BY id_menu";
									$tampil2 = querydb($query2);
									while($w=$tampil2->fetch_array()){
										$parentId   = (int)($w['id_menu'] ?? 0);
										$parentName = e($w['nama_menu'] ?? '');
										if ($r['id_parent']==$w['id_menu']){
											echo "<option value=\"{$parentId}\" selected>{$parentName}</option>";
										} else {
											if ($w['id_menu']==$r['id_menu']){
												echo "<option value=\"0\">Tanpa Level</option>";
											}
											else{
												echo "<option value=\"{$parentId}\">{$parentName}</option>";
											}
										}
									}
									?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="aktif" class="col-sm-2 control-label">Aktif</label>
							<div class="col-sm-6">
								<?php
								if($r['aktif']=="Y") {
								?>
									<label><input type="radio" class="minimal" id="aktif" name="aktif" value="Y" checked> Y &nbsp; </label>
									<label><input type="radio" class="minimal" id="aktif" name="aktif" value="N"> N </label>
								<?php
								}
								elseif($r['aktif']=="N") {
								?>
									<label><input type="radio" class="minimal" id="aktif" name="aktif" value="Y"> Y &nbsp; </label>
									<label><input type="radio" class="minimal" id="aktif" name="aktif" value="N" checked> N </label>
								<?php
								}
								?>
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
