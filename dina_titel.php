<?php
// DB is opened by the parent (media.php/template.php)
// Expecting: ?id=ID of berita
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// anchor: dina_titel-prepared
if ($id > 0) {
    $res = querydb_prepared("SELECT judul FROM berita WHERE id_berita = ?", "i", [$id]);
    $row = $res ? $res->fetch_array() : null;
    $title = $row['judul'] ?? 'Artikel';
} else {
    $title = 'Artikel';
}

// Output safe title
echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

?>