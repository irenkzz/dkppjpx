<?php
include_once __DIR__ . "/config/koneksi.php";

opendb();

// anchor: downlot-secure-download
$direktori = __DIR__ . "/files/";

// 1. Ambil dan sanitasi parameter
$raw = $_GET['file'] ?? '';
$raw = str_replace("\0", '', $raw); // buang null byte
$filename = basename($raw);

// 2. Validasi minimal nama file
if ($filename === '') {
    http_response_code(400);
    echo "<h1>Bad request</h1><p>Parameter file tidak valid.</p>";
    closedb();
    exit;
}

// 3. Pastikan file tercatat di database
global $dbconnection;

$stmt = $dbconnection->prepare(
    "SELECT id_download, nama_file 
     FROM download 
     WHERE nama_file = ? 
     LIMIT 1"
);
if ($stmt === false) {
    http_response_code(500);
    echo "<h1>Server error</h1><p>Gagal mempersiapkan query.</p>";
    closedb();
    exit;
}

$stmt->bind_param("s", $filename);
$stmt->execute();
$result = $stmt->get_result();
$row    = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    // Tidak ada record download untuk file ini
    http_response_code(404);
    echo "<h1>File tidak ditemukan</h1>
          <p>Data file tidak tersedia.</p>";
    closedb();
    exit;
}

// Gunakan nama_file dari DB (mencegah mismatch aneh)
$db_filename = basename($row['nama_file'] ?? '');
$fullpath    = $direktori . $db_filename;

// 4. Validasi extension & keberadaan file fisik
$allowed_ext = ['pdf','zip','rar','doc','xls','ppt','gif','png','jpeg','jpg'];
$file_extension = strtolower(pathinfo($db_filename, PATHINFO_EXTENSION));

// NOTE: .php sudah otomatis tidak masuk allowed_ext, tapi kita tetap extra-safe
if (
    $db_filename === '' ||
    !in_array($file_extension, $allowed_ext, true) ||
    $file_extension === 'php' ||
    stripos($db_filename, '.php') !== false ||
    !is_file($fullpath)
) {
    http_response_code(403);
    echo "<h1>Access forbidden!</h1>
          <p>File tidak tersedia atau akses dilarang.</p>";
    closedb();
    exit;
}

// 5. Tentukan content-type
switch ($file_extension) {
    case "pdf":  $ctype = "application/pdf"; break;
    case "zip":  $ctype = "application/zip"; break;
    case "rar":  $ctype = "application/vnd.rar"; break;
    case "doc":  $ctype = "application/msword"; break;
    case "xls":  $ctype = "application/vnd.ms-excel"; break;
    case "ppt":  $ctype = "application/vnd.ms-powerpoint"; break;
    case "gif":  $ctype = "image/gif"; break;
    case "png":  $ctype = "image/png"; break;
    case "jpeg":
    case "jpg":  $ctype = "image/jpeg"; break;
    default:     $ctype = "application/octet-stream";
}

// 6. Update hits berdasarkan id_download (lebih kuat)
$id_download = (int)($row['id_download'] ?? 0);
if ($id_download > 0) {
    exec_prepared(
        "UPDATE download SET hits = hits + 1 WHERE id_download = ?",
        "i",
        [$id_download]
    );
}

// 7. File size check
$filesize = @filesize($fullpath);
if ($filesize === false || $filesize === 0) {
    http_response_code(404);
    echo "<h1>File tidak ditemukan</h1>";
    closedb();
    exit;
}

// 8. Kirim header dan stream file
if (ob_get_length()) {
    ob_end_clean();
}

header("Content-Type: $ctype");
header("Content-Description: File Transfer");
header('Content-Disposition: attachment; filename="' . rawurldecode(basename($db_filename)) . '";');
header("Content-Transfer-Encoding: binary");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header("Content-Length: " . $filesize);

$fp = fopen($fullpath, 'rb');
if ($fp !== false) {
    while (!feof($fp)) {
        echo fread($fp, 8192);
        flush();
    }
    fclose($fp);
}

closedb();
exit();
?>
