<?php
// Guard login
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])) {
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
	return;
}

require_once __DIR__ . "/../../includes/bootstrap.php";

$csrfToken  = $_SESSION['csrf'] ?? '';
$isAdmin    = isset($_SESSION['leveluser']) && $_SESSION['leveluser'] === 'admin';
$albumId    = isset($_GET['album']) ? (int)$_GET['album'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$root       = realpath(__DIR__ . '/../../../');

function gallery_order_enabled()
{
    return db_column_exists('gallery', 'urutan');
}

function gallery_active_enabled()
{
    return db_column_exists('gallery', 'aktif');
}

function album_cover_url($file)
{
    global $root;
    $fallback = '/adminweb/dist/img/boxed-bg.jpg';
    $file = trim((string)$file);
    if ($file === '') return $fallback;
    $candidates = array(
        '/img_album/small_' . $file,
        '/img_album/' . $file,
        '/img_galeri/small_' . $file,
        '/img_galeri/' . $file,
    );
    foreach ($candidates as $rel) {
        if (is_file($root . $rel)) {
            return $rel;
        }
    }
    return $fallback;
}

function photo_thumb_url($file)
{
    global $root;
    $fallback = '/adminweb/dist/img/boxed-bg.jpg';
    $file = trim((string)$file);
    if ($file === '') return $fallback;
    $candidates = array(
        '/img_galeri/small_' . $file,
        '/img_galeri/' . $file,
    );
    foreach ($candidates as $rel) {
        if (is_file($root . $rel)) {
            return $rel;
        }
    }
    return $fallback;
}

$hasActive = gallery_active_enabled();
$hasUrutan = gallery_order_enabled();

// Load album options for move/select
$albumOptions = array();
$optRes = querydb("SELECT id_album, jdl_album FROM album ORDER BY id_album DESC");
while ($opt = $optRes->fetch_assoc()) {
    $albumOptions[] = array(
        'id' => (int)$opt['id_album'],
        'title' => $opt['jdl_album'],
    );
}

if ($albumId <= 0) {
    // Show chooser
    ?>
    <section class="content-header">
        <h1>Pilih Album Foto</h1>
    </section>
    <section class="content">
        <div class="box box-primary">
            <div class="box-body">
                <p>Pilih album untuk mengelola fotonya. Anda bisa menambah album di halaman Album Photo.</p>
                <div class="row">
                    <?php foreach ($albumOptions as $opt): ?>
                        <div class="col-sm-6 col-md-4">
                            <div class="box box-solid">
                                <div class="box-body">
                                    <h4><?php echo e($opt['title']); ?></h4>
                                    <a class="btn btn-primary btn-sm" href="?module=galerifoto&album=<?php echo $opt['id']; ?>"><i class="fa fa-image"></i> Kelola Foto</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
    return;
}

$albumStmt = querydb_prepared("SELECT id_album, jdl_album, album_seo, gbr_album, aktif FROM album WHERE id_album = ?", "i", array($albumId));
$album = $albumStmt ? $albumStmt->fetch_assoc() : null;

if (!$album) {
    echo "<section class='content'><div class='alert alert-danger'>Album tidak ditemukan.</div></section>";
    return;
}

$album['cover_url'] = album_cover_url($album['gbr_album']);

$photoFields = "id_gallery, id_album, jdl_gallery, keterangan, gallery_seo, gbr_gallery";
if ($hasActive) $photoFields .= ", aktif";
if ($hasUrutan) $photoFields .= ", urutan";

$photoSql = "SELECT $photoFields FROM gallery WHERE id_album = ?";
$photoSql .= $hasUrutan ? " ORDER BY urutan ASC, id_gallery DESC" : " ORDER BY id_gallery DESC";
$stmtPhoto = $dbconnection->prepare($photoSql);
$stmtPhoto->bind_param("i", $albumId);
$stmtPhoto->execute();
$photosRes = $stmtPhoto->get_result();
$photos = array();
while ($p = $photosRes->fetch_assoc()) {
    $payload = array(
        'id'          => (int)$p['id_gallery'],
        'album_id'    => (int)$p['id_album'],
        'title'       => $p['jdl_gallery'],
        'description' => $p['keterangan'],
        'slug'        => $p['gallery_seo'],
        'image'       => $p['gbr_gallery'],
        'thumb_url'   => photo_thumb_url($p['gbr_gallery']),
        'is_cover'    => ($p['gbr_gallery'] === $album['gbr_album']),
    );
    if ($hasActive && isset($p['aktif'])) $payload['aktif'] = $p['aktif'];
    if ($hasUrutan && isset($p['urutan'])) $payload['urutan'] = (int)$p['urutan'];
    $photos[] = $payload;
}
$stmtPhoto->close();
?>

<section class="content-header">
    <h1>Galeri Foto</h1>
    <ol class="breadcrumb">
        <li><a href="?module=album"><i class="fa fa-arrow-left"></i> Kembali ke Album</a></li>
    </ol>
</section>

<section class="content">
    <style>
        .album-header { display: flex; gap: 20px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
        .album-header .cover { width: 180px; height: 120px; background-size: cover; background-position: center; border: 1px solid #eee; border-radius: 4px; }
        .photo-grid .photo-card { margin-bottom: 15px; }
        .photo-thumb { width: 100%; padding-top: 75%; background-size: cover; background-position: center; border-radius: 4px; border: 1px solid #eee; position: relative; }
        .photo-actions { margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; }
        .photo-meta { margin-top: 6px; min-height: 20px; }
        .photo-cover-badge { position:absolute; top:6px; left:6px; background:rgba(0,0,0,0.6); color:#fff; padding:2px 6px; border-radius:3px; font-size:11px; }
        .photo-handle { cursor: move; position:absolute; top:6px; right:6px; color:#fff; background:rgba(0,0,0,0.35); padding:2px 4px; border-radius:3px; }
        .photo-placeholder { border:1px dashed #aaa; background:#fafafa; height:140px; margin-bottom:15px; }
    </style>

    <div class="box box-primary">
        <div class="box-body">
            <div class="album-header">
                <div class="cover" style="background-image:url(<?php echo e($album['cover_url']); ?>);"></div>
                <div>
                    <h3 style="margin-top:0;"><?php echo e($album['jdl_album']); ?></h3>
                    <p>Status: <strong><?php echo e($album['aktif'] === 'Y' ? 'Aktif' : 'Non-aktif'); ?></strong></p>
                    <p>Total foto: <span id="photoCount"><?php echo count($photos); ?></span></p>
                </div>
            </div>
            <?php if ($isAdmin): ?>
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-sm-8">
                    <form id="uploadForm" enctype="multipart/form-data" class="form-inline" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <input type="hidden" name="csrf" value="<?php echo e($csrfToken); ?>">
                        <input type="hidden" name="album_id" value="<?php echo $albumId; ?>">
                        <div class="form-group">
                            <input type="file" name="photos[]" id="uploadPhotos" class="form-control" accept="image/*" multiple required>
                        </div>
                        <?php if ($hasActive): ?>
                        <div class="form-group">
                            <div class="yn-toggle" id="uploadPhotoStatusToggle" data-name="status" data-yes="Y" data-no="N">
                                <input type="hidden" name="status" value="Y">
                                <button type="button" class="btn btn-default btn-xs yn-yes">Aktif</button>
                                <button type="button" class="btn btn-default btn-xs yn-no">Non-aktif</button>
                            </div>
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i> Unggah Foto</button>
                        <span id="uploadStatus" class="text-muted"></span>
                    </form>
                </div>
                <div class="col-sm-4 text-right">
                    <?php if ($hasUrutan): ?>
                    <button class="btn btn-success btn-sm" id="savePhotoOrder" style="display:none;"><i class="fa fa-save"></i> Simpan Urutan</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info">Mode baca: Anda tidak dapat menambah atau mengedit foto.</div>
            <?php endif; ?>

            <div class="row photo-grid" id="photoGrid"></div>
        </div>
    </div>

    <div class="modal fade" id="editPhotoModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Edit Foto</h4>
                </div>
                <form id="editPhotoForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf" value="<?php echo e($csrfToken); ?>">
                        <input type="hidden" name="id" id="editPhotoId">
                        <div class="form-group">
                            <label for="editPhotoTitle">Judul</label>
                            <input type="text" class="form-control" id="editPhotoTitle" name="title">
                        </div>
                        <div class="form-group">
                            <label for="editPhotoDesc">Keterangan</label>
                            <textarea class="form-control" id="editPhotoDesc" name="description" rows="2"></textarea>
                        </div>
                        <?php if ($hasActive): ?>
                        <div class="form-group">
                            <label for="editPhotoStatus">Status</label>
                            <div class="yn-toggle" id="editPhotoStatusToggle" data-name="status" data-yes="Y" data-no="N">
                                <input type="hidden" name="status" value="Y" id="editPhotoStatus">
                                <button type="button" class="btn btn-default btn-xs yn-yes">Aktif</button>
                                <button type="button" class="btn btn-default btn-xs yn-no">Non-aktif</button>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="editPhotoAlbum">Pindahkan ke Album</label>
                            <select class="form-control" id="editPhotoAlbum" name="album_id">
                                <?php foreach ($albumOptions as $opt): ?>
                                    <option value="<?php echo $opt['id']; ?>"><?php echo e($opt['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
(function(){
    function boot($){
    var photos = <?php echo json_encode($photos); ?>;
    var album  = <?php echo json_encode($album); ?>;
    var albumOptions = <?php echo json_encode($albumOptions); ?>;
    var csrfToken = '<?php echo e($csrfToken); ?>';
    var canManage = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    var hasOrder  = <?php echo $hasUrutan ? 'true' : 'false'; ?>;
    var hasActive = <?php echo $hasActive ? 'true' : 'false'; ?>;
    var apiGallery = '/adminweb/modul/mod_galerifoto/aksi_galerifoto.php';
    var apiAlbum   = '/adminweb/modul/mod_album/aksi_album.php';
    var orderDirty = false;

    function statusBadge(stat) {
        if (!hasActive) return '';
        var cls = stat === 'Y' ? 'label-success' : 'label-default';
        var txt = stat === 'Y' ? 'Aktif' : 'Non-aktif';
        return '<span class="label ' + cls + '">' + txt + '</span>';
    }

    function renderPhotos(list) {
        var html = '';
        $.each(list, function(_, item){
            var actions = '';
            var handle = '';
            if (canManage) {
                if (hasOrder) handle = '<span class="photo-handle" title="Geser untuk urutkan"><i class="fa fa-arrows"></i></span>';
                actions += '<button class="btn btn-default btn-xs edit-photo" data-id="' + item.id + '"><i class="fa fa-pencil"></i></button>';
                actions += ' <button class="btn btn-danger btn-xs delete-photo" data-id="' + item.id + '"><i class="fa fa-trash"></i></button>';
                actions += ' <button class="btn btn-info btn-xs set-cover" data-id="' + item.id + '"><i class="fa fa-bookmark"></i> Sampul</button>';
            }
            var coverBadge = item.is_cover ? '<span class="photo-cover-badge">Sampul</span>' : '';
            html += '<div class="col-sm-6 col-md-3 photo-card" data-id="' + item.id + '">' +
                        '<div class="photo-thumb" style="background-image:url(' + item.thumb_url + ');">' + coverBadge + handle + '</div>' +
                        '<div class="photo-meta">' + statusBadge(item.aktif) + ' ' + $('<div>').text(item.title || 'Tanpa judul').html() + '</div>' +
                        '<div class="photo-actions">' + actions + '</div>' +
                    '</div>';
        });
        $('#photoGrid').html(html);
        $('#photoCount').text(list.length);
        if (canManage && hasOrder) {
            initSortable();
        }
    }

    function initSortable() {
        if (!hasOrder || !canManage) return;
        var init = function() {
            $('#photoGrid').sortable({
                items: '.photo-card',
                handle: '.photo-handle',
                placeholder: 'photo-placeholder',
                start: function(e, ui){ ui.placeholder.height(ui.item.height()); },
                update: function(){ orderDirty = true; $('#savePhotoOrder').show(); }
            });
        };
        if ($.ui && $.ui.sortable) {
            init();
        } else {
            $.getScript('/adminweb/plugins/jQueryUI/jquery-ui.min.js')
                .done(init)
                .fail(function(){
                    $.getScript('https://code.jquery.com/ui/1.13.2/jquery-ui.min.js').done(init);
                });
        }
    }

    function findPhoto(id) {
        return photos.find(function(p){ return p.id === id; });
    }

    <?php if ($isAdmin): ?>
    $('#uploadForm').on('submit', function(e){
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'upload_photo');
        $.ajax({
            url: apiGallery + '?action=upload_photo',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(){ $('#uploadStatus').text('Mengunggah...'); },
            success: function(resp){
                $('#uploadStatus').text('');
                if (resp && resp.ok && resp.photos) {
                    photos = resp.photos.concat(photos);
                    renderPhotos(photos);
                    $('#uploadForm')[0].reset();
                    <?php if ($hasActive): ?>
                    setYnToggleValue('#uploadPhotoStatusToggle', $('#uploadPhotoStatusToggle input[name="status"]').val());
                    <?php endif; ?>
                } else {
                    alert(resp.error || 'Upload gagal.');
                }
            },
            error: function(){ $('#uploadStatus').text(''); alert('Gagal terhubung ke server.'); }
        });
    });

    $('#photoGrid').on('click', '.edit-photo', function(){
        var id = parseInt($(this).data('id'), 10);
        var p = findPhoto(id);
        if (!p) return;
        $('#editPhotoId').val(p.id);
        $('#editPhotoTitle').val(p.title);
        $('#editPhotoDesc').val(p.description);
        <?php if ($hasActive): ?>
        setYnToggleValue('#editPhotoStatusToggle', p.aktif || 'Y');
        <?php endif; ?>
        $('#editPhotoAlbum').val(p.album_id);
        $('#editPhotoModal').modal('show');
    });

    $('#editPhotoForm').on('submit', function(e){
        e.preventDefault();
        var formData = $(this).serializeArray();
        formData.push({ name: 'action', value: 'update_photo' });
        $.post(apiGallery + '?action=update_photo', formData, function(resp){
            if (resp && resp.ok && resp.photo) {
                photos = photos.map(function(p){ return p.id === resp.photo.id ? resp.photo : p; });
                renderPhotos(photos);
                $('#editPhotoModal').modal('hide');
            } else {
                alert(resp.error || 'Gagal memperbarui foto.');
            }
        }, 'json').fail(function(){
            alert('Gagal terhubung ke server.');
        });
    });

    $('#photoGrid').on('click', '.delete-photo', function(){
        var id = parseInt($(this).data('id'), 10);
        if (!confirm('Hapus foto ini?')) return;
        $.post(apiGallery + '?action=delete_photo', { csrf: csrfToken, id: id }, function(resp){
            if (resp && resp.ok) {
                photos = photos.filter(function(p){ return p.id !== id; });
                renderPhotos(photos);
            } else {
                alert(resp.error || 'Gagal menghapus foto.');
            }
        }, 'json').fail(function(){
            alert('Gagal terhubung ke server.');
        });
    });

    $('#photoGrid').on('click', '.set-cover', function(){
        var id = parseInt($(this).data('id'), 10);
        $.post(apiGallery + '?action=set_cover', { csrf: csrfToken, album_id: album.id_album, photo_id: id }, function(resp){
            if (resp && resp.ok) {
                album.gbr_album = resp.album ? resp.album.gbr_album : album.gbr_album;
                album.cover_url = resp.cover_url || album.cover_url;
                photos = photos.map(function(p){
                    p.is_cover = (p.id === id);
                    return p;
                });
                renderPhotos(photos);
                $('.album-header .cover').css('background-image', 'url(' + album.cover_url + ')');
            } else {
                alert(resp.error || 'Gagal mengatur sampul.');
            }
        }, 'json').fail(function(){
            alert('Gagal terhubung ke server.');
        });
    });

    $('#savePhotoOrder').on('click', function(){
        if (!orderDirty) return;
        var orders = [];
        $('#photoGrid .photo-card').each(function(idx){
            orders.push({ id: parseInt($(this).data('id'), 10), position: idx + 1 });
        });
        $.post(apiGallery + '?action=reorder_photo', { csrf: csrfToken, album_id: album.id_album, orders: JSON.stringify(orders) }, function(resp){
            if (resp && resp.ok) {
                orderDirty = false;
                $('#savePhotoOrder').hide();
                var orderMap = {};
                $.each(orders, function(_, o){ orderMap[o.id] = o.position; });
                photos.sort(function(a, b){
                    return (orderMap[a.id] || 0) - (orderMap[b.id] || 0);
                });
            } else {
                alert(resp.error || 'Gagal menyimpan urutan.');
            }
        }, 'json').fail(function(){
            alert('Gagal terhubung ke server.');
        });
    });
    <?php endif; ?>

    renderPhotos(photos);
    }

    if (window.jQuery) {
        boot(window.jQuery);
    } else {
        var ensure = function(){
            if (window.jQuery) {
                boot(window.jQuery);
            } else {
                setTimeout(ensure, 30);
            }
        };
        window.addEventListener('load', function(){
            if (!window.jQuery) {
                var s = document.createElement('script');
                s.src = '/adminweb/plugins/jQuery/jQuery-2.1.4.min.js';
                document.head.appendChild(s);
            }
            ensure();
        });
    }
})();
</script>
