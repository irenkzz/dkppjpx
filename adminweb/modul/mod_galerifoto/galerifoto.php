<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	require_once __DIR__ . "/../../includes/bootstrap.php";
  $aksi = "modul/mod_galerifoto/aksi_galerifoto.php";

  // mengatasi variabel yang belum di definisikan (notice undefined index)
  $act = isset($_GET['act']) ? $_GET['act'] : '';  
?>
	<section class="content-header">
		<h1>Galeri Foto</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=galerifoto&act=tambahgalerifoto"><i class="fa fa-plus"></i>Tambah Galeri Photo</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php

  switch($act){
    // Tampil Galeri Foto
    default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="datagalerifoto" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Judul Foto</th>
                        <th>Album Photo</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					$stmt = $dbconnection->prepare("
					SELECT g.id_gallery, g.jdl_gallery, g.gbr_gallery, a.jdl_album
						FROM gallery g
						JOIN album a ON g.id_album = a.id_album
					ORDER BY g.id_gallery DESC
					");
					$stmt->execute();
					if (method_exists($stmt, 'get_result')) {
						$res = $stmt->get_result();
						$no = 1;
						while ($r = $res->fetch_assoc()) {
							$idGallery = (int)($r['id_gallery'] ?? 0);
							$img       = e($r['gbr_gallery'] ?? '');
							$title     = e($r['jdl_gallery'] ?? '');
							$album     = e($r['jdl_album'] ?? '');
							$imgSrc    = "../img_galeri/small_{$img}";

							echo "<tr><td>{$no}</td>
								<td><img src=\"{$imgSrc}\" width=\"100\" height=\"75\" alt=\"{$title}\"></td>
								<td>{$title}</td>
								<td>{$album}</td>
								<td align=\"center\">
									<a href=\"?module=galerifoto&act=editgalerifoto&id={$idGallery}\"><i class=\"fa fa-pencil\"></i></a> &nbsp;
									<form action=\"{$aksi}?module=galerifoto&act=hapus\" method=\"POST\" style=\"display:inline\">";
							csrf_field();
							echo	"<input type=\"hidden\" name=\"id\" value=\"{$idGallery}\" />
										<a href=\"#\" onclick=\"if(confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS FOTO INI ?')){ this.closest('form').submit(); } return false;\"><i class=\"fa fa-trash text-red\"></i></a>
									</form>
								</td>
								</tr>";
							$no++;
						}
					} else {
						$stmt->bind_result($id_gallery, $jdl_gallery, $gbr_gallery, $jdl_album);
						$no = 1;
						while ($stmt->fetch()) {
							$idGallery = (int)$id_gallery;
							$img       = e($gbr_gallery ?? '');
							$title     = e($jdl_gallery ?? '');
							$album     = e($jdl_album ?? '');
							$imgSrc    = "../img_galeri/small_{$img}";

							echo "<tr><td>{$no}</td>
								<td><img src=\"{$imgSrc}\" width=\"100\" height=\"75\" alt=\"{$title}\"></td>
								<td>{$title}</td>
								<td>{$album}</td>
								<td align=\"center\">
									<a href=\"?module=galerifoto&act=editgalerifoto&id={$idGallery}\"><i class=\"fa fa-pencil\"></i></a> &nbsp;
									<form action=\"{$aksi}?module=galerifoto&act=hapus\" method=\"POST\" style=\"display:inline\">";
							csrf_field();
							echo			"<input type=\"hidden\" name=\"id\" value=\"{$idGallery}\" />
										<a href=\"#\" onclick=\"if(confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS FOTO INI ?')){ this.closest('form').submit(); } return false;\"><i class=\"fa fa-trash text-red\"></i></a>
									</form>
								</td>
								</tr>";
							$no++;
						}
					}
					$stmt->close();
					?>

                    </tbody>
                  </table>
                </div><!-- /.box-body -->
              </div><!-- /.box -->
<?php
	break;
	
	case "tambahgalerifoto":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Galeri Foto</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=galerifoto&act=input" class="form-horizontal" enctype="multipart/form-data">
					<?php echo csrf_field(); ?>
					<div class="box-body">
						<div class="form-group">
							<label for="judul_galeri" class="col-sm-2 control-label">Judul Foto</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="judul_galeri" name="judul_galeri" />
							</div>
						</div>
						<div class="form-group">
							<label for="album" class="col-sm-2 control-label">Album Photo</label>
							<div class="col-sm-10">
								<select class="form-control select2" id="album" name="album">
									<option value="0" selected>- Pilih Album Photo -</option>
									<?php
									$stmt = $dbconnection->prepare("SELECT id_album, jdl_album FROM album ORDER BY id_album DESC");
									$stmt->execute();
									if (method_exists($stmt, 'get_result')) {
										$res = $stmt->get_result();
										while($r = $res->fetch_assoc()){
											$idAlbumOpt = (int)($r['id_album'] ?? 0);
											$albumTitle = e($r['jdl_album'] ?? '');
											echo "<option value=\"{$idAlbumOpt}\">{$albumTitle}</option>";
										}
									} else {
										$stmt->bind_result($id_album, $jdl_album);
										while($stmt->fetch()){
											$idAlbumOpt = (int)$id_album;
											$albumTitle = e($jdl_album ?? '');
											echo "<option value=\"{$idAlbumOpt}\">{$albumTitle}</option>";
										}
									}
									$stmt->close();
									?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="keterangan" class="col-sm-2 control-label">Keterangan</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="keterangan" name="keterangan" />
							</div>
						</div>
						<div class="form-group">
							<label for="fupload" class="col-sm-2 control-label">Foto</label>
							<div class="col-sm-10">
								<input type="file" class="form-control" id="fupload" name="fupload" />
								<small>- Tipe foto harus JPG/JPEG</small>
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
	
	case "editgalerifoto":
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$stmt = $dbconnection->prepare("SELECT * FROM gallery WHERE id_gallery = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		if (method_exists($stmt, 'get_result')) {
			$res = $stmt->get_result();
			$r = $res->fetch_array();
		} else {
			$stmt->bind_result($id_gallery, $jdl_gallery, $gallery_seo, $id_album, $keterangan, $gbr_gallery);
			$r = $stmt->fetch()
				? ['id_gallery'=>$id_gallery,'jdl_gallery'=>$jdl_gallery,'gallery_seo'=>$gallery_seo,'id_album'=>$id_album,'keterangan'=>$keterangan,'gbr_gallery'=>$gbr_gallery]
				: null;
		}
		$stmt->close();
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit Galeri Foto</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=galerifoto&act=update" class="form-horizontal" enctype="multipart/form-data">
					<?php echo csrf_field(); ?>
					<input type="hidden" name="id" value="<?php echo (int)($r['id_gallery'] ?? 0); ?>" />
					<div class="box-body">
						<div class="form-group">
							<label for="judul_galeri" class="col-sm-2 control-label">Judul Foto</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="judul_galeri" name="judul_galeri" value="<?php echo e($r['jdl_gallery'] ?? ''); ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="album" class="col-sm-2 control-label">Album Photo</label>
							<div class="col-sm-10">
								<select class="form-control select2" id="album" name="album">
									<?php if ($r['id_album']==0){ ?>
									<option value="0" selected>- Pilih Album Photo -</option>
									<?php
									}
									$stmt2 = $dbconnection->prepare("SELECT id_album, jdl_album FROM album ORDER BY id_album DESC");
									$stmt2->execute();
									if (method_exists($stmt2, 'get_result')) {
										$res2 = $stmt2->get_result();
										while ($w = $res2->fetch_assoc()) {
											$albumIdOption = (int)($w['id_album'] ?? 0);
											$albumTitleOpt = e($w['jdl_album'] ?? '');
											if ($r['id_album'] == $w['id_album']){
												echo "<option value=\"{$albumIdOption}\" selected>{$albumTitleOpt}</option>";
											} else {
												echo "<option value=\"{$albumIdOption}\">{$albumTitleOpt}</option>";
											}
										}
									} else {
										$stmt2->bind_result($id_album2, $jdl_album2);
										while ($stmt2->fetch()) {
											$albumIdOption = (int)$id_album2;
											$albumTitleOpt = e($jdl_album2 ?? '');
											if ($r['id_album'] == $id_album2){
												echo "<option value=\"{$albumIdOption}\" selected>{$albumTitleOpt}</option>";
											} else {
												echo "<option value=\"{$albumIdOption}\">{$albumTitleOpt}</option>";
											}
										}
									}
									$stmt2->close();
									?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="keterangan" class="col-sm-2 control-label">Keterangan</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="keterangan" name="keterangan" value="<?php echo e($r['keterangan'] ?? ''); ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="fupload" class="col-sm-2 control-label">Gambar</label>
							<div class="col-sm-10">
								<?php
								if ($r['gbr_gallery']!=''){
									$currentImg = e($r['gbr_gallery'] ?? '');
									echo "<img src=\"../img_galeri/small_{$currentImg}\" alt=\"\">";  
								}
								else{
									echo "Belum ada foto";
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
