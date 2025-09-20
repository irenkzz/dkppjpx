<?php
// adminweb/includes/bootstrap.php
// Init session + cookie yang aman
if (session_status() === PHP_SESSION_NONE) {
    // NOTE: aktifkan 'secure'=>true kalau situs sudah HTTPS
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_field(): void {
    echo '<input type="hidden" name="csrf" value="' . htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
}
function csrf_check(): void {
    // Jika dipanggil di aksi POST; aman fallback jika proyek lama belum menambahkan token
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tok = $_POST['csrf'] ?? '';
        $sess = $_SESSION['csrf'] ?? '';
        if (!$tok || !$sess || !hash_equals($sess, $tok)) {
            http_response_code(400);
            exit('CSRF verification failed.');
        }
    }
}

// Escape output HTML
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Koneksi DB (root/config/koneksi.php)
$root = dirname(dirname(__DIR__));           // .../adminweb -> project root
require_once $root . '/config/koneksi.php';  // pastikan file ini mengisi $koneksi (mysqli)
