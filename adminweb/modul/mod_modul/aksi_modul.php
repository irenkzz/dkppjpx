<?php
require_once __DIR__ . '/../../includes/bootstrap.php'; // secure session + helpers
opendb();

// Apabila user belum login
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
    closedb();
    exit;
}

// Batasi hanya admin
if (($_SESSION['leveluser'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

$module = $_GET['module'];
$act    = $_GET['act'];

// Input modul
if ($module=='modul' AND $act=='input'){
  require_post_csrf();

  // cari urutan terakhir (read-only; keep as-is)
  $query = querydb("SELECT urutan FROM modul ORDER BY urutan DESC LIMIT 1");
  $r     = $query->fetch_array();
  $urutan = (int)$r['urutan'] + 1;

  $nama_modul = $_POST['nama_modul'];
  $link       = $_POST['link'];

  exec_prepared(
    "INSERT INTO modul (nama_modul, link, urutan) VALUES (?, ?, ?)",
    "ssi",
    [$nama_modul, $link, $urutan]
  );

  header("location:../../media.php?module=".$module);
}

elseif ($module=='modul' AND $act=='update'){
require_post_csrf();

$id         = (int)($_POST['id'] ?? 0);
$urutan     = (int)($_POST['urutan'] ?? 0);
$nama_modul = $_POST['nama_modul'];
$link       = $_POST['link'];
$status     = $_POST['status'];
$aktif      = $_POST['aktif'];

exec_prepared(
  "UPDATE modul SET nama_modul = ?, link = ?, urutan = ?, status = ?, aktif = ? WHERE id_modul = ?",
  "ssissi",
  [$nama_modul, $link, $urutan, $status, $aktif, $id]
);

header("location:../../media.php?module=".$module);
}

closedb();
?>
