<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])) {
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else {
    if (!isset($_SESSION['leveluser']) || $_SESSION['leveluser'] !== 'admin') {
        echo "<script>alert('Anda tidak memiliki izin untuk mengakses modul ini.'); window.location = '/admin';</script>";
        exit;
    }

    $aksi = "/adminweb/modul/mod_banner/aksi_banner.php";

    // mengatasi variabel yang belum di definisikan (notice undefined index)
    $act = isset($_GET['act']) ? $_GET['act'] : '';
    ?>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
<?php

    switch ($act) {
        // Tampil Banner
        default:
            ?>
                <div class="box">
                    <div class="box-body">
                        <table id="databanner" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Link</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $query  = "SELECT * FROM banner ORDER BY id_banner DESC";
                            $tampil = querydb($query);
                            $no     = 1;
                            while ($r = $tampil->fetch_array()) {
                                $judul = e($r['judul'] ?? '');
                                $link  = e($r['link'] ?? '');
                                $id    = (int)($r['id_banner'] ?? 0);
                                ?>
                                <tr>
                                    <td><?php echo $no; ?></td>
                                    <td><?php echo $judul; ?></td>
                                    <td>
                                        <?php if ($link !== ''): ?>
                                            <a href="<?php echo $link; ?>" target="_blank">
                                                <?php echo $link; ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="?module=banner&amp;act=editbanner&amp;id=<?php echo $id; ?>"
                                           title="Ganti Banner">
                                            <i class="fa fa-pencil"></i>
                                        </a>
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

        case "editbanner":
            // Sanitize & validate id
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                header("Location: /admin?module=banner");
                exit;
            }

            // Use prepared SELECT
            $hasil = querydb_prepared(
                "SELECT * FROM banner WHERE id_banner = ?",
                "i",
                [$id]
            );
            $r = $hasil->fetch_array();
            if (!$r) {
                header("Location: /admin?module=banner");
                exit;
            }
            ?>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit Banner</h3>
                    </div><!-- /.box-header -->
                    <form method="POST"
                          action="<?php echo $aksi; ?>?module=banner&amp;act=update"
                          class="form-horizontal"
                          enctype="multipart/form-data">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$r['id_banner']; ?>" />
                        <div class="box-body">
                            <div class="form-group">
                                <label for="judul" class="col-sm-2 control-label">Judul</label>
                                <div class="col-sm-10">
                                    <input type="text"
                                           class="form-control"
                                           id="judul"
                                           name="judul"
                                           value="<?php echo e($r['judul'] ?? ''); ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="link" class="col-sm-2 control-label">Link</label>
                                <div class="col-sm-10">
                                    <input type="text"
                                           class="form-control"
                                           id="link"
                                           name="link"
                                           value="<?php echo e($r['link'] ?? ''); ?>" />
                                    <small>* Contoh : <b>https://jayapurakota.go.id/</b></small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="fupload" class="col-sm-2 control-label">Gambar</label>
                                <div class="col-sm-10">
                                    <?php
                                    if (!empty($r['gambar'])) {
                                        $gambar = e($r['gambar']);
                                        echo '<img src="../foto_banner/' . $gambar . '" alt="Banner">';
                                    } else {
                                        echo "Belum ada gambar";
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="fupload" class="col-sm-2 control-label">Ganti Gambar</label>
                                <div class="col-sm-10">
                                    <input type="file" class="form-control" id="fupload" name="fupload" />
                                    <small>Ukuran gambar banner yang di upload adalah 367x325 px.</small>
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
