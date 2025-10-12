<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // When viewing an article → use the article title
    $res = querydb_prepared("SELECT judul FROM berita WHERE id_berita = ?", "i", [$id]);
    $row = $res ? $res->fetch_array() : null;
    $title = $row['judul'] ?? '';
} else {
    // When on the homepage → use the website title from identitas table
    $res = querydb("SELECT nama_website FROM identitas LIMIT 1");
    $row = $res ? $res->fetch_array() : null;
    $title = $row['nama_website'] ?? 'Website';
}

echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
?>