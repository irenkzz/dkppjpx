<?php
include_once __DIR__ . "/config/koneksi.php";


opendb();

// anchor: downlot-secure-download
$direktori = __DIR__ . "/files/";
$raw = $_GET['file'] ?? '';
$raw = str_replace("\0", '', $raw);
$filename = basename($raw);

$allowed_ext = ['pdf','exe','zip','rar','doc','xls','ppt','gif','png','jpeg','jpg'];
$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$fullpath = $direktori . $filename;

if (empty($filename) || !in_array($file_extension, $allowed_ext, true) || !is_file($fullpath)) {
    http_response_code(403);
    echo "<h1>Access forbidden!</h1>
          <p>File tidak tersedia atau akses dilarang.</p>";
    closedb();
    exit;
}

if ($file_extension === 'php' || stripos($filename, '.php') !== false) {
    http_response_code(403);
    echo "<h1>Access forbidden!</h1>
          <p>Jenis file tidak diperbolehkan.</p>";
    closedb();
    exit;
}

switch($file_extension){
  case "pdf": $ctype="application/pdf"; break;
  case "exe": $ctype="application/octet-stream"; break;
  case "zip": $ctype="application/zip"; break;
  case "rar": $ctype="application/vnd.rar"; break;
  case "doc": $ctype="application/msword"; break;
  case "xls": $ctype="application/vnd.ms-excel"; break;
  case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
  case "gif": $ctype="image/gif"; break;
  case "png": $ctype="image/png"; break;
  case "jpeg":
  case "jpg": $ctype="image/jpeg"; break;
  default: $ctype="application/octet-stream";
}

exec_prepared("UPDATE download SET hits = hits + 1 WHERE nama_file = ?", "s", [$filename]);

if (filesize($fullpath) === 0) {
    http_response_code(404);
    closedb();
    exit;
}

if (ob_get_length()) ob_end_clean();
header("Content-Type: $ctype");
header("Content-Description: File Transfer");
header('Content-Disposition: attachment; filename="' . rawurldecode(basename($filename)) . '";');
header("Content-Transfer-Encoding: binary");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header("Content-Length: " . filesize($fullpath));

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