<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	require_once __DIR__ . "/../../includes/bootstrap.php";
	$aksi = "/adminweb/modul/mod_fopim/aksi_fopim.php";
	$query = "SELECT id_identitas, fopim FROM identitas LIMIT 1";
    $hasil = querydb($query);
    $r     = $hasil->fetch_array();
?>
	<section class="content-header">
		<h1>Foto Pimpinan dibawah Slider</h1>
	</section>
	
	<!-- Main content -->
	<section class="content">
		<!-- Default box -->
		<div class="box">
		<?php			
		if(isset($_GET['r'])) {
			if($_GET['r']=="sukses") {
		?>
				<div class="alert alert-success alert-dismissable">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
					<h4><i class="icon fa fa-check"></i> SUKSES!</h4>
					Data BERHASIL di SIMPAN!
				</div>
		<?php
			}
			elseif($_GET['r']=="gagal") {
		?>
				<div class="alert alert-danger alert-dismissable">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
					<h4><i class="icon fa fa-ban"></i> GAGAL!</h4>
					Data GAGAL di SIMPAN!
			</div>
		<?php
			}
		}
		?>
			<form method="POST" enctype="multipart/form-data" action="<?php echo $aksi; ?>?module=fopim" class="form-horizontal">
				<?php csrf_field(); ?>
				<input type="hidden" name="id" value="<?php echo $r['id_identitas']; ?>">
				<div class="box-body">
					<div class="form-group">
						<label for="googlemap" class="col-sm-3 control-label">Gambar Foto dibawah Slider</label>
						<div class="col-sm-9">
							<img src="../images/<?php echo $r['fopim']; ?>">
						</div>
					</div>
					<div class="form-group">
						<label for="fupload" class="col-sm-3 control-label">Ganti Foto</label>
						<div class="col-sm-9">
							<input type="hidden" class="form-control" id="fupload_hapus" name="fupload_hapus" value="<?php echo $r['fopim']; ?>" />
							<input type="file" class="form-control" id="fupload" name="fupload" required />
							<small><strong>*) Silahkan download template file Photoshopnya <a href="../images/foto_pimpinan.psd" target="_blank">disini</a><strong></small>
						</div>
					</div>
				</div><!-- /.box-body -->
				<div class="box-footer">
					<button type="submit" class="btn btn-primary">Update</button> <button type="button" onclick="self.history.back()" class="btn">Batal</button>
				</div><!-- /.box-footer -->
			</form>
		</div><!-- /.box -->
	</section><!-- /.content -->
<?php
}
?>
