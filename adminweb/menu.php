<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

// Helpers keep sidebar state logic centralized and suppress undefined index notices.
function is_admin()
{
    return isset($_SESSION['leveluser']) && $_SESSION['leveluser'] === 'admin';
}

function normalize_menu_module($module)
{
    $module = strtolower(trim((string) $module));
    $aliases = array(
        'beranda'       => 'beranda',
        'identitas'     => 'identitas',
        'logo'          => 'logo',
        'fopim'         => 'fopim',
        'user'          => 'user',
        'menu'          => 'menu',
        'listslider'    => 'listslider',
        'slider'        => 'slider',
        'auditlog'      => 'auditlog',
        'modul'         => 'modul',
        'berita'        => 'berita',
        'kategori'      => 'kategori',
        'tag'           => 'tag',
        'halamanstatis' => 'halamanstatis',
        'album'         => 'album',
        'galerifoto'    => 'galerifoto',
        'sekilasinfo'   => 'sekilasinfo',
        'download'      => 'download',
        'agenda'        => 'agenda',
        'pengumuman'    => 'pengumuman',
        'polling'       => 'polling',
        'hubungi'       => 'hubungi',
        'banner'        => 'banner',
    );

    return isset($aliases[$module]) ? $aliases[$module] : $module;
}

function menu_is_active($current, $keys)
{
    $current = normalize_menu_module($current);

    foreach ((array) $keys as $key) {
        if ($current === normalize_menu_module($key)) {
            return 'active';
        }
    }

    return '';
}

function menu_item_visible(array $item)
{
    return empty($item['admin_only']) || is_admin();
}

$currentModule = normalize_menu_module(isset($_GET['module']) ? $_GET['module'] : '');
$fullName = isset($_SESSION['namalengkap']) ? $_SESSION['namalengkap'] : '';

// Declarative menu definition keeps markup stable while reducing inline conditionals.
$MENU = array(
    array('type' => 'header', 'label' => 'MENU'),
    array(
        'type'   => 'link',
        'module' => 'beranda',
        'icon'   => 'fa fa-dashboard',
        'label'  => 'Beranda',
        'href'   => '?module=beranda',
        'title'  => 'beranda',
    ),
    array(
        'type'        => 'tree',
        'label'       => 'Menu Utama',
        'icon'        => 'fa fa-gear',
        'active_keys' => array('identitas', 'modul', 'user', 'slider', 'menu', 'logo', 'fopim', 'listslider', 'auditlog'),
        'items'       => array(
            array('module' => 'identitas', 'label' => 'Identitas Web', 'href' => '?module=identitas', 'admin_only' => true),
            array('module' => 'logo', 'label' => 'Logo Header', 'href' => '?module=logo', 'admin_only' => true),
            array('module' => 'fopim', 'label' => 'Foto Pimpinan', 'href' => '?module=fopim', 'admin_only' => true),
            array('module' => 'user', 'label' => 'Manajemen User', 'href' => '?module=user'),
            array('module' => 'menu', 'label' => 'Manajemen Menu', 'href' => '?module=menu', 'admin_only' => true),
            array('module' => 'listslider', 'label' => 'List Slider', 'href' => '?module=listslider', 'admin_only' => true),
            array('module' => 'slider', 'label' => 'Slider Beranda', 'href' => '?module=slider', 'admin_only' => true),
            array('module' => 'auditlog', 'label' => 'Audit Log', 'href' => '?module=auditlog', 'admin_only' => true),
        ),
    ),
    array(
        'type'        => 'tree',
        'label'       => 'Modul Berita',
        'icon'        => 'fa fa-edit',
        'active_keys' => array('berita', 'kategori', 'tag'),
        'items'       => array(
            array('module' => 'berita', 'label' => 'Berita', 'href' => '?module=berita'),
            array('module' => 'kategori', 'label' => 'Kategori', 'href' => '?module=kategori'),
            array('module' => 'tag', 'label' => 'Tag', 'href' => '?module=tag'),
        ),
    ),
    array(
        'type'       => 'link',
        'module'     => 'halamanstatis',
        'icon'       => 'fa fa-tag',
        'label'      => 'Halaman Statis',
        'href'       => '?module=halamanstatis',
        'title'      => 'Halaman Statis',
        'admin_only' => true,
    ),
    array(
        'type'        => 'tree',
        'label'       => 'Media',
        'icon'        => 'fa fa-edit',
        'active_keys' => array('album', 'galerifoto', 'sekilasinfo'),
        'items'       => array(
            array('module' => 'album', 'label' => 'Album Photo', 'href' => '?module=album'),
            array('module' => 'galerifoto', 'label' => 'Galeri Photo', 'href' => '?module=galerifoto'),
            array('module' => 'sekilasinfo', 'label' => 'Sekilas Info', 'href' => '?module=sekilasinfo', 'admin_only' => true),
        ),
    ),
    array(
        'type'   => 'link',
        'module' => 'download',
        'icon'   => 'fa fa-download',
        'label'  => 'Download',
        'href'   => '?module=download',
        'title'  => 'Download',
        'admin_only' => true,
    ),
    array(
        'type'        => 'tree',
        'label'       => 'Interaksi',
        'icon'        => 'fa fa-edit',
        'active_keys' => array('agenda', 'pengumuman', 'polling', 'hubungi'),
        'items'       => array(
            array('module' => 'agenda', 'label' => 'Agenda', 'href' => '?module=agenda'),
            array('module' => 'pengumuman', 'label' => 'Pengumuman', 'href' => '?module=pengumuman'),
            array('module' => 'polling', 'label' => 'Polling', 'href' => '?module=polling', 'admin_only' => true),
            array('module' => 'hubungi', 'label' => 'Hubungi Kami', 'href' => '?module=hubungi'),
        ),
    ),
    array(
        'type'   => 'link',
        'module' => 'banner',
        'icon'   => 'fa fa-tag',
        'label'  => 'Banner',
        'href'   => '?module=banner',
        'title'  => 'Banner',
        'admin_only' => true,
    ),
    array(
        'type'  => 'link',
        'module'=> null,
        'icon'  => 'fa fa-sign-out',
        'label' => 'Keluar',
        'href'  => '/adminweb/logout.php',
        'title' => 'Keluar',
    ),
);
?>
<!-- Left side column. contains the sidebar -->
<aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
        <div class="user-panel">
            <div class="pull-left image">
                <img src="/adminweb/dist/img/user.png" class="img-responsive">
            </div>
            <div class="pull-left info">
                <p><?php echo e($fullName); ?></p>
                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
        </div>
        <!-- sidebar menu: : style can be found in sidebar.less -->
        <ul class="sidebar-menu">
