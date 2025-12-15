<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
include "../../../config/fungsi_seo.php";
require_once __DIR__ . '/../../includes/upload_helpers.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$module = isset($_GET['module']) ? $_GET['module'] : '';
$act    = isset($_GET['act']) ? $_GET['act'] : '';
$root   = realpath(__DIR__ . '/../../../');

function json_response($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

function require_admin_json()
{
    if (!isset($_SESSION['leveluser']) || $_SESSION['leveluser'] !== 'admin') {
        json_response(array('ok' => false, 'error' => 'Forbidden'), 403);
    }
}

function gallery_order_enabled()
{
    return db_column_exists('gallery', 'urutan');
}

function gallery_active_enabled()
{
    return db_column_exists('gallery', 'aktif');
}

function reshape_files_array($files)
{
    $normalized = array();
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $normalized;
    }
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $normalized[] = array(
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        );
    }
    return $normalized;
}

function photo_thumb_url($file)
{
    global $root;
    $file = trim((string)$file);
    $fallback = '/adminweb/dist/img/boxed-bg.jpg';
    if ($file === '') {
        return $fallback;
    }
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

function photo_payload($row, $albumCover, $hasActive, $hasUrutan)
{
    $payload = array(
        'id'          => (int)$row['id_gallery'],
        'album_id'    => (int)$row['id_album'],
        'title'       => $row['jdl_gallery'],
        'description' => $row['keterangan'],
        'slug'        => $row['gallery_seo'],
        'image'       => $row['gbr_gallery'],
        'thumb_url'   => photo_thumb_url($row['gbr_gallery']),
        'is_cover'    => ($row['gbr_gallery'] === $albumCover),
    );
    if ($hasActive && isset($row['aktif'])) {
        $payload['aktif'] = $row['aktif'];
    }
    if ($hasUrutan && isset($row['urutan'])) {
        $payload['urutan'] = (int)$row['urutan'];
    }
    return $payload;
}

function fetch_album_info($id)
{
    $res = querydb_prepared("SELECT id_album, jdl_album, album_seo, gbr_album, aktif FROM album WHERE id_album = ?", "i", array($id));
    return $res ? $res->fetch_assoc() : null;
}

// JSON endpoints
if ($action !== '') {
    if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])) {
        json_response(array('ok' => false, 'error' => 'Unauthorized'), 401);
    }
    opendb();

    $hasActive = gallery_active_enabled();
    $hasUrutan = gallery_order_enabled();

    // list photos for album
    if ($action === 'list_photos') {
        $albumId = isset($_GET['album_id']) ? (int)$_GET['album_id'] : (isset($_GET['album']) ? (int)$_GET['album'] : 0);
        if ($albumId <= 0) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Album tidak ditemukan'), 404);
        }
        $album = fetch_album_info($albumId);
        if (!$album) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Album tidak ditemukan'), 404);
        }

        $fields = "id_gallery, id_album, jdl_gallery, keterangan, gallery_seo, gbr_gallery";
        if ($hasActive) $fields .= ", aktif";
        if ($hasUrutan) $fields .= ", urutan";

        $sql = "SELECT $fields FROM gallery WHERE id_album = ?";
        $sql .= $hasUrutan ? " ORDER BY urutan ASC, id_gallery DESC" : " ORDER BY id_gallery DESC";
        $stmt = $dbconnection->prepare($sql);
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $res = $stmt->get_result();
        $photos = array();
        while ($row = $res->fetch_assoc()) {
            $photos[] = photo_payload($row, $album['gbr_album'], $hasActive, $hasUrutan);
        }
        $stmt->close();
        closedb();
        json_response(array('ok' => true, 'album' => $album, 'photos' => $photos));
    }

    // upload photo(s)
    if ($action === 'upload_photo') {
        require_admin_json();
        require_post_csrf();

        $albumId     = isset($_POST['album_id']) ? (int)$_POST['album_id'] : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $status      = isset($_POST['status']) && strtoupper($_POST['status']) === 'N' ? 'N' : 'Y';

        if ($albumId <= 0) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Album belum dipilih'), 422);
        }
        $album = fetch_album_info($albumId);
        if (!$album) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Album tidak ditemukan'), 404);
        }

        $files = array();
        if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
            $files = reshape_files_array($_FILES['photos']);
        } elseif (!empty($_FILES['fupload']['tmp_name'])) {
            $files[] = $_FILES['fupload'];
        }

        if (empty($files)) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Tidak ada file yang diunggah'), 422);
        }

        $currentOrder = 0;
        if ($hasUrutan) {
            $resOrder = querydb_prepared("SELECT COALESCE(MAX(urutan),0) AS ord FROM gallery WHERE id_album = ?", "i", array($albumId));
            $rowOrder = $resOrder ? $resOrder->fetch_assoc() : array('ord' => 0);
            $currentOrder = (int)($rowOrder['ord'] ?? 0);
        }

        $inserted = array();
        foreach ($files as $idx => $file) {
            $title = isset($_POST['title']) && $_POST['title'] !== '' ? trim($_POST['title']) : pathinfo($file['name'], PATHINFO_FILENAME);
            $slug  = seo_title($title);
            $thisOrder = $hasUrutan ? $currentOrder + $idx + 1 : null;
            try {
                $upload = upload_image_secure($file, array(
                    'dest_dir'     => __DIR__ . '/../../../img_galeri',
                    'thumb_max_w'  => 360,
                    'thumb_max_h'  => 360,
                    'jpeg_quality' => 85,
                    'prefix'       => 'galeri_',
                ));
                $filename = $upload['filename'];
            } catch (Throwable $e) {
                closedb();
                json_response(array('ok' => false, 'error' => $e->getMessage()), 400);
            }

            $fields = "jdl_gallery, gallery_seo, id_album, keterangan, gbr_gallery";
            $types  = "ssiss";
            $params = array($title, $slug, $albumId, $description, $filename);
            if ($hasActive) {
                $fields .= ", aktif";
                $types  .= "s";
                $params[] = $status;
            }
            if ($hasUrutan) {
                $fields .= ", urutan";
                $types  .= "i";
                $params[] = $thisOrder;
            }

            $placeholders = implode(',', array_fill(0, count($params), '?'));
            $sql = "INSERT INTO gallery ($fields) VALUES ($placeholders)";
            $stmt = $dbconnection->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $newId = insert_id();
            $stmt->close();

            $row = array(
                'id_gallery' => $newId,
                'id_album'   => $albumId,
                'jdl_gallery'=> $title,
                'keterangan' => $description,
                'gallery_seo'=> $slug,
                'gbr_gallery'=> $filename,
            );
            if ($hasActive) $row['aktif'] = $status;
            if ($hasUrutan) $row['urutan'] = $thisOrder;
            $inserted[] = photo_payload($row, $album['gbr_album'], $hasActive, $hasUrutan);
        }

        closedb();
        json_response(array('ok' => true, 'photos' => $inserted));
    }

    // update photo meta
    if ($action === 'update_photo') {
        require_admin_json();
        require_post_csrf();

        $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title    = isset($_POST['title']) ? trim($_POST['title']) : '';
        $albumId  = isset($_POST['album_id']) ? (int)$_POST['album_id'] : 0;
        $desc     = isset($_POST['description']) ? trim($_POST['description']) : '';
        $status   = isset($_POST['status']) && strtoupper($_POST['status']) === 'N' ? 'N' : 'Y';
        $newOrder = $hasUrutan && isset($_POST['urutan']) ? (int)$_POST['urutan'] : null;

        if ($id <= 0) {
            closedb();
            json_response(array('ok' => false, 'error' => 'ID tidak valid'), 422);
        }

        $fields = "id_gallery, id_album, jdl_gallery, keterangan, gallery_seo, gbr_gallery";
        if ($hasActive) $fields .= ", aktif";
        if ($hasUrutan) $fields .= ", urutan";

        $stmt = $dbconnection->prepare("SELECT $fields FROM gallery WHERE id_gallery = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Foto tidak ditemukan'), 404);
        }

        $targetAlbum = $albumId > 0 ? $albumId : (int)$row['id_album'];
        $album = fetch_album_info($targetAlbum);
        if (!$album) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Album tujuan tidak ditemukan'), 404);
        }

        $newTitle = $title !== '' ? $title : $row['jdl_gallery'];
        $newSlug  = seo_title($newTitle);
        $newDesc  = $desc !== '' ? $desc : $row['keterangan'];
        $newStatus = $hasActive ? $status : null;

        $sql = "UPDATE gallery SET jdl_gallery = ?, gallery_seo = ?, id_album = ?, keterangan = ?";
        $types = "ssis";
        $params = array($newTitle, $newSlug, $targetAlbum, $newDesc);
        if ($hasActive) {
            $sql   .= ", aktif = ?";
            $types .= "s";
            $params[] = $newStatus;
        }
        if ($hasUrutan && $newOrder !== null) {
            $sql   .= ", urutan = ?";
            $types .= "i";
            $params[] = $newOrder;
        }
        $sql .= " WHERE id_gallery = ?";
        $types .= "i";
        $params[] = $id;

        $stmt = $dbconnection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        $row['id_album']    = $targetAlbum;
        $row['jdl_gallery'] = $newTitle;
        $row['gallery_seo'] = $newSlug;
        $row['keterangan']  = $newDesc;
        if ($hasActive) $row['aktif'] = $newStatus;
        if ($hasUrutan && $newOrder !== null) $row['urutan'] = $newOrder;

        $payload = photo_payload($row, $album['gbr_album'], $hasActive, $hasUrutan);
        closedb();
        json_response(array('ok' => true, 'photo' => $payload));
    }

    // delete photo
    if ($action === 'delete_photo') {
        require_admin_json();
        require_post_csrf();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            closedb();
            json_response(array('ok' => false, 'error' => 'ID tidak valid'), 422);
        }

        $stmt = $dbconnection->prepare("SELECT id_album, gbr_gallery FROM gallery WHERE id_gallery = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($albumId, $gbr);
        $has = $stmt->fetch();
        $stmt->close();

        if (!$has) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Foto tidak ditemukan'), 404);
        }

        $stmt = $dbconnection->prepare("DELETE FROM gallery WHERE id_gallery = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        if ($gbr) {
            $base = basename($gbr);
            @unlink(__DIR__ . "/../../../img_galeri/" . $base);
            @unlink(__DIR__ . "/../../../img_galeri/small_" . $base);
            @unlink(__DIR__ . "/../../../img_galeri/kecil_" . $base);
        }

        $stmt = $dbconnection->prepare("UPDATE album SET gbr_album = '' WHERE id_album = ? AND gbr_album = ?");
        $stmt->bind_param("is", $albumId, $gbr);
        $stmt->execute();
        $stmt->close();

        closedb();
        json_response(array('ok' => true));
    }

    // reorder photos
    if ($action === 'reorder_photo') {
        require_admin_json();
        require_post_csrf();

        if (!$hasUrutan) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Kolom urutan belum tersedia'), 400);
        }
        $albumId = isset($_POST['album_id']) ? (int)$_POST['album_id'] : 0;
        $orders = isset($_POST['orders']) ? $_POST['orders'] : array();
        if (is_string($orders)) {
            $decoded = json_decode($orders, true);
            if (is_array($decoded)) {
                $orders = $decoded;
            }
        }
        if ($albumId <= 0 || !is_array($orders) || empty($orders)) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Data urutan tidak lengkap'), 422);
        }

        $stmt = $dbconnection->prepare("UPDATE gallery SET urutan = ? WHERE id_gallery = ? AND id_album = ?");
        foreach ($orders as $row) {
            $photoId = isset($row['id']) ? (int)$row['id'] : 0;
            $pos     = isset($row['position']) ? (int)$row['position'] : 0;
            if ($photoId > 0) {
                $stmt->bind_param("iii", $pos, $photoId, $albumId);
                $stmt->execute();
            }
        }
        $stmt->close();
        closedb();
        json_response(array('ok' => true));
    }

    // set album cover from photo
    if ($action === 'set_cover') {
        require_admin_json();
        require_post_csrf();

        $albumId = isset($_POST['album_id']) ? (int)$_POST['album_id'] : 0;
        $photoId = isset($_POST['photo_id']) ? (int)$_POST['photo_id'] : 0;
        if ($albumId <= 0 || $photoId <= 0) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Parameter tidak lengkap'), 422);
        }

        $stmt = $dbconnection->prepare("SELECT gbr_gallery FROM gallery WHERE id_gallery = ? AND id_album = ?");
        $stmt->bind_param("ii", $photoId, $albumId);
        $stmt->execute();
        $stmt->bind_result($gbr);
        $has = $stmt->fetch();
        $stmt->close();

        if (!$has || !$gbr) {
            closedb();
            json_response(array('ok' => false, 'error' => 'Foto tidak ditemukan di album ini'), 404);
        }

        // Pastikan file sampul tersedia di folder img_album agar sisi publik tidak 404
        sync_album_cover_files($gbr, $root);

        $stmt = $dbconnection->prepare("UPDATE album SET gbr_album = ? WHERE id_album = ?");
        $stmt->bind_param("si", $gbr, $albumId);
        $stmt->execute();
        $stmt->close();

        $album = fetch_album_info($albumId);
        closedb();
        json_response(array('ok' => true, 'album' => $album, 'cover_url' => photo_thumb_url($gbr)));
    }

    closedb();
    json_response(array('ok' => false, 'error' => 'Action tidak dikenali'), 400);
}

// Legacy non-AJAX flow (kept for backward compatibility)
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
else{
  opendb();

  // Hapus galeri foto
  if ($module=='galerifoto' AND $act=='hapus'){
    require_post_csrf();
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) { 
      header("Location: /admin?module=".$module); 
      exit; 
    }

    // fetch filename (prepared)
    $stmt = $dbconnection->prepare("SELECT gbr_gallery FROM gallery WHERE id_gallery = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($gbr);
    $has = $stmt->fetch();
    $stmt->close();

    // delete row (prepared)
    $stmt = $dbconnection->prepare("DELETE FROM gallery WHERE id_gallery = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // unlink files safely
    if ($has && !empty($gbr)) {
      $base = basename($gbr);
      @unlink(__DIR__ . "/../../../img_galeri/" . $base);
      @unlink(__DIR__ . "/../../../img_galeri/small_" . $base);
      @unlink(__DIR__ . "/../../../img_galeri/kecil_" . $base);
    }

    header("Location: /admin?module=".$module);
    exit;
  }

  // Input galeri foto
  elseif ($module=='galerifoto' AND $act=='input'){
    require_post_csrf();

    $judul_galeri = $_POST['judul_galeri'];
    $galeri_seo   = seo_title($_POST['judul_galeri']);
    $album        = $_POST['album'];
    $keterangan   = $_POST['keterangan'];

    $has_file = !empty($_FILES['fupload']['tmp_name']);

    if (!$has_file){
      // prepared insert (no image)
      $stmt = $dbconnection->prepare("
        INSERT INTO gallery (jdl_gallery, gallery_seo, id_album, keterangan)
        VALUES (?, ?, ?, ?)
      ");
      $stmt->bind_param("ssis", $judul_galeri, $galeri_seo, $album, $keterangan);
      $stmt->execute();
      $stmt->close();
      header("Location: /admin?module=".$module);
    } else {
      // secure upload -> img_galeri + small_ thumbnail
      try {
        $res = upload_image_secure($_FILES['fupload'], [
          'dest_dir'     => __DIR__ . '/../../../img_galeri',
          'thumb_max_w'  => 180,
          'thumb_max_h'  => 180,
          'jpeg_quality' => 85,
          'prefix'       => 'galeri_',
        ]);
        $nama_foto = $res['filename'];
      } catch (Throwable $e) {
        echo "<script>window.alert('Upload Gagal: " . e($e->getMessage()) . "'); window.location=('/admin?module=galerifoto')</script>";
        exit;
      }

      $stmt = $dbconnection->prepare("
        INSERT INTO gallery (jdl_gallery, gallery_seo, id_album, keterangan, gbr_gallery)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("ssiss", $judul_galeri, $galeri_seo, $album, $keterangan, $nama_foto);
      $stmt->execute();
      $stmt->close();
      header("Location: /admin?module=".$module);
    }
  }

  // Update galeri foto
  elseif ($module=='galerifoto' AND $act=='update'){
    require_post_csrf();

    $id           = (int)$_POST['id'];
    $judul_galeri = $_POST['judul_galeri'];
    $galeri_seo   = seo_title($_POST['judul_galeri']);
    $album        = $_POST['album'];
    $keterangan   = $_POST['keterangan'];

    $has_new = !empty($_FILES['fupload']['tmp_name']);
    if (!$has_new) {
      // prepared update (no image change)
      $stmt = $dbconnection->prepare("
        UPDATE gallery SET jdl_gallery = ?, gallery_seo = ?, id_album = ?, keterangan = ? WHERE id_gallery = ?
      ");
      $stmt->bind_param("ssisi", $judul_galeri, $galeri_seo, $album, $keterangan, $id);
      $stmt->execute();
      $stmt->close();
      header("Location: /admin?module=".$module);
    } else {
      // upload new image securely
      try {
        $res = upload_image_secure($_FILES['fupload'], [
          'dest_dir'     => __DIR__ . '/../../../img_galeri',
          'thumb_max_w'  => 180,
          'thumb_max_h'  => 180,
          'jpeg_quality' => 85,
          'prefix'       => 'galeri_',
        ]);
        $nama_foto = $res['filename'];
      } catch (Throwable $e) {
        echo "<script>window.alert('Upload Gagal: " . e($e->getMessage()) . "'); window.location=('/admin?module=galerifoto')</script>";
        exit;
      }

      // fetch old file for cleanup post-update
      $old = null;
      $stmt = $dbconnection->prepare("SELECT gbr_gallery FROM gallery WHERE id_gallery = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($old_gbr);
      if ($stmt->fetch()) $old = $old_gbr;
      $stmt->close();

      // prepared update with new filename
      $stmt = $dbconnection->prepare("
        UPDATE gallery SET jdl_gallery = ?, gallery_seo = ?, id_album = ?, keterangan = ?, gbr_gallery = ? WHERE id_gallery = ?
      ");
      $stmt->bind_param("ssissi", $judul_galeri, $galeri_seo, $album, $keterangan, $nama_foto, $id);
      $stmt->execute();
      $stmt->close();

      // cleanup old files (best-effort)
      if (!empty($old)) {
        $base = basename($old);
        @unlink(__DIR__ . "/../../../img_galeri/" . $base);
        @unlink(__DIR__ . "/../../../img_galeri/small_" . $base);
        @unlink(__DIR__ . "/../../../img_galeri/kecil_" . $base);
      }

      header("Location: /admin?module=".$module);
    }
  }

  closedb();
}
?>
