<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	$aksi = "modul/mod_user/aksi_user.php";

	// mengatasi variabel yang belum di definisikan (notice undefined index)
	$act = isset($_GET['act']) ? $_GET['act'] : ''; 
?>
	<section class="content-header">
		<h1>Manajemen User</h1>
<?php
		if ($_SESSION['leveluser']=='admin'){
			$query  = "SELECT * FROM users ORDER BY username";
			$tampil = querydb($query);
?>
		<ol class="breadcrumb">
            <li><a class="btn btn-warning btn-sm" href="?module=user&act=tambahuser"><i class="fa fa-plus"></i>Tambah User</a></li>
        </ol>
<?php
		}
		else{
			$query  = "SELECT * FROM users WHERE username='$_SESSION[namauser]'";
			$tampil = querydb($query);
		}
?>
	</section>
	<!-- Main content -->
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
<?php
	switch($act){
		// Tampil Modul
		default:
?>
              <div class="box">
                <div class="box-body">
                  <table id="datauser" class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Level</th>
                        <th>Blokir</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
					<?php
					$no = 1;
      while ($r=$tampil->fetch_array()){
        echo "<tr>
             <td>$no</td>
             <td>$r[username]</td>
             <td>$r[nama_lengkap]</td>
		         <td><a href=\"mailto:$r[email]\">$r[email]</a></td>
		         <td>$r[level]</td>
		         <td align=\"center\">$r[blokir]</td>
				 <td align=\"center\"><a href=\"?module=user&act=edituser&id=$r[id_session]\"><i class=\"fa fa-pencil\"></i></a></td>
             </tr>";
        $no++;
      }
					?>
                    </tbody>
                  </table>
                </div><!-- /.box-body -->
				<div class="box-footer">
					<i>*) Data pada User tidak bisa dihapus, tapi bisa di blokir melalui Edit User.</i>
                </div><!-- /.box-footer -->
              </div><!-- /.box -->
<?php
		break;
		
		case "tambahuser":
			if ($_SESSION['leveluser']=='admin'){
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Tambah User</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=user&act=input" class="form-horizontal">
					<?php csrf_field(); ?>
					<div class="box-body">
						<div class="form-group">
							<label for="username" class="col-sm-3 control-label">Username</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="username" name="username" />
							</div>
						</div>
						<div class="form-group">
							<label for="password" class="col-sm-3 control-label">Password</label>
							<div class="col-sm-6">
								<input type="password" class="form-control" id="password" name="password" />
							</div>
						</div>
						<div class="form-group">
							<label for="nama_lengkap" class="col-sm-3 control-label">Nama Lengkap</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" />
							</div>
						</div>
						<div class="form-group">
							<label for="email" class="col-sm-3 control-label">Email</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="email" name="email" />
							</div>
						</div>
					</div><!-- /.box-body -->
					<div class="box-footer">
						<button type="submit" class="btn btn-primary">Simpan</button> <button type="button" onclick="self.history.back()" class="btn">Batal</button>
					</div><!-- /.box-footer -->
				</form>
              </div><!-- /.box -->
<?php
		}
		else{
			echo "<p>Anda tidak berhak mengakses halaman ini.</p>";
		}
		break;
		
		case "edituser":
			$query = "SELECT * FROM users WHERE id_session='$_GET[id]'";
			$hasil = querydb($query);
			$r     = $hasil->fetch_array();
?>
			<div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">Edit User</h3>
                </div><!-- /.box-header -->
                <form method="POST" action="<?php echo $aksi; ?>?module=user&act=update" class="form-horizontal">
					
					<?php
					if ($_SESSION['leveluser']=='admin'){
						csrf_field();
					?>
					<input type="hidden" name="id" value="<?php echo $r['id_session']; ?>">
					<div class="box-body">
						<div class="form-group">
							<label for="username" class="col-sm-3 control-label">Username</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="username" name="username" value="<?php echo $r['username']; ?>" disabled="disabled" />
								<small>*) Username tidak bisa diubah.</small>
							</div>
						</div>
						<div class="form-group">
							<label for="password" class="col-sm-3 control-label">Password</label>
							<div class="col-sm-6">
								<input type="password" class="form-control" id="password" name="password" />
								<small>*) Apabila password tidak diubah, dikosongkan saja.</small>
							</div>
						</div>
						<div class="form-group">
							<label for="nama_lengkap" class="col-sm-3 control-label">Nama Lengkap</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo $r['nama_lengkap']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="email" class="col-sm-3 control-label">Email</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="email" name="email" value="<?php echo $r['email']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="blokir" class="col-sm-3 control-label">Blokir</label>
							<div class="col-sm-6">
								<?php
								if($r['blokir']=="Y") {
								?>
									<label><input type="radio" class="minimal" id="blokir" name="blokir" value="Y" checked> Y &nbsp; </label>
									<label><input type="radio" class="minimal" id="blokir" name="blokir" value="N"> N </label>
								<?php
								}
								elseif($r['blokir']=="N") {
								?>
									<label><input type="radio" class="minimal" id="blokir" name="blokir" value="Y"> Y &nbsp; </label>
									<label><input type="radio" class="minimal" id="blokir" name="blokir" value="N" checked> N </label>
								<?php
								}
								?>
							</div>
						</div>
					</div><!-- /.box-body -->
					<?php
					}
					else {
						csrf_field();
					?>
					<input type="hidden" name="id" value="<?php echo $r['id_session']; ?>">
					<input type="hidden" name="blokir" value="<?php echo $r['blokir']; ?>">
					<div class="box-body">
						<div class="form-group">
							<label for="username" class="col-sm-3 control-label">Username</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="username" name="username" value="<?php echo $r['username']; ?>" disabled="disabled" />
								<small>*) Username tidak bisa diubah.</small>
							</div>
						</div>
						<div class="form-group">
							<label for="password" class="col-sm-3 control-label">Password</label>
							<div class="col-sm-6">
								<input type="password" class="form-control" id="password" name="password" />
								<small>*) Apabila password tidak diubah, dikosongkan saja.</small>
							</div>
						</div>
						<div class="form-group">
							<label for="nama_lengkap" class="col-sm-3 control-label">Nama Lengkap</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo $r['nama_lengkap']; ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="email" class="col-sm-3 control-label">Email</label>
							<div class="col-sm-6">
								<input type="text" class="form-control" id="email" name="email" value="<?php echo $r['email']; ?>" />
							</div>
						</div>
					</div><!-- /.box-body -->
					<?php
					}
					?>
					<div class="box-footer">
						<button type="submit" class="btn btn-primary">Simpan</button> <button type="button" onclick="self.history.back()" class="btn">Batal</button>
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