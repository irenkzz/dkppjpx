<?php
// DB is opened by the parent (media.php/template.php)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $res = querydb_prepared("SELECT tag FROM berita WHERE id_berita = ?", "i", [$id]);
    $row = $res ? $res->fetch_array() : null;
    $out = $row['tag'] ?? '';
} else {
    $res = querydb("SELECT meta_keyword FROM identitas LIMIT 1");
    $row = $res ? $res->fetch_array() : null;
    $out = $row['meta_keyword'] ?? '';
}

echo htmlspecialchars($out, ENT_QUOTES, 'UTF-8');
?>