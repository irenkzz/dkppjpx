<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
    require_once __DIR__ . '/../../includes/bootstrap.php'; // path relatif dari modul
    $aksi = "/adminweb/modul/mod_halamanstatis/aksi_halamanstatis.php";

    // mengatasi variabel yang belum di definisikan (notice undefined index)
    $act  = $_GET['act'] ?? '';
    ?>
    <section class="content-header">
        <h1>Halaman Statis</h1>
        <ol class="breadcrumb">
            <li>
                <a class="btn btn-warning btn-sm"
                   href="?module=halamanstatis&amp;act=tambahhalamanstatis">
                    <i class="fa fa-plus"></i>Tambah Halaman Statis
                </a>
            </li>
        </ol>
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
<?php

    switch($act){
        // Tampil Halaman Statis
        default:
            ?>
            <div class="box">
                <div class="box-body">
                    <table id="datahalamanstatis" class="table table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Link</th>
                            <th>Tanggal Posting</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $query  = "SELECT id_halaman, judul, judul_seo, DATE(tgl_posting) AS tanggal_fix
                                   FROM halamanstatis
                                   ORDER BY id_halaman DESC";
                        $tampil = querydb($query);
                        $no = 1;
                        while ($r = $tampil->fetch_assoc()) {
                            $tgl_posting = !empty($r['tanggal_fix']) ? tgl_indo($r['tanggal_fix']) : '';
                            $id   = (int)$r['id_halaman'];
                            $jdl  = e($r['judul'] ?? '');
                            $jseo = e($r['judul_seo'] ?? '');

                            echo "<tr>
                                <td>{$no}</td>
                                <td>{$jdl}</td>
                                <td>statis-{$id}-{$jseo}.html</td>
                                <td>{$tgl_posting}</td>
                                <td align=\"center\">
                                  <a href=\"?module=halamanstatis&amp;act=edithalamanstatis&amp;id={$id}\"
                                     title=\"Edit Data\"><i class=\"fa fa-pencil\"></i></a>
                                  &nbsp;
                                  <form action=\"{$aksi}?module=halamanstatis&amp;act=hapus\"
                                        method=\"post\" style=\"display:inline\"
                                        onsubmit=\"return confirm('APAKAH ANDA YAKIN AKAN MENGHAPUS HALAMAN INI ?')\">
                                    <input type=\"hidden\" name=\"id\" value=\"{$id}\">";
                                    csrf_field();
                            echo "  <button type=\"submit\" title=\"Hapus Data\"
                                         style=\"border:none;background:none;padding:0;cursor:pointer\">
                                      <i class=\"fa fa-trash text-red\"></i>
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
                <div class="box-footer">
                    *) Link akan terisi otomatis, nanti Link tersebut di-isikan pada saat membuat Menu Website
                    untuk Halaman Statis.
                </div><!-- /.box-footer -->
            </div><!-- /.box -->
            <?php
        break;

        case "tambahhalamanstatis":
            ?>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Tambah Halaman Statis</h3>
                </div><!-- /.box-header -->
                <form method="POST"
                      action="<?php echo $aksi; ?>?module=halamanstatis&amp;act=input"
                      class="form-horizontal"
                      enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="judul" class="col-sm-2 control-label">Judul</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="judul" name="judul" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="isi_halaman" class="col-sm-2 control-label">Isi Halaman</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" id="isi_halamanstatis" name="isi_halaman"></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="fupload" class="col-sm-2 control-label">Gambar</label>
                            <div class="col-sm-10">
                                <input type="file" class="form-control" id="fupload" name="fupload" />
                                <small>- Tipe gambar harus JPG/PNG (disarankan lebar gambar 600 px).</small>
                            </div>
                        </div>
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" onclick="self.history.back()" class="btn">Batal</button>
                    </div><!-- /.box-footer -->
                </form>
            </div><!-- /.box -->
            <?php
        break;

        case "edithalamanstatis":
            // Sanitize and validate id
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                header("Location: /admin?module=halamanstatis");
                exit;
            }

            // Safe prepared SELECT
            $hasil = querydb_prepared(
                "SELECT * FROM halamanstatis WHERE id_halaman = ?",
                "i",
                [$id]
            );
            $r = $hasil ? $hasil->fetch_array() : null;
            if (!$r) {
                header("Location: /admin?module=halamanstatis");
                exit;
            }
            ?>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Halaman Statis</h3>
                </div><!-- /.box-header -->
                <form method="POST"
                      action="<?php echo $aksi; ?>?module=halamanstatis&amp;act=update"
                      class="form-horizontal"
                      enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$r['id_halaman']; ?>" />
                    <div class="box-body">
                        <div class="form-group">
                            <label for="judul" class="col-sm-2 control-label">Judul</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="judul" name="judul"
                                       value="<?php echo e($r['judul'] ?? ''); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="isi_halaman" class="col-sm-2 control-label">Isi Halaman</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" id="isi_halamanstatis" name="isi_halaman"><?php
                                    echo e($r['isi_halaman'] ?? '');
                                ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="fupload" class="col-sm-2 control-label">Gambar</label>
                            <div class="col-sm-10">
                                <?php
                                if (!empty($r['gambar'])) {
                                    echo '<img src="../foto_banner/' . e($r['gambar']) . '">';
                                } else {
                                    echo "Tidak ada gambar";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="fupload" class="col-sm-2 control-label">Ganti Gambar</label>
                            <div class="col-sm-10">
                                <input type="file" class="form-control" id="fupload" name="fupload" />
                                <small>
                                    - Apabila gambar tidak diganti, dikosongkan saja.<br>
                                    - Tipe gambar harus JPG/PNG (disarankan lebar gambar 600 px).
                                </small>
                            </div>
                        </div>
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" onclick="self.history.back()" class="btn">Batal</button>
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
