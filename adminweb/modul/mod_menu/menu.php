<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])) {
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . '/../../includes/bootstrap.php';

$aksi     = "/adminweb/modul/mod_menu/aksi_menu.php";
$isAdmin  = (isset($_SESSION['leveluser']) && $_SESSION['leveluser'] === 'admin');
$csrfTok  = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
$actParam = isset($_GET['act']) ? $_GET['act'] : '';
$editId   = isset($_GET['id']) ? (int)$_GET['id'] : 0; // untuk auto-open modal jika perlu

// Ambil daftar parent root untuk select
function menu_parent_options_root()
{
    $rows = array();
    $res  = querydb("SELECT id_menu, nama_menu FROM menu WHERE id_parent = 0 ORDER BY urutan, id_menu");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Build tree: $tree[parent_id][] = row
function menu_build_tree()
{
    $tree = array();
    $res = querydb("SELECT * FROM menu ORDER BY id_parent, urutan, id_menu");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $tree[(int)$row['id_parent']][] = $row;
        }
    }
    return $tree;
}

// Daftar halaman statis
function menu_halaman_list()
{
    $rows = array();
    $res  = querydb("SELECT id_halaman, judul, judul_seo FROM halamanstatis ORDER BY judul");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Deteksi link statis
function menu_is_statis($link)
{
    return (bool)preg_match('/^statis-(\\d+)-([a-z0-9\\-]+)\\.html$/i', (string)$link);
}

// Preset modul links (sesuaikan jika perlu)
function menu_preset_map()
{
    return array(
        'gallery'    => 'arsip-foto.html',
        'pengumuman' => 'arsip-pengumuman.html',
        'agenda'     => 'arsip-agenda.html',
        'berita'     => 'kategori-2-berita.html',
        'artikel'    => 'kategori-1-artikel.html',
        'hubungi'    => 'hubungi-kami.html',
        'download'   => 'semua-download.html',
    );
}

function menu_preset_from_link($link)
{
    $map = menu_preset_map();
    foreach ($map as $k => $v) {
        if (strcasecmp((string)$link, (string)$v) === 0) {
            return $k;
        }
    }
    return '';
}

// Render recursive tree
function render_menu_tree($tree, $parentId, $depth, $isAdmin)
{
    if (!isset($tree[$parentId])) {
        return;
    }
    echo '<ol class="menu-sortable"'.($depth === 0 ? ' id="menu-root"' : '').'>';
    foreach ($tree[$parentId] as $row) {
        $id       = (int)$row['id_menu'];
        $title    = e($row['nama_menu'] ?? '');
        $link     = e($row['link'] ?? '');
        $aktif    = ($row['aktif'] === 'Y') ? 'Y' : 'N';
        $depthLbl = $depth === 0 ? 'Root' : 'Level ' . $depth;
        $isStatis = menu_is_statis($row['link'] ?? '');
        $presetKey = menu_preset_from_link($row['link'] ?? '');
        ?>
        <li class="menu-item" data-id="<?php echo $id; ?>" data-name="<?php echo $title; ?>" data-link="<?php echo $link; ?>" data-parent="<?php echo (int)$row['id_parent']; ?>" data-aktif="<?php echo $aktif; ?>">
            <div class="menu-item-content">
                <span class="menu-title"><?php echo $title; ?></span>
                <span class="menu-link"><?php echo $link; ?><?php if ($isStatis): ?><span class="badge-statis">Statis</span><?php endif; ?><?php if ($presetKey): ?><span class="badge-statis">Modul</span><?php endif; ?></span>
                <span class="menu-depth"><?php echo $depthLbl; ?></span>
                <span class="menu-status-wrap">
                    <label class="switch-mini">
                        <input type="checkbox" class="menu-status-toggle" data-id="<?php echo $id; ?>" <?php echo $aktif === 'Y' ? 'checked' : ''; ?> <?php echo ($id === 1) ? 'disabled' : ''; ?>>
                        <span class="slider-mini"></span>
                    </label>
                </span>
                <span class="menu-actions">
                    <?php if ($isAdmin): ?>
                        <a href="#" class="btn-edit-menu" title="Edit" data-id="<?php echo $id; ?>"><i class="fa fa-pencil"></i></a>
                        &nbsp;
                        <a href="#" class="btn-delete-menu text-red" title="Hapus" data-id="<?php echo $id; ?>"><i class="fa fa-trash"></i></a>
                    <?php else: ?>
                        <i class="fa fa-lock text-muted" title="Hanya administrator"></i>
                    <?php endif; ?>
                </span>
            </div>
            <?php render_menu_tree($tree, $id, $depth + 1, $isAdmin); ?>
        </li>
        <?php
    }
    echo '</ol>';
}

$rootOptions = menu_parent_options_root();
$tree        = menu_build_tree();
$halamanList = menu_halaman_list();
$presetMap   = menu_preset_map();
?>
<section class="content-header">
    <h1>Menu Website</h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <style>
                .menu-legend, .menu-item-content {
                    display: flex;
                    align-items: center;
                }
                .menu-legend span, .menu-item-content span {
                    padding: 6px 8px;
                    border-right: 1px solid #ddd;
                }
                .menu-legend span:last-child, .menu-item-content span:last-child {
                    border-right: none;
                }
                .menu-legend {
                    background: #f7f7f7;
                    border: 1px solid #ddd;
                    font-weight: bold;
                }
                .menu-title { flex: 1 0 200px; }
                .menu-link { flex: 1 0 200px; color: #777; }
                .menu-depth { width: 100px; font-weight: bold; color: #555; }
                .menu-status-wrap { width: 60px; text-align: center; display: inline-block; }
                .menu-actions { width: 100px; text-align: center; }
                .switch-mini { position: relative; display: inline-block; width: 36px; height: 18px; vertical-align: middle; }
                .switch-mini input { display: none; }
                .slider-mini { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #d9534f; transition: .2s; border-radius: 18px; }
                .slider-mini:before { position: absolute; content: ""; height: 14px; width: 14px; left: 2px; bottom: 2px; background-color: white; transition: .2s; border-radius: 50%; }
                .switch-mini input:checked + .slider-mini { background-color: #5cb85c; }
                .switch-mini input:checked + .slider-mini:before { transform: translateX(18px); }
                .badge-statis { background:#5bc0de; color:#fff; padding:2px 6px; border-radius:10px; font-size:11px; margin-left:4px; }
                ol.menu-sortable { list-style: none; padding-left: 20px; }
                ol.menu-sortable li { margin: 4px 0; }
                .menu-item-content { border: 1px solid #ddd; background: #fff; cursor: move; user-select: none; -webkit-user-select: none; -ms-user-select: none; }
                .menu-item-content:hover { background: #f9f9f9; }
                .menu-placeholder { border: 1px dashed #999; height: 36px; margin: 4px 0; background: #fcfcfc; }
                .menu-item-content.view-only { cursor: default; }
                .inline-form-disabled { opacity: 0.6; pointer-events: none; }
                .menu-box-title { font-weight: 600; margin-bottom: 10px; }
                .divider-hr { border: 0; border-top: 1px solid #e0e0e0; margin: 10px 0 20px; }
                .menu-col-left { padding-right: 12px; border-right: 1px solid #e5e5e5; }
                .menu-col-right { padding-left: 12px; }
            </style>

            <div class="box">
                <div class="box-header with-border">
                    <p class="text-muted">
                        <?php if ($isAdmin): ?>
                            Drag &amp; drop untuk mengatur urutan/parent.
                        <?php else: ?>
                            Tampilan hanya-baca. Hanya administrator yang dapat mengubah struktur menu.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4 menu-col-left">
                            <?php if ($isAdmin): ?>
                            <form id="form-add-menu" class="form" method="post" action="#">
                                <input type="hidden" name="csrf" value="<?php echo e($csrfTok); ?>">
                                <div class="menu-box-title">Tambah Menu</div>
                                <div class="form-group">
                                    <label for="add_nama_menu">Nama Menu</label>
                                    <input type="text" class="form-control" id="add_nama_menu" name="nama_menu" placeholder="Nama Menu" required>
                                </div>
                                <div class="form-group">
                                    <label for="add_link">Link</label>
                                    <input type="text" class="form-control" id="add_link" name="link" placeholder="Link" required>
                                    <input type="hidden" id="add_preset_modul" name="preset_modul" value="">
                                    <input type="hidden" id="add_statis" value="">
                                </div>
                                <div class="form-group">
                                    <label for="add_link_source">Sumber Link</label>
                                    <select class="form-control" id="add_link_source">
                                        <option value="">Custom URL</option>
                                        <optgroup label="Preset Modul">
                                            <?php foreach ($presetMap as $key => $val): ?>
                                                <option value="<?php echo 'preset:' . e($key); ?>"><?php echo ucfirst($key); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="Halaman Statis">
                                            <?php foreach ($halamanList as $h): ?>
                                                <option value="<?php echo 'statis:' . (int)$h['id_halaman']; ?>" data-slug="<?php echo e($h['judul_seo'] ?? ''); ?>"><?php echo e($h['judul'] ?? ''); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="add_id_parent">Parent</label>
                                    <select class="form-control" id="add_id_parent" name="id_parent">
                                        <option value="0">Menu Utama</option>
                                        <?php foreach ($rootOptions as $opt): ?>
                                            <option value="<?php echo (int)$opt['id_menu']; ?>"><?php echo e($opt['nama_menu'] ?? ''); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" id="btn-add-menu" class="btn btn-primary btn-block"><i class="fa fa-plus"></i> Tambah Menu</button>
                            </form>
                            <?php else: ?>
                            <div class="alert alert-info">Anda tidak memiliki izin untuk menambah atau mengubah menu.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8 menu-col-right">
                            <div class="menu-box-title">Urut &amp; Struktur Menu</div>
                            <div class="menu-legend">
                                <span style="flex:1 0 200px;">Nama Menu</span>
                                <span style="flex:1 0 200px;">Link</span>
                                <span style="width:100px;">Level</span>
                                <span style="width:60px;">Aktif</span>
                                <span style="width:100px;">Aksi</span>
                            </div>
                            <div style="margin-top:10px;">
                                <?php render_menu_tree($tree, 0, 0, $isAdmin); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <?php if ($isAdmin): ?>
                        <button id="btn-save-order" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Urutan &amp; Struktur</button>
                    <?php else: ?>
                        <button class="btn btn-default" disabled><i class="fa fa-lock"></i> Hanya administrator yang dapat mengubah struktur menu</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal Edit -->
            <div class="modal fade" id="modalEditMenu" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="form-edit-menu">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Edit Menu</h4>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="csrf" value="<?php echo e($csrfTok); ?>">
                                <input type="hidden" name="id_menu" id="edit_id_menu">
                                <div class="form-group">
                                    <label for="edit_nama_menu">Nama Menu</label>
                                    <input type="text" class="form-control" name="nama_menu" id="edit_nama_menu" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_link">Link</label>
                                    <input type="text" class="form-control" name="link" id="edit_link" required>
                                    <input type="hidden" id="edit_preset_modul" name="preset_modul" value="">
                                    <input type="hidden" id="edit_statis" value="">
                                </div>
                                <div class="form-group">
                                    <label for="edit_link_source">Sumber Link</label>
                                    <select class="form-control" id="edit_link_source">
                                        <option value="">Custom URL</option>
                                        <optgroup label="Preset Modul">
                                            <?php foreach ($presetMap as $key => $val): ?>
                                                <option value="<?php echo 'preset:' . e($key); ?>"><?php echo ucfirst($key); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="Halaman Statis">
                                            <?php foreach ($halamanList as $h): ?>
                                                <option value="<?php echo 'statis:' . (int)$h['id_halaman']; ?>" data-slug="<?php echo e($h['judul_seo'] ?? ''); ?>"><?php echo e($h['judul'] ?? ''); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit_id_parent">Parent Menu</label>
                                    <select class="form-control" name="id_parent" id="edit_id_parent">
                                        <option value="0">Menu Utama</option>
                                        <?php foreach ($rootOptions as $opt): ?>
                                            <option value="<?php echo (int)$opt['id_menu']; ?>"><?php echo e($opt['nama_menu'] ?? ''); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Aktif</label><br>
                                    <div class="yn-toggle" id="editMenuAktifToggle" data-name="aktif" data-yes="Y" data-no="N">
                                        <input type="hidden" name="aktif" value="Y">
                                        <button type="button" class="btn btn-default btn-xs yn-yes">Ya</button>
                                        <button type="button" class="btn btn-default btn-xs yn-no">Tidak</button>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div><!-- /.col -->
    </div>
</section>

<script type="text/javascript">
(function() {
    function waitForjQuery(cb) {
        if (window.jQuery) {
            cb(window.jQuery);
            return;
        }
        setTimeout(function() { waitForjQuery(cb); }, 50);
    }

    waitForjQuery(function($) {
        var isAdmin     = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        var csrfToken   = '<?php echo e($csrfTok); ?>';
        var rootOptions = <?php echo json_encode($rootOptions); ?>;
        var halamanList = <?php echo json_encode($halamanList); ?>;
        var presetLinks = <?php echo json_encode($presetMap); ?>;

        function detectPresetFromLink(link) {
            link = String(link || '').toLowerCase();
            for (var k in presetLinks) {
                if (!presetLinks.hasOwnProperty(k)) continue;
                if (String(presetLinks[k]).toLowerCase() === link) {
                    return k;
                }
            }
            return '';
        }

        function findSlugById(id) {
            id = parseInt(id, 10) || 0;
            for (var i = 0; i < halamanList.length; i++) {
                if (parseInt(halamanList[i].id_halaman, 10) === id) {
                    return halamanList[i].judul_seo || '';
                }
            }
            return '';
        }

        function addRootOption(option) {
            var exists = false;
            for (var i = 0; i < rootOptions.length; i++) {
                if (parseInt(rootOptions[i].id_menu, 10) === parseInt(option.id_menu, 10)) {
                    exists = true;
                    break;
                }
            }
            if (!exists) {
                rootOptions.push(option);
            }
        }

        function removeRootOption(id) {
            rootOptions = $.grep(rootOptions, function(o) { return parseInt(o.id_menu, 10) !== parseInt(id, 10); });
        }

        function rebuildRootOptions($select, excludeId, selectedId) {
            $select.empty();
            $select.append('<option value="0">Menu Utama</option>');
            for (var i = 0; i < rootOptions.length; i++) {
                var opt = rootOptions[i];
                if (excludeId && parseInt(opt.id_menu, 10) === excludeId) {
                    continue;
                }
                var sel = (selectedId && parseInt(opt.id_menu, 10) === selectedId) ? ' selected' : '';
                $select.append('<option value="'+opt.id_menu+'"'+sel+'>'+opt.nama_menu+'</option>');
            }
        }

        function refreshDepthLabels() {
            function traverse($ol, depth) {
                $ol.children('li.menu-item').each(function() {
                    var $li = $(this);
                    $li.find('> .menu-item-content .menu-depth').text(depth === 0 ? 'Root' : 'Level ' + depth);
                    var $child = $li.children('ol.menu-sortable');
                    if ($child.length) {
                        traverse($child, depth + 1);
                    }
                });
            }
            traverse($('#menu-root'), 0);
        }

        function ensureChildList(parentId) {
            var $parentLi = $('li.menu-item[data-id="'+parentId+'"]');
            if (!$parentLi.length) {
                return $('#menu-root');
            }
            var $child = $parentLi.children('ol.menu-sortable');
            if (!$child.length) {
                $child = $('<ol class="menu-sortable"></ol>');
                $parentLi.append($child);
                initSortableList($child);
            }
            return $child;
        }

        function initSortableList($el) {
            if (!isAdmin) {
                $('.menu-item-content').addClass('view-only').css('cursor', 'default');
                return;
            }
            var opts = {
                connectWith: 'ol.menu-sortable',
                handle: '.menu-item-content',
                placeholder: 'menu-placeholder',
                items: '> li',
                cancel: '.menu-actions a, .menu-actions button, input, textarea, select, option',
                distance: 5,
                tolerance: 'pointer',
                forcePlaceholderSize: true,
                start: function(e, ui) { ui.placeholder.height(ui.item.outerHeight()); },
                stop: function() { refreshDepthLabels(); }
            };
            if ($el.data('ui-sortable')) {
                $el.sortable('destroy');
            }
            $el.sortable(opts).disableSelection();
        }

        function ensureSortable(callback) {
            if (!isAdmin) {
                callback();
                return;
            }
            if (typeof $.fn.sortable === 'function') {
                callback();
                return;
            }
            $.getScript('/adminweb/plugins/jQueryUI/jquery-ui.min.js').always(function() {
                if (typeof $.fn.sortable === 'function') {
                    callback();
                } else {
                    $.getScript('https://code.jquery.com/ui/1.13.2/jquery-ui.min.js').always(function() {
                        if (typeof $.fn.sortable === 'function') {
                            callback();
                        } else {
                            alert('jQuery UI tidak berhasil dimuat. Drag & drop dinonaktifkan.');
                        }
                    });
                }
            });
        }

        function setLinkDisplay($span, link) {
            $span.empty();
            $span.text(link || '');
            var presetKey = detectPresetFromLink(link);
            if (/^statis-(\d+)-([a-z0-9\-]+)\.html$/i.test(String(link || ''))) {
                $span.append('<span class="badge-statis">Statis</span>');
            }
            if (presetKey) {
                $span.append('<span class="badge-statis">Modul</span>');
            }
        }

        function createMenuItem(item) {
            var $li = $('<li class="menu-item"></li>').attr({
                'data-id': item.id_menu,
                'data-name': item.nama_menu,
                'data-link': item.link,
                'data-parent': item.id_parent,
                'data-aktif': item.aktif
            });
            var $content = $('<div class="menu-item-content"></div>');
            var $title   = $('<span class="menu-title"></span>').text(item.nama_menu);
            var $link    = $('<span class="menu-link"></span>');
            setLinkDisplay($link, item.link);
            var $depth   = $('<span class="menu-depth"></span>').text('Root');
            var $statusWrap = $('<span class="menu-status-wrap"></span>');
            var $switch  = $('<label class="switch-mini"><input type="checkbox" class="menu-status-toggle" data-id="'+item.id_menu+'"><span class="slider-mini"></span></label>');
            if (item.aktif === 'Y') {
                $switch.find('input').prop('checked', true);
            }
            if (item.id_menu == 1) {
                $switch.find('input').prop('disabled', true);
            }
            $statusWrap.append($switch);
            var $actions = $('<span class="menu-actions"></span>');
            if (isAdmin) {
                $actions.append('<a href="#" class="btn-edit-menu" title="Edit" data-id="'+item.id_menu+'"><i class="fa fa-pencil"></i></a> &nbsp;');
                $actions.append('<a href="#" class="btn-delete-menu text-red" title="Hapus" data-id="'+item.id_menu+'"><i class="fa fa-trash"></i></a>');
            } else {
                $actions.append('<i class="fa fa-lock text-muted" title="Hanya administrator"></i>');
            }

            $content.append($title, $link, $depth, $statusWrap, $actions);
            $li.append($content);
            return $li;
        }

        function appendMenuItem(item) {
            var $targetOl = (item.id_parent && item.id_parent != 0) ? ensureChildList(item.id_parent) : $('#menu-root');
            var $li = createMenuItem(item);
            $targetOl.append($li);
            initSortableList($targetOl);
            if (item.id_parent == 0) {
                addRootOption({id_menu: item.id_menu, nama_menu: item.nama_menu});
                rebuildRootOptions($('#add_id_parent'), null, null);
                rebuildRootOptions($('#edit_id_parent'), item.id_menu, 0);
            }
            refreshDepthLabels();
        }

        function serializeMenu() {
            var data = [];
            $('.menu-item').each(function() {
                var $item = $(this);
                var id = parseInt($item.data('id'), 10) || 0;
                var parentId = 0;
                var $parentLi = $item.parents('li.menu-item').first();
                if ($parentLi.length) {
                    parentId = parseInt($parentLi.data('id'), 10) || 0;
                }
                var position = $item.index();
                data.push({id: id, parent_id: parentId, position: position});
            });
            return data;
        }

        function findMenuItem(id) {
            return $('li.menu-item[data-id="'+id+'"]');
        }

        function applyLink(prefix) {
            var $source = $('#'+prefix+'_link_source');
            var $link   = $('#'+prefix+'_link');
            var $preset = $('#'+prefix+'_preset_modul');
            var $statis = $('#'+prefix+'_statis');
            var val     = $source.val() || '';
            $preset.val('');
            $statis.val('');
            $link.prop('readonly', false);
            if (val.indexOf('preset:') === 0) {
                var key = val.split(':')[1] || '';
                if (presetLinks[key]) {
                    $preset.val(key);
                    $link.val(presetLinks[key]).prop('readonly', true);
                }
            } else if (val.indexOf('statis:') === 0) {
                var statisId = val.split(':')[1] || '';
                var slug = findSlugById(statisId);
                if (slug) {
                    $statis.val(statisId);
                    $link.val('statis-' + statisId + '-' + slug + '.html').prop('readonly', true);
                }
            }
        }

        function updateMenuItemDom(item) {
            var $li = findMenuItem(item.id_menu);
            if (!$li.length) {
                appendMenuItem(item);
                return;
            }
            var oldParent = parseInt($li.data('parent'), 10) || 0;
            $li.attr('data-name', item.nama_menu)
               .attr('data-link', item.link)
               .attr('data-parent', item.id_parent)
               .attr('data-aktif', item.aktif);
            $li.find('> .menu-item-content .menu-title').text(item.nama_menu);
            setLinkDisplay($li.find('> .menu-item-content .menu-link'), item.link);
            var $toggle = $li.find('.menu-status-toggle');
            $toggle.prop('checked', item.aktif === 'Y');
            if (item.id_menu == 1) {
                $toggle.prop('disabled', true);
            }

            var currentParent = 0;
            var $parentLi = $li.parents('li.menu-item').first();
            if ($parentLi.length) {
                currentParent = parseInt($parentLi.data('id'), 10) || 0;
            }
            if (currentParent !== item.id_parent) {
                $li.detach();
                var $targetOl = (item.id_parent && item.id_parent != 0) ? ensureChildList(item.id_parent) : $('#menu-root');
                $targetOl.append($li);
                initSortableList($targetOl);
            }

            if (oldParent !== 0 && item.id_parent === 0) {
                addRootOption({id_menu: item.id_menu, nama_menu: item.nama_menu});
            } else if (oldParent === 0 && item.id_parent !== 0) {
                removeRootOption(item.id_menu);
            }
            rebuildRootOptions($('#add_id_parent'), null, null);
            rebuildRootOptions($('#edit_id_parent'), item.id_menu, item.id_parent);
            refreshDepthLabels();
        }

        function resetAddForm() {
            $('#add_nama_menu').val('');
            $('#add_link').val('').prop('readonly', false);
            $('#add_id_parent').val('0');
            $('#add_preset_modul').val('');
            $('#add_statis').val('');
            $('#add_link_source').val('');
        }

        $(function() {
            ensureSortable(function() {
                $('ol.menu-sortable').each(function() {
                    initSortableList($(this));
                });
                refreshDepthLabels();
            });

            $('#add_link_source').on('change', function() { applyLink('add'); });
            $('#edit_link_source').on('change', function() { applyLink('edit'); });

            $('#btn-add-menu').on('click', function(e) {
                e.preventDefault();
                if (!isAdmin) return;
                applyLink('add');
                var nama   = $('#add_nama_menu').val();
                var link   = $('#add_link').val();
                var parent = parseInt($('#add_id_parent').val(), 10) || 0;
                var preset = $('#add_preset_modul').val();

                if (!nama || !link) {
                    alert('Nama menu dan link wajib diisi.');
                    return;
                }

                $.post('/adminweb/modul/mod_menu/aksi_menu.php?module=menu&act=create', {
                    csrf: csrfToken,
                    nama_menu: nama,
                    link: link,
                    preset_modul: preset,
                    id_parent: parent
                }, function(resp) {
                    if (resp && resp.ok && resp.item) {
                        appendMenuItem(resp.item);
                        resetAddForm();
                        alert('Menu berhasil ditambahkan.');
                    } else {
                        alert('Gagal menambah menu.');
                    }
                }, 'json').fail(function() {
                    alert('Gagal menambah menu.');
                });
            });

            $('#btn-save-order').on('click', function(e) {
                e.preventDefault();
                if (!isAdmin) return;
                var payload = serializeMenu();
                $.post('/adminweb/modul/mod_menu/aksi_menu.php?module=menu&act=reorder', {
                    csrf: csrfToken,
                    tree: JSON.stringify(payload)
                }, function(resp) {
                    if (resp && resp.ok) {
                        alert('Berhasil menyimpan urutan & struktur.');
                    } else {
                        alert('Gagal menyimpan: ' + (resp && resp.error ? resp.error : 'unknown'));
                    }
                }, 'json').fail(function() {
                    alert('Gagal menyimpan perubahan.');
                });
            });

            $(document).on('click', '.btn-edit-menu', function(e) {
                e.preventDefault();
                if (!isAdmin) return;
                var id = parseInt($(this).data('id'), 10) || 0;
                var $li = findMenuItem(id);
                if (!$li.length) return;

                var name   = $li.data('name');
                var link   = $li.data('link');
                var parent = parseInt($li.data('parent'), 10) || 0;
                var aktif  = $li.data('aktif') === 'N' ? 'N' : 'Y';
                var presetKey = detectPresetFromLink(link);
                var statisMatch = /^statis-(\d+)-([a-z0-9\-]+)\.html$/i.exec(String(link || ''));

                $('#edit_id_menu').val(id);
                $('#edit_nama_menu').val(name);
                $('#edit_link').val(link);
                $('#edit_preset_modul').val(presetKey);
                $('#edit_statis').val(statisMatch ? statisMatch[1] : '');

                var sourceVal = '';
                if (presetKey) {
                    sourceVal = 'preset:' + presetKey;
                } else if (statisMatch) {
                    sourceVal = 'statis:' + statisMatch[1];
                }
                $('#edit_link_source').val(sourceVal);
                applyLink('edit');
                if (!presetKey && !statisMatch) {
                    $('#edit_link').val(link);
                }

                rebuildRootOptions($('#edit_id_parent'), id, parent);
                $('#edit_id_parent').val(parent);
                setYnToggleValue('#editMenuAktifToggle', aktif);
                $('#modalEditMenu').modal('show');
            });

            $('#form-edit-menu').on('submit', function(e) {
                e.preventDefault();
                if (!isAdmin) return;
                applyLink('edit');
                var id     = parseInt($('#edit_id_menu').val(), 10) || 0;
                var nama   = $('#edit_nama_menu').val();
                var link   = $('#edit_link').val();
                var parent = parseInt($('#edit_id_parent').val(), 10) || 0;
                var aktif  = $('#editMenuAktifToggle input[name="aktif"]').val();
                var preset = $('#edit_preset_modul').val();

                $.post('/adminweb/modul/mod_menu/aksi_menu.php?module=menu&act=update', {
                    csrf: csrfToken,
                    id_menu: id,
                    nama_menu: nama,
                    link: link,
                    preset_modul: preset,
                    id_parent: parent,
                    aktif: aktif
                }, function(resp) {
                    if (resp && resp.ok && resp.item) {
                        updateMenuItemDom(resp.item);
                        $('#modalEditMenu').modal('hide');
                        alert('Perubahan disimpan.');
                    } else {
                        alert('Gagal menyimpan perubahan.');
                    }
                }, 'json').fail(function() {
                    alert('Gagal menyimpan perubahan.');
                });
            });

            $(document).on('click', '.btn-delete-menu', function(e) {
                e.preventDefault();
                if (!isAdmin) return;
                if (!confirm('Hapus menu ini beserta sub-menunya?')) return;
                var id = parseInt($(this).data('id'), 10) || 0;
                $.post('/adminweb/modul/mod_menu/aksi_menu.php?module=menu&act=delete', {
                    csrf: csrfToken,
                    id_menu: id
                }, function(resp) {
                    if (resp && resp.ok) {
                        var $li = findMenuItem(id);
                        if ($li.length) {
                            var parentId = parseInt($li.data('parent'), 10) || 0;
                            if (parentId === 0) {
                                removeRootOption(id);
                                rebuildRootOptions($('#add_id_parent'), null, null);
                                rebuildRootOptions($('#edit_id_parent'), null, null);
                            }
                            $li.remove();
                            refreshDepthLabels();
                        }
                        alert('Menu dihapus.');
                    } else {
                        alert('Gagal menghapus menu.');
                    }
                }, 'json').fail(function() {
                    alert('Gagal menghapus menu.');
                });
            });

            $(document).on('change', '.menu-status-toggle', function() {
                if (!isAdmin) return;
                var $input = $(this);
                var $li = $input.closest('li.menu-item');
                var id = parseInt($input.data('id'), 10) || 0;
                if (id === 1) {
                    $input.prop('checked', true);
                    alert('Beranda tidak boleh dimatikan.');
                    return;
                }
                var newAktif = $input.is(':checked') ? 'Y' : 'N';
                if (newAktif === 'N') {
                    var hasChild = $li.children('ol.menu-sortable').children('li.menu-item').length > 0;
                    if (hasChild && !confirm('Menu ini memiliki submenu. Nonaktifkan semua?')) {
                        $input.prop('checked', true);
                        return;
                    }
                }
                $.post('/adminweb/modul/mod_menu/aksi_menu.php?module=menu&act=toggle', {
                    csrf: csrfToken,
                    id_menu: id,
                    aktif: newAktif
                }, function(resp) {
                    if (resp && resp.ok) {
                        var val = resp.aktif === 'N' ? 'N' : 'Y';
                        $li.attr('data-aktif', val);
                        $input.prop('checked', val === 'Y');
                    } else {
                        alert('Gagal mengubah status: ' + (resp && resp.error ? resp.error : 'unknown'));
                        $input.prop('checked', !$input.is(':checked'));
                    }
                }, 'json').fail(function() {
                    alert('Gagal mengubah status.');
                    $input.prop('checked', !$input.is(':checked'));
                });
            });

            refreshDepthLabels();
            if (isAdmin && '<?php echo $actParam; ?>' === 'editmenu' && <?php echo $editId; ?> > 0) {
                var $btn = $('.btn-edit-menu[data-id="<?php echo $editId; ?>"]');
                if ($btn.length) {
                    $btn.trigger('click');
                }
            }

            $('#modalEditMenu').on('shown.bs.modal', function() {
                $('#edit_nama_menu').focus();
            });
            $('#modalEditMenu').on('hidden.bs.modal', function() {
                $(this).find(':focus').blur();
                $('#btn-add-menu').focus();
            });
        });
    });
})();
</script>
