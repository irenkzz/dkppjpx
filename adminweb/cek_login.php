<?php
// Login handler with secure session cookies and password upgrade path
require_once __DIR__ . "/includes/bootstrap.php"; // starts session with secure flags, loads DB helpers
opendb();

// Legacy key for old MD5-based hashes
$legacy_key = base64_decode($key ?? '');

function anti_injection($data) {
    return stripslashes(strip_tags(htmlspecialchars($data, ENT_QUOTES)));
}

$username_raw = $_POST['username'] ?? '';
$password_raw = $_POST['password'] ?? '';

$username = anti_injection($username_raw);

// Basic allowlist on username (preserve legacy behavior)
if (!ctype_alnum($username)) {
    echo "<script>alert('Sekarang loginnya tidak bisa di injeksi lho.'); window.location = 'index.php'</script>";
    closedb();
    exit;
}

// Fetch user row (blokir = N only)
$login = querydb_prepared(
    "SELECT * FROM users WHERE username = ? AND blokir = 'N' LIMIT 1",
    "s",
    [$username]
);

$verified    = false;
$needsRehash = false;
$userRow     = $login && $login->num_rows > 0 ? $login->fetch_assoc() : null;

if ($userRow) {
    $storedHash   = $userRow['password'] ?? '';
    $isLegacyHash = (bool)preg_match('/^[a-f0-9]{32}$/i', $storedHash);

    if ($isLegacyHash) {
        // Legacy check: md5($password . $legacy_key)
        $legacyCandidate = md5($password_raw . $legacy_key);
        if ($storedHash !== '' && hash_equals($storedHash, $legacyCandidate)) {
            $verified    = true;
            $needsRehash = true; // upgrade to password_hash() below
        }
    } elseif ($storedHash !== '') {
        // Modern check using password_hash / password_verify
        $verified    = password_verify($password_raw, $storedHash);
        $needsRehash = $verified && password_needs_rehash($storedHash, PASSWORD_DEFAULT);
    }
}

if (!$verified) {
    echo "<link href='style_login.css' rel='stylesheet' type='text/css' />
          <center>LOGIN GAGAL! <br>
          Username atau Password Anda tidak benar.<br>
          Atau account Anda sedang diblokir.<br>";
    echo "<a href=index.php><b>ULANGI LAGI</b></a></center>";
    closedb();
    exit;
}

// Rehash legacy/weak hashes on successful login to migrate transparently
if ($needsRehash) {
    $newHash = password_hash($password_raw, PASSWORD_DEFAULT);
    exec_prepared("UPDATE users SET password = ? WHERE username = ?", "ss", [$newHash, $username]);
    $userRow['password'] = $newHash;
}

// Regenerate session ID on login for security
session_regenerate_id(true);

$_SESSION['namauser']    = $userRow['username'];
$_SESSION['passuser']    = $userRow['password']; // store current hash (legacy or upgraded)
$_SESSION['namalengkap'] = $userRow['nama_lengkap'];
$_SESSION['leveluser']   = $userRow['level'];

// Keep original behavior: update id_session and redirect
$sid_baru = session_id();
exec_prepared("UPDATE users SET id_session = ? WHERE username = ?", "ss", [$sid_baru, $username]);

closedb();
header("location:media.php?module=beranda");
exit;
