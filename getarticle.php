<?php
include_once __DIR__ . "/config/koneksi.php";


opendb();

$filter = $_POST['filter'] ?? '';

// anchor: getarticle-filter-sql
// Use prepared statement to avoid SQL injection
$query = querydb_prepared(
    "SELECT * FROM berita WHERE DATE_FORMAT(tanggal, '%M %Y') = ?",
    "s",
    [$filter]
);

while ($row = $query->fetch_array()) {
    echo "<div>";
    echo "<h3>" . htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8') . "</h3>";
    echo "<p>" . htmlspecialchars($row['isi_berita'], ENT_QUOTES, 'UTF-8') . "</p>";
    echo "</div>";
}

closedb();
?>