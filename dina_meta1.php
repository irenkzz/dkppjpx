<?php

/// DB is opened by the parent (media.php/template.php)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $res = querydb_prepared("SELECT nama_kategori FROM kategori WHERE id_kategori = ?", "i", [$id]);
    $row = $res ? $res->fetch_array() : null;
    $nama = $row['nama_kategori'] ?? 'Kategori';
} else {
    $nama = 'Kategori';
}

echo htmlspecialchars($nama, ENT_QUOTES, 'UTF-8');

?>