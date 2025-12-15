<?php
// Login handler with secure session cookies and password upgrade path
require_once __DIR__ . "/includes/bootstrap.php"; // starts session with secure flags, loads DB helpers
require_once __DIR__ . '/inc/audit_log.php';
require_once __DIR__ . '/inc/login_bruteforce.php';
opendb();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    closedb();
    header('Location: /login');
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

// Check temporary block before processing credentials
$remainBlock = login_block_is_active($ip);
if ($remainBlock > 0) {
    if (!login_block_recently_logged($ip)) {
        audit_event('auth', 'LOGIN_BLOCKED', 'ip', $ip, 'Login blocked due to repeated failures', null, null, [
            'ip' => $ip,
            'remaining' => $remainBlock
        ]);
        login_block_mark_logged($ip);
    }
    closedb();
    header('Location: /login?blocked=' . max(1, (int)$remainBlock));
    exit;
}

// Progressive throttle based on recent failures
$failCount = login_fail_get_count($ip, 600);
$delay = login_throttle_delay_seconds($failCount);
login_throttle_sleep($delay);

// Legacy key for old MD5-based hashes
$legacy_key = base64_decode($key ?? '');

function anti_injection($data) {
    return trim(stripslashes(strip_tags(htmlspecialchars($data, ENT_QUOTES))));
}

$username_raw = $_POST['username'] ?? '';
$password_raw = $_POST['password'] ?? '';

$username = anti_injection($username_raw);

// Basic allowlist on username: letters, numbers, underscore, dot, dash, @
if (!preg_match('/^[A-Za-z0-9_.@-]+$/', $username)) {
    $state = login_fail_track($ip, $username);
    $blockedNow = false;
    if (login_fail_is_bruteforce($state)) {
        login_block_set($ip, 900);
        $blockedNow = true;
        if (!login_fail_recently_logged($ip)) {
            audit_event('auth', 'LOGIN_BRUTEFORCE', 'ip', $ip, 'Brute force login detected', null, null, [
                'ip' => $ip,
                'usernames' => $state['usernames'],
                'fail_count' => $state['count'],
                'window_seconds' => 600
            ]);
            login_fail_mark_logged($ip);
        }
        if (!login_block_recently_logged($ip)) {
            audit_event('auth', 'LOGIN_BLOCKED', 'ip', $ip, 'Login blocked due to repeated failures', null, null, [
                'ip' => $ip,
                'block_seconds' => 900
            ]);
            login_block_mark_logged($ip);
        }
    }
    closedb();
    if ($blockedNow) {
        $remain = login_block_is_active($ip);
        $seconds = $remain > 0 ? $remain : 900;
        closedb();
        header('Location: /login?blocked=' . (int)$seconds);
        exit;
    }
    header('Location: /login?error=1');
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
    $storedHash   = trim($userRow['password'] ?? '');
    $storedLen    = strlen($storedHash);
    $isLegacyMd5  = (bool)preg_match('/^[a-f0-9]{32}$/i', $storedHash);
    $isLegacySha1 = (bool)preg_match('/^[a-f0-9]{40}$/i', $storedHash);
    $isTruncBcrypt = (strpos($storedHash, '$2y$') === 0 && $storedLen > 0 && $storedLen < 60);

    if ($isLegacyMd5) {
        // Legacy check: md5($password . $legacy_key) OR plain md5($password) (case-insensitive)
        $legacyCandidateSalted = strtolower(md5($password_raw . $legacy_key));
        $legacyCandidatePlain  = strtolower(md5($password_raw));
        if ($storedHash !== '' && (
            strtolower($storedHash) === $legacyCandidateSalted ||
            strtolower($storedHash) === $legacyCandidatePlain
        )) {
            $verified    = true;
            $needsRehash = true; // upgrade to password_hash() below
        }
    } elseif ($isLegacySha1) {
        // Some older installs used sha1
        $shaCandidate = strtolower(sha1($password_raw));
        if (strtolower($storedHash) === $shaCandidate) {
            $verified    = true;
            $needsRehash = true;
        }
    } elseif ($isTruncBcrypt) {
        // Truncated bcrypt hashes (column too short). Recreate using the salt part (first 29 chars).
        $salt = substr($storedHash, 0, 29);
        $fullCandidate = crypt($password_raw, $salt);
        if (is_string($fullCandidate) && hash_equals(substr($fullCandidate, 0, $storedLen), $storedHash)) {
            $verified    = true;
            $needsRehash = true;
            $storedHash  = $fullCandidate; // promote to full length for rehash/store
        }
    } elseif ($storedHash !== '') {
        // Modern check using password_hash / password_verify
        $verified    = password_verify($password_raw, $storedHash);
        $needsRehash = $verified && password_needs_rehash($storedHash, PASSWORD_DEFAULT);
    } else {
        // Extremely old plain-text password storage
        if ($password_raw === $storedHash) {
            $verified    = true;
            $needsRehash = true;
        }
    }
}

if (!$verified) {
    $state = login_fail_track($ip, $username);
    $blockedNow = false;
    if (login_fail_is_bruteforce($state)) {
        login_block_set($ip, 900);
        $blockedNow = true;
        if (!login_fail_recently_logged($ip)) {
            audit_event('auth', 'LOGIN_BRUTEFORCE', 'ip', $ip, 'Brute force login detected', null, null, [
                'ip' => $ip,
                'usernames' => $state['usernames'],
                'fail_count' => $state['count'],
                'window_seconds' => 600
            ]);
            login_fail_mark_logged($ip);
        }
        if (!login_block_recently_logged($ip)) {
            audit_event('auth', 'LOGIN_BLOCKED', 'ip', $ip, 'Login blocked due to repeated failures', null, null, [
                'ip' => $ip,
                'block_seconds' => 900
            ]);
            login_block_mark_logged($ip);
        }
    }
    closedb();
    if ($blockedNow) {
        $remain = login_block_is_active($ip);
        $seconds = $remain > 0 ? $remain : 900;
        closedb();
        header('Location: /login?blocked=' . (int)$seconds);
        exit;
    }
    header('Location: /login?error=1');
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

audit_event('auth', 'LOGIN_OK', 'user', $username, 'Login success', null, null, ['username' => $username]);
login_fail_reset($ip);
login_block_clear($ip);

closedb();
header("Location: /admin");
exit;