<?php foreach ($MENU as $section): ?>
<?php
    if ($section['type'] === 'header') {
        ?>
            <li class="header"><?php echo $section['label']; ?></li>
<?php
        continue;
    }

    if ($section['type'] === 'link') {
        if (!menu_item_visible($section)) {
            continue;
        }

        $activeClass = '';
        if (isset($section['module']) && $section['module'] !== '' && $section['module'] !== null) {
            $activeClass = menu_is_active($currentModule, $section['module']);
        }
        ?>
            <li<?php echo $activeClass ? ' class="' . $activeClass . '"' : ''; ?>><a href="<?php echo $section['href']; ?>" title="<?php echo $section['title']; ?>"><i class="<?php echo $section['icon']; ?>"></i> <span><?php echo $section['label']; ?></span></a></li>
<?php
        continue;
    }

    if ($section['type'] === 'tree') {
        $visibleChildren = array();
        foreach ($section['items'] as $child) {
            if (menu_item_visible($child)) {
                $visibleChildren[] = $child;
            }
        }

        if (count($visibleChildren) === 0) {
            continue;
        }

        $treeActive = menu_is_active($currentModule, $section['active_keys']);
        ?>
            <li class="treeview<?php echo $treeActive ? ' ' . $treeActive : ''; ?>">
                <a href="#">
                    <i class="<?php echo $section['icon']; ?>"></i>
                    <span><b><?php echo $section['label']; ?></b></span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
<?php foreach ($visibleChildren as $child): ?>
<?php $childActive = menu_is_active($currentModule, $child['module']); ?>
                    <li<?php echo $childActive ? ' class="' . $childActive . '"' : ''; ?>><a href="<?php echo $child['href']; ?>"><i class="fa fa-circle-o"></i> <span><?php echo $child['label']; ?></span></a></li>
<?php endforeach; ?>
                </ul>
            </li>
<?php
    }
?>
<?php endforeach; ?>
        </ul>
    </section>
    <!-- /.sidebar -->
</aside>
