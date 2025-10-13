<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
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
						$tgl_posting = tgl_indo($r['tgl_posting']);
						echo "<tr><td>$no</td>
							<td>$r[info]</td>";
						echo "<td>$tgl_posting</td>
							<td align=\"center\"><a href=\"?module=sekilasinfo&act=editsekilasinfo&id=$r[id_sekilas]\" title=\"Edit Data\"><i class=\"fa fa-pencil\"></i></a> &nbsp; 
							<a href=\"$aksi?module=sekilasinfo&act=hapus&id=$r[id_sekilas]\" onclick=\"return confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS INFO INI ?')\" title=\"Hapus Data\"><i class=\"fa fa-trash text-red\"></i></a></td>
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
	
	case "tambahsekilasinfo":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Info</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=sekilasinfo&act=input" class="form-horizontal" enctype="multipart/form-data">
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

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $dbconnection->prepare("SELECT * FROM sekilasinfo WHERE id_sekilas = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $hasil = $stmt->get_result();

      $r = $hasil->fetch_array();
        $stmt->close();

?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Info</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=sekilasinfo&act=update" class="form-horizontal" enctype="multipart/form-data">
					<input type="hidden" name="id" value="<?php echo $r['id_sekilas']; ?>" />
					<div class="box-body">
						<div class="form-group">
							<label for="isi_agenda" class="col-sm-2 control-label">Info</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="info" name="info"><?php echo $r['info']; ?></textarea>
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