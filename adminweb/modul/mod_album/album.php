<?php
// Guard login
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])) {
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
    return;
}

require_once __DIR__ . "/../../includes/bootstrap.php";

$csrfToken  = $_SESSION['csrf'] ?? '';
$isAdmin    = isset($_SESSION['leveluser']) && $_SESSION['leveluser'] === 'admin';
$root       = realpath(__DIR__ . '/../../../');

function album_desc_column()
{
    if (db_column_exists('album', 'deskripsi')) return 'deskripsi';
    if (db_column_exists('album', 'keterangan')) return 'keterangan';
    return null;
}

function album_order_enabled()
{
    return db_column_exists('album', 'urutan');
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

$descCol   = album_desc_column();
$hasUrutan = album_order_enabled();

$fields = "a.id_album, a.jdl_album, a.album_seo, a.gbr_album, a.aktif";
if ($descCol) $fields .= ", a.$descCol";
if ($hasUrutan) $fields .= ", a.urutan";

$sql = "
    SELECT $fields, COALESCE(g.cnt,0) AS photo_count
    FROM album a
    LEFT JOIN (SELECT id_album, COUNT(*) AS cnt FROM gallery GROUP BY id_album) g ON g.id_album = a.id_album
";
$sql .= $hasUrutan ? " ORDER BY a.urutan ASC, a.id_album DESC" : " ORDER BY a.id_album DESC";
$albums = array();

$res = querydb($sql);
while ($row = $res->fetch_assoc()) {
    $item = array(
        'id'          => (int)$row['id_album'],
        'title'       => $row['jdl_album'],
        'slug'        => $row['album_seo'],
        'aktif'       => $row['aktif'],
        'description' => $descCol ? ($row[$descCol] ?? '') : '',
        'cover'       => $row['gbr_album'],
        'cover_url'   => album_cover_url($row['gbr_album']),
        'photo_count' => (int)$row['photo_count'],
    );
    if ($hasUrutan) $item['urutan'] = (int)$row['urutan'];
    $albums[] = $item;
}
?>
<section class="content-header">
    <h1>Album Photo</h1>
    <ol class="breadcrumb">
        <?php if ($isAdmin): ?>
        <li><a class="btn btn-warning btn-sm" href="#addAlbumPanel"><i class="fa fa-plus"></i> Tambah Album Photo</a></li>
        <?php endif; ?>
    </ol>
</section>

<section class="content">
    <style>
        .album-grid .album-card { margin-bottom: 20px; }
        .album-box { position: relative; min-height: 100%; }
        .album-cover { width: 100%; padding-top: 62%; background-size: cover; background-position: center; border-radius: 4px; border: 1px solid #eee; }
        .album-title { margin-top: 10px; font-weight: 600; min-height: 44px; }
        .album-meta { margin: 8px 0; display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .album-actions .btn { margin: 2px 0; }
        .album-handle { cursor: move; position: absolute; top: 6px; right: 10px; color: #aaa; }
        .album-placeholder { border: 1px dashed #aaa; background: #fafafa; height: 120px; margin-bottom: 20px; }
        .album-status-Y { background: #00a65a; }
        .album-status-N { background: #d2d6de; color: #555; }
        .album-search-row { margin-bottom: 15px; }
        .album-cover-badge { position:absolute; top:10px; left:10px; background:rgba(0,0,0,0.6); color:#fff; padding:2px 6px; border-radius:3px; font-size:11px; }
        .album-box .label { font-size: 11px; }
    </style>

    <div class="box box-primary" id="addAlbumPanel">
        <div class="box-header with-border">
            <h3 class="box-title">Kelola Album</h3>
            <?php if ($hasUrutan && $isAdmin): ?>
            <button class="btn btn-success btn-sm pull-right" id="saveAlbumOrder" style="display:none;"><i class="fa fa-save"></i> Simpan Urutan</button>
            <?php endif; ?>
        </div>
        <div class="box-body">
            <div class="row album-search-row">
                <div class="col-sm-4">
                    <input type="text" id="albumSearch" class="form-control" placeholder="Cari judul album...">
                </div>
                <div class="col-sm-3">
                    <select id="statusFilter" class="form-control">
                        <option value="">Semua status</option>
                        <option value="Y">Aktif</option>
                        <option value="N">Non-aktif</option>
                    </select>
                </div>
            </div>
            <?php if ($isAdmin): ?>
            <div class="row">
                <div class="col-sm-12">
                    <form id="createAlbumForm" enctype="multipart/form-data" class="form-inline" style="gap:10px; display:flex; flex-wrap:wrap; align-items:flex-start;">
                        <input type="hidden" name="csrf" value="<?php echo e($csrfToken); ?>">
                        <div class="form-group" style="min-width:180px;">
                            <label class="sr-only" for="albumTitle">Judul</label>
                            <input type="text" class="form-control" id="albumTitle" name="title" placeholder="Judul album" required>
                        </div>
                        <div class="form-group" style="min-width:150px;">
                            <label class="sr-only" for="albumSlug">Slug</label>
                            <input type="text" class="form-control" id="albumSlug" name="slug" placeholder="Slug (opsional)">
                        </div>
                        <?php if ($descCol): ?>
                        <div class="form-group" style="min-width:200px;">
                            <label class="sr-only" for="albumDesc">Deskripsi</label>
                            <input type="text" class="form-control" id="albumDesc" name="description" placeholder="Deskripsi singkat">
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="sr-only" for="albumStatus">Status</label>
                            <div class="yn-toggle" id="albumStatusToggle" data-name="status" data-yes="Y" data-no="N">
                                <input type="hidden" name="status" value="Y">
                                <button type="button" class="btn btn-default btn-xs yn-yes">Aktif</button>
                                <button type="button" class="btn btn-default btn-xs yn-no">Non-aktif</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="sr-only" for="albumCover">Sampul</label>
                            <input type="file" class="form-control" id="albumCover" name="cover" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Tambah Album</button>
                        <span id="createAlbumStatus" class="text-muted" style="margin-left:10px;"></span>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row album-grid" id="albumList"></div>

    <div class="modal fade" id="editAlbumModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Edit Album</h4>
                </div>
                <form id="editAlbumForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="csrf" value="<?php echo e($csrfToken); ?>">
                        <input type="hidden" name="id" id="editAlbumId">
                        <div class="form-group">
                            <label for="editAlbumTitle">Judul</label>
                            <input type="text" class="form-control" id="editAlbumTitle" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="editAlbumSlug">Slug</label>
                            <input type="text" class="form-control" id="editAlbumSlug" name="slug">
                        </div>
                        <?php if ($descCol): ?>
                        <div class="form-group">
                            <label for="editAlbumDesc">Deskripsi</label>
                            <textarea class="form-control" id="editAlbumDesc" name="description" rows="2"></textarea>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="editAlbumStatus">Status</label>
                            <div class="yn-toggle" id="editAlbumStatusToggle" data-name="status" data-yes="Y" data-no="N">
                                <input type="hidden" name="status" value="Y" id="editAlbumStatus">
                                <button type="button" class="btn btn-default btn-xs yn-yes">Aktif</button>
                                <button type="button" class="btn btn-default btn-xs yn-no">Non-aktif</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="editAlbumCover">Ganti Sampul</label>
                            <input type="file" class="form-control" id="editAlbumCover" name="cover" accept="image/*">
                            <small class="text-muted">Kosongkan jika tidak diganti.</small>
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
    var albums = <?php echo json_encode($albums); ?>;
    var csrfToken = '<?php echo e($csrfToken); ?>';
    var canManage = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    var hasOrder  = <?php echo $hasUrutan ? 'true' : 'false'; ?>;
    var apiAlbum  = '/adminweb/modul/mod_album/aksi_album.php';
    var orderDirty = false;

    function statusLabel(status) {
        var cls = status === 'Y' ? 'label-success' : 'label-default';
        var text = status === 'Y' ? 'Aktif' : 'Non-aktif';
        return '<span class="label ' + cls + '">' + text + '</span>';
    }

    function albumCard(item) {
        var btnManage = '<a class="btn btn-primary btn-sm" href="?module=galerifoto&album=' + item.id + '"><i class="fa fa-image"></i> Kelola Foto</a>';
        var actions = btnManage;
        if (canManage) {
            actions += ' <button class="btn btn-default btn-sm edit-album" data-id="' + item.id + '"><i class="fa fa-pencil"></i> Edit</button>';
            actions += ' <button class="btn btn-danger btn-sm delete-album" data-id="' + item.id + '"><i class="fa fa-trash"></i></button>';
        }
        var handle = (canManage && hasOrder) ? '<span class="album-handle" title="Geser untuk urutkan"><i class="fa fa-ellipsis-v"></i><i class="fa fa-ellipsis-v"></i></span>' : '';
        return (
            '<div class="col-sm-6 col-md-4 album-card" data-id="' + item.id + '" data-aktif="' + item.aktif + '">' +
                '<div class="box box-solid album-box">' + handle +
                    '<div class="box-body text-center">' +
                        '<div class="album-cover" style="background-image:url(' + item.cover_url + ');"></div>' +
                        '<div class="album-meta">' +
                            statusLabel(item.aktif) +
                            '<span class="badge bg-blue">' + item.photo_count + ' foto</span>' +
                        '</div>' +
                        '<h4 class="album-title">' + $('<div>').text(item.title).html() + '</h4>' +
                        '<div class="album-actions">' + actions + '</div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
    }

    function renderAlbums(list) {
        var html = '';
        $.each(list, function(_, item){
            html += albumCard(item);
        });
        $('#albumList').html(html);
    }

    function filteredAlbums() {
        var term = $('#albumSearch').val().toLowerCase();
        var stat = $('#statusFilter').val();
        return albums.filter(function(item){
            var matchTerm = term === '' || item.title.toLowerCase().indexOf(term) !== -1;
            var matchStatus = stat === '' || item.aktif === stat;
            return matchTerm && matchStatus;
        });
    }

    function refreshView() {
        renderAlbums(filteredAlbums());
        if (canManage && hasOrder) {
            initSortable();
        }
    }

    function showMessage(el, text) {
        $(el).text(text);
        if (text) {
            setTimeout(function(){ $(el).text(''); }, 3000);
        }
    }

    $('#albumSearch, #statusFilter').on('input change', function(){
        refreshView();
    });

    <?php if ($isAdmin): ?>
    $('#createAlbumForm').on('submit', function(e){
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'create_album');
        $.ajax({
            url: apiAlbum + '?action=create_album',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp){
                if (resp && resp.ok) {
                    albums.unshift(resp.album);
                    refreshView();
                    $('#createAlbumForm')[0].reset();
                    setYnToggleValue('#albumStatusToggle', $('#albumStatusToggle input[name="status"]').val());
                    showMessage('#createAlbumStatus', 'Album ditambahkan');
                } else {
                    alert(resp.error || 'Gagal menyimpan album.');
                }
            },
            error: function(){
                alert('Gagal terhubung ke server.');
            }
        });
    });

    $('#albumList').on('click', '.edit-album', function(){
        var id = parseInt($(this).data('id'), 10);
        var found = albums.find(function(a){ return a.id === id; });
        if (!found) return;
        $('#editAlbumId').val(found.id);
        $('#editAlbumTitle').val(found.title);
        $('#editAlbumSlug').val(found.slug);
        setYnToggleValue('#editAlbumStatusToggle', found.aktif);
        <?php if ($descCol): ?>
        $('#editAlbumDesc').val(found.description || '');
        <?php endif; ?>
        $('#editAlbumCover').val('');
        $('#editAlbumModal').modal('show');
    });

    $('#editAlbumForm').on('submit', function(e){
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'update_album');
        $.ajax({
            url: apiAlbum + '?action=update_album',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp){
                if (resp && resp.ok && resp.album) {
                    albums = albums.map(function(a){ return a.id === resp.album.id ? resp.album : a; });
                    refreshView();
                    $('#editAlbumModal').modal('hide');
                } else {
                    alert(resp.error || 'Gagal memperbarui album.');
                }
            },
            error: function(){
                alert('Gagal terhubung ke server.');
            }
        });
    });

    $('#albumList').on('click', '.delete-album', function(){
        var id = parseInt($(this).data('id'), 10);
        if (!confirm('Hapus album ini? Foto harus dipindahkan terlebih dahulu.')) return;
        $.post(apiAlbum + '?action=delete_album', { csrf: csrfToken, id: id }, function(resp){
            if (resp && resp.ok) {
                albums = albums.filter(function(a){ return a.id !== id; });
                refreshView();
            } else {
                alert(resp.error || 'Album tidak dapat dihapus.');
            }
        }, 'json').fail(function(){
            alert('Gagal terhubung ke server.');
        });
    });
    <?php endif; ?>

    function initSortable() {
        if (!hasOrder || !canManage) return;
        var init = function() {
            $('#albumList').sortable({
                items: '.album-card',
                handle: '.album-handle',
                placeholder: 'album-placeholder',
                start: function(event, ui){ ui.placeholder.height(ui.item.height()); },
                update: function(){ orderDirty = true; $('#saveAlbumOrder').show(); }
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

    $('#saveAlbumOrder').on('click', function(){
        if (!orderDirty) return;
        var orders = [];
        $('#albumList .album-card').each(function(idx){
            orders.push({ id: parseInt($(this).data('id'), 10), position: idx + 1 });
        });
        $.post(apiAlbum + '?action=reorder_album', { csrf: csrfToken, orders: JSON.stringify(orders) }, function(resp){
            if (resp && resp.ok) {
                orderDirty = false;
                $('#saveAlbumOrder').hide();
            } else {
                alert(resp.error || 'Gagal menyimpan urutan.');
            }
        }, 'json').fail(function(){
            alert('Gagal terhubung ke server.');
        });
    });

    refreshView();
    }

    if (window.jQuery) {
        boot(window.jQuery);
    } else {
        // fallback: wait for footer jQuery or load it, then boot
        var ensure = function() {
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
