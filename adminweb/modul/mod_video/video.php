<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  $aksi = "/adminweb/modul/mod_video/aksi_video.php";

  // mengatasi variabel yang belum di definisikan (notice undefined index)
  $act = isset($_GET['act']) ? $_GET['act'] : '';  
?>
	<section class="content-header">
		<h1>Video</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=video&act=tambahvideo"><i class="fa fa-plus"></i>Tambah Video</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php

  switch($act){
    // Tampil Video
    default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="datavideo" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Judul Video</th>
                        <th>Link Youtube</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					$query  = "SELECT * FROM video ORDER BY id_video DESC";
					$tampil = querydb($query);
					$no=1;
					while ($r=$tampil->fetch_array()){  
						echo '<tr>';
						echo '  <td>' . $no . '</td>';
						echo '  <td>' . e($r['judul_video']) . '</td>';
						echo '  <td>http://www.youtube.com/watch?v=' . e($r['link_youtube']) . '</td>';
						echo '  <td align="center">';
						echo '    <a href="?module=video&act=editvideo&id=' . (int)$r['id_video'] . '" title="Edit Video">';
						echo '      <i class="fa fa-pencil"></i>';
						echo '    </a> &nbsp;';
						echo '    <form method="POST" action="' . $aksi . '?module=video&act=hapus" style="display:inline"';
						echo '          onsubmit="return confirm(\'APAKAH ANDA YAKIN AKAN MENGHAPUS VIDEO INI ?\')">';
						csrf_field();
						echo '      <input type="hidden" name="id" value="' . (int)$r['id_video'] . '">';
						echo '      <button type="submit" class="btn btn-link" title="Hapus Video"';
						echo '              style="padding:0;border:none;background:transparent">';
						echo '        <i class="fa fa-trash text-red"></i>';
						echo '      </button>';
						echo '    </form>';
						echo '  </td>';
						echo '</tr>';
						$no++;
					}
					?>
                    </tbody>
                  </table>
                </div><!-- /.box-body -->
              </div><!-- /.box -->
<?php
	break;
	
	case "tambahvideo":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Video</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=video&act=input" class="form-horizontal" enctype="multipart/form-data">
					<div class="box-body">
						<div class="form-group">
							<label for="judul_video" class="col-sm-2 control-label">Judul Video</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="judul_video" name="judul_video" />
							</div>
						</div>
						<div class="form-group">
							<label for="link_youtube" class="col-sm-2 control-label">Link Youtube</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="link_youtube" name="link_youtube" />
							</div>
						</div>
						<div class="form-group">
							<label for="deskripsi" class="col-sm-2 control-label">Deskripsi</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="isi_video" name="deskripsi"></textarea>
							</div>
						</div>
						<!--<div class="form-group">
							<label for="fupload" class="col-sm-2 control-label">Gambar</label>
							<div class="col-sm-10">
								<input type="file" class="form-control" id="fupload" name="fupload" />
							</div>
						</div>-->
					</div><!-- /.box-body -->
					<div class="box-footer">
						<button type="submit" class="btn btn-primary">Simpan</button> <button type="button" onclick="self.history.back()" class="btn">Batal</button>
					</div><!-- /.box-footer -->
				</form>
            </div><!-- /.box -->
<?php
	break;
	
	case "editvideo":
      $query = "SELECT * FROM video WHERE id_video='$_GET[id]'";
      $hasil = querydb($query);

      $r = $hasil->fetch_array();
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Video</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=video&act=update" class="form-horizontal" enctype="multipart/form-data">
					<input type="hidden" name="id" value="<?php echo $r['id_video']; ?>" />
					<div class="box-body">
						<div class="form-group">
							<label for="judul_video" class="col-sm-2 control-label">Judul Video</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="judul_video" name="judul_video" value="<?php echo $r['judul_video']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="link_youtube" class="col-sm-2 control-label">Link Youtube</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="link_youtube" name="link_youtube" value="http://www.youtube.com/watch?v=<?php echo $r['link_youtube']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="deskripsi" class="col-sm-2 control-label">Deskripsi</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="isi_video" name="deskripsi"><?php echo $r['deskripsi']; ?></textarea>
							</div>
						</div>
						<!--<div class="form-group">
							<label for="fupload" class="col-sm-2 control-label">Gambar</label>
							<div class="col-sm-10">
								<?php
								if ($r['gambar']!=''){
									echo "<img src=\"../foto_video/small_$r[gambar]\">";  
								}
								else{
									echo "Belum ada gambar";
								}
								?>
							</div>
						</div>
						<div class="form-group">
							<label for="fupload" class="col-sm-2 control-label">Ganti Gambar</label>
							<div class="col-sm-10">
								<input type="file" class="form-control" id="fupload" name="fupload" />
								<small>- Apabila gambar tidak diganti, dikosongkan saja.</small>
							</div>
						</div>-->
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