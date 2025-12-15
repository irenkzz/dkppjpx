<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../inc/audit_log.php';
opendb();

// Apabila user belum login
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])) {
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
    closedb();
    exit;
}

$module = isset($_GET['module']) ? $_GET['module'] : '';
$act    = isset($_GET['act']) ? $_GET['act'] : '';

function json_response($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
}

function require_admin_json()
{
    if (!isset($_SESSION['leveluser']) || $_SESSION['leveluser'] !== 'admin') {
        json_response(array('ok' => false, 'error' => 'Forbidden'), 403);
        closedb();
        exit;
    }
}

// Link preset modul (sesuaikan jika diperlukan)
function menu_preset_link($key)
{
    $map = array(
        'gallery'     => 'arsip-foto.html',
        'pengumuman'  => 'arsip-pengumuman.html',
        'agenda'      => 'arsip-agenda.html',
        'berita'      => 'kategori-2-berita.html',
        'artikel'     => 'kategori-1-artikel.html',
        'hubungi'     => 'hubungi-kami.html',
        'download'    => 'semua-download.html',
    );
    return isset($map[$key]) ? $map[$key] : '';
}

// CREATE
if ($module === 'menu' && $act === 'create') {
    require_admin_json();
    require_post_csrf();

    $nama_menu = isset($_POST['nama_menu']) ? trim($_POST['nama_menu']) : '';
    $link      = isset($_POST['link']) ? trim($_POST['link']) : '';
    $id_parent = isset($_POST['id_parent']) ? (int)$_POST['id_parent'] : 0;
    $preset    = isset($_POST['preset_modul']) ? trim($_POST['preset_modul']) : '';
    $presetLink = menu_preset_link($preset);
    if ($presetLink !== '') {
        $link = $presetLink;
    }

    $nextOrder = 1;
    $res = querydb_prepared("SELECT urutan FROM menu WHERE id_parent = ? ORDER BY urutan DESC LIMIT 1", "i", array($id_parent));
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $nextOrder = ((int)$row['urutan']) + 1;
    }

    exec_prepared(
        "INSERT INTO menu (nama_menu, link, id_parent, urutan, aktif) VALUES (?, ?, ?, ?, 'Y')",
        "ssii",
        array($nama_menu, $link, $id_parent, $nextOrder)
    );
    $newId = insert_id();
    $item = array(
        'id_menu'   => $newId,
        'nama_menu' => $nama_menu,
        'link'      => $link,
        'id_parent' => $id_parent,
        'aktif'     => 'Y',
        'urutan'    => $nextOrder
    );
    audit_event('menu', 'CREATE', 'menu', $newId, 'Menu created', null, null, array('nama' => $nama_menu, 'link' => $link, 'parent' => $id_parent));
    json_response(array('ok' => true, 'item' => $item));
    closedb();
    exit;
}

// UPDATE
elseif ($module === 'menu' && $act === 'update') {
    require_admin_json();
    require_post_csrf();

    $id_menu   = isset($_POST['id_menu']) ? (int)$_POST['id_menu'] : 0;
    $nama_menu = isset($_POST['nama_menu']) ? trim($_POST['nama_menu']) : '';
    $link      = isset($_POST['link']) ? trim($_POST['link']) : '';
    $id_parent = isset($_POST['id_parent']) ? (int)$_POST['id_parent'] : 0;
    $aktif     = (isset($_POST['aktif']) && $_POST['aktif'] === 'N') ? 'N' : 'Y';
    $preset    = isset($_POST['preset_modul']) ? trim($_POST['preset_modul']) : '';
    $presetLink = menu_preset_link($preset);
    if ($presetLink !== '') {
        $link = $presetLink;
    }

    exec_prepared(
        "UPDATE menu SET nama_menu = ?, link = ?, aktif = ?, id_parent = ? WHERE id_menu = ?",
        "sssii",
        array($nama_menu, $link, $aktif, $id_parent, $id_menu)
    );

    $item = array(
        'id_menu'   => $id_menu,
        'nama_menu' => $nama_menu,
        'link'      => $link,
        'id_parent' => $id_parent,
        'aktif'     => $aktif
    );
    audit_event('menu', 'UPDATE', 'menu', $id_menu, 'Menu updated', null, null, array('nama' => $nama_menu, 'link' => $link, 'aktif' => $aktif));
    json_response(array('ok' => true, 'item' => $item));
    closedb();
    exit;
}

// DELETE
elseif ($module === 'menu' && $act === 'delete') {
    require_admin_json();
    require_post_csrf();

    $id_menu = isset($_POST['id_menu']) ? (int)$_POST['id_menu'] : 0;
    if ($id_menu > 0) {
        // hapus subtree sederhana
        $queue = array($id_menu);
        while (!empty($queue)) {
            $current = array_shift($queue);
            $childRes = querydb_prepared("SELECT id_menu FROM menu WHERE id_parent = ?", "i", array($current));
            if ($childRes) {
                while ($c = $childRes->fetch_assoc()) {
                    $queue[] = (int)$c['id_menu'];
                }
            }
            exec_prepared("DELETE FROM menu WHERE id_menu = ?", "i", array($current));
        }
    }
    audit_event('menu', 'DELETE', 'menu', $id_menu, 'Menu deleted', null, null, array('id_menu' => $id_menu));
    json_response(array('ok' => true));
    closedb();
    exit;
}

// REORDER
elseif ($module === 'menu' && $act === 'reorder') {
    require_admin_json();
    require_post_csrf();

    $json = isset($_POST['tree']) ? $_POST['tree'] : '';
    $data = json_decode($json, true);
    if (!is_array($data)) {
        json_response(array('ok' => false, 'error' => 'Invalid payload'), 400);
        closedb();
        exit;
    }

    foreach ($data as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id        = isset($item['id']) ? (int)$item['id'] : 0;
        $parent_id = isset($item['parent_id']) ? (int)$item['parent_id'] : 0;
        $position  = isset($item['position']) ? (int)$item['position'] : 0;
        if ($id <= 0) {
            continue;
        }
        $urutan = $position + 1;
        exec_prepared(
            "UPDATE menu SET id_parent = ?, urutan = ? WHERE id_menu = ?",
            "iii",
            array($parent_id, $urutan, $id)
        );
    }

    audit_event('menu', 'REORDER', 'menu', null, 'Menu reordered', null, null, array('count' => count($data)));
    json_response(array('ok' => true));
    closedb();
    exit;
}

// TOGGLE
elseif ($module === 'menu' && $act === 'toggle') {
    require_admin_json();
    require_post_csrf();

    $id_menu = isset($_POST['id_menu']) ? (int)$_POST['id_menu'] : 0;
    $aktif   = (isset($_POST['aktif']) && $_POST['aktif'] === 'N') ? 'N' : 'Y';
    // Lock Beranda (anggap id=1 adalah beranda)
    if ($id_menu === 1 && $aktif === 'N') {
        json_response(array('ok' => false, 'error' => 'Beranda tidak boleh dimatikan'), 400);
        closedb();
        exit;
    }
    if ($id_menu > 0) {
        $check = querydb_prepared("SELECT id_menu FROM menu WHERE id_menu = ? LIMIT 1", "i", array($id_menu));
        if (!$check || $check->num_rows === 0) {
            json_response(array('ok' => false, 'error' => 'Data tidak ditemukan'), 404);
            closedb();
            exit;
        }
        exec_prepared("UPDATE menu SET aktif = ? WHERE id_menu = ?", "si", array($aktif, $id_menu));
        audit_event('menu', 'TOGGLE', 'menu', $id_menu, 'Menu toggled', null, null, array('aktif' => $aktif));
        json_response(array('ok' => true, 'aktif' => $aktif));
        closedb();
        exit;
    }
    json_response(array('ok' => false, 'error' => 'ID tidak valid'), 400);
    closedb();
    exit;
}

// Default fallback
closedb();
header("Location: ../../media.php?module=menu");
exit;
?>
