<?php
session_start();
include_once __DIR__ . "/config/koneksi.php";

opendb();

// Preserve current hashing scheme for compatibility
$kunci = base64_decode($key);

function anti_injection($data) {
    return stripslashes(strip_tags(htmlspecialchars($data, ENT_QUOTES)));
}

$username_raw = $_POST['username'] ?? '';
$password_raw = $_POST['password'] ?? '';

$username = anti_injection($username_raw);
$password = anti_injection(md5($password_raw . $kunci));

// Optional: basic allowlist like the original (keeps behavior)
if (!ctype_alnum($username) || !ctype_alnum($password)) {
    echo "<script>alert('Sekarang loginnya tidak bisa di injeksi lho.'); window.location = 'index.php'</script>";
    closedb();
    exit;
}

// anchor: cek_login-prepared-compatible
$login = querydb_prepared(
    "SELECT * FROM users WHERE username = ? AND password = ? AND blokir = 'N' LIMIT 1",
    "ss",
    [$username, $password]
);

if ($login && $login->num_rows > 0) {
    $r = $login->fetch_array();

    // Regenerate session ID on login for security
    session_regenerate_id(true);

    $_SESSION['namauser']     = $r['username'];
    $_SESSION['passuser']     = $r['password'];
    $_SESSION['namalengkap']  = $r['nama_lengkap'];
    $_SESSION['leveluser']    = $r['level'];

    // Keep original behavior: update id_session and redirect
    $sid_baru = session_id();
    exec_prepared("UPDATE users SET id_session = ? WHERE username = ?", "ss", [$sid_baru, $username]);

    header("location:media.php?module=beranda");
    exit;
} else {
    echo "<link href='style_login.css' rel='stylesheet' type='text/css' />
          <center>LOGIN GAGAL! <br>
          Username atau Password Anda tidak benar.<br>
          Atau account Anda sedang diblokir.<br>";
    echo "<a href=index.php><b>ULANGI LAGI</b></a></center>";
}

closedb();
?>
