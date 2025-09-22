<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
  $aksi = "modul/mod_download/aksi_download.php";
  require_once __DIR__ . '/../../includes/bootstrap.php';


  // mengatasi variabel yang belum di definisikan (notice undefined index)
  $act = isset($_GET['act']) ? $_GET['act'] : '';  
?>
	<section class="content-header">
		<h1>Download</h1>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=download&act=tambahdownload"><i class="fa fa-plus"></i>Tambah Download</a></li>
        </ol>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php

  switch($act){
    // Tampil Download
    default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="datadownload" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Nama File</th>
                        <th>Tgl. Posting</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					$stmt = $dbconnection->prepare("SELECT id_download, judul, nama_file, tgl_posting FROM download ORDER BY id_download DESC");
					$stmt->execute();
					$stmt->bind_result($id_download, $judul_raw, $nama_file_raw, $tgl_posting_raw);

					$no = 1;
					while ($stmt->fetch()) {
						$id          = (int)$id_download;
						$judul       = e($judul_raw);
						$nama_file   = e($nama_file_raw);
						$tgl_posting = e(tgl_indo($tgl_posting_raw));

						echo '<tr>';
						echo '  <td class="text-center">' . $no . '</td>';
						echo '  <td>' . $judul . '</td>';
						echo '  <td>' . $nama_file . '</td>';
						echo '  <td>' . $tgl_posting . '</td>';
						echo '  <td align="center">';
						echo '    <a href="?module=download&act=editdownload&id=' . urlencode((string)$id) . '"><i class="fa fa-pencil"></i></a> &nbsp;';
						echo '    <form method="POST" action="' . $aksi . '?module=download&act=hapus" style="display:inline" onsubmit="return confirm(\'APAKAH ANDA YAKIN AKAN MENGHAPUS FILE INI ?\')">';
						csrf_field();
						echo '      <input type="hidden" name="id" value="' . $id . '">';
						echo '      <button type="submit" class="btn btn-link" title="Hapus" style="padding:0;border:none;background:transparent">';
						echo '        <i class="fa fa-trash text-red"></i>';
						echo '      </button>';
						echo '    </form>';
						echo '  </td>';
						echo '</tr>';

						$no++;
					}
					$stmt->close();
					?>
                    </tbody>
                  </table>
                </div><!-- /.box-body -->
              </div><!-- /.box -->
<?php
	break;
	
	case "tambahdownload":
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah Download</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=download&act=input" class="form-horizontal" enctype="multipart/form-data">
					<?php csrf_field(); ?>	
					<div class="box-body">
						<div class="form-group">
							<label for="judul" class="col-sm-2 control-label">Judul</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="judul" name="judul" />
							</div>
						</div>
						<div class="form-group">
							<label for="fupload" class="col-sm-2 control-label">File</label>
							<div class="col-sm-10">
								<input type="file" class="form-control" id="fupload" name="fupload" />
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
	
	case "editdownload":
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

	// prepared select
	$stmt = $dbconnection->prepare("SELECT id_download, judul, nama_file, tgl_posting FROM download WHERE id_download = ?");
	$stmt->bind_param("i", $id);
	$stmt->execute();

	// Version A: with mysqlnd
	if (method_exists($stmt, 'get_result')) {
		$res = $stmt->get_result();
		$r = $res->fetch_assoc();
	} else {
		// Version B: bind_result fallback
		$stmt->bind_result($id_download, $judul_raw, $nama_file_raw, $tgl_posting_raw);
		$r = $stmt->fetch() ? [
			'id_download' => $id_download,
			'judul' => $judul_raw,
			'nama_file' => $nama_file_raw,
			'tgl_posting' => $tgl_posting_raw,
		] : null;
	}
	$stmt->close();

	if (!$r) {
		echo '<div class="alert alert-danger">Data tidak ditemukan.</div>';
	} else {
?>
			<div class="box">
			<div class="box-header with-border">
			<h3 class="box-title">Edit Download</h3>
			</div>
			<form method="POST" action="<?php echo $aksi; ?>?module=download&act=update" class="form-horizontal" enctype="multipart/form-data">
			<?php csrf_field(); ?>
			<input type="hidden" name="id" value="<?php echo (int)$r['id_download']; ?>">
			<div class="box-body">
				<div class="form-group">
				<label for="judul" class="col-sm-2 control-label">Judul</label>
				<div class="col-sm-10">
					<input type="text" class="form-control" id="judul" name="judul" value="<?php echo e($r['judul']); ?>" />
				</div>
				</div>

				<div class="form-group">
				<label class="col-sm-2 control-label">File Saat Ini</label>
				<div class="col-sm-10">
					<p class="form-control-static"><?php echo e($r['nama_file']); ?></p>
					<input type="hidden" name="fupload_hapus" value="<?php echo e($r['nama_file']); ?>">
				</div>
				</div>

				<div class="form-group">
				<label for="fupload" class="col-sm-2 control-label">Ganti File (opsional)</label>
				<div class="col-sm-10">
					<input type="file" class="form-control" id="fupload" name="fupload" />
				</div>
				</div>
			</div>
			<div class="box-footer">
				<button type="submit" class="btn btn-primary">Simpan</button>
				<a class="btn btn-default" href="?module=download">Batal</a>
			</div>
			</form>
		</div>
<?php
	}
	break;
  }
?>
            </div><!-- /.col -->
		</div><!-- /.row -->
	</section><!-- /.section -->
<?php
}
?>