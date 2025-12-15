<?php
// adminweb/includes/bootstrap.php
// Security-first bootstrap: safe session cookies, CSRF helpers, HTML escaping,
// and DB connection include.

/**
 * Determine if current request is HTTPS (covers typical proxies/ports).
 */
function is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if (is_string($proto) && strtolower($proto) === 'https') return true;
    return false;
}

// ---------- Session init with secure cookies ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax', // consider 'Strict' for admin-only dashboards
        'secure'   => is_https(), // set true once you’re fully on HTTPS
    ]);
    session_start();
}

// ---------- CSRF token + helpers ----------
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/**
 * Echo a hidden input with the CSRF token.
 * Use this inside every <form method="POST"> that mutates state.
 */
function csrf_field(): void {
    echo '<input type="hidden" name="csrf" value="' .
         htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') .
         '">';
}

/**
 * Verify CSRF for POST. If not POST or token invalid, stop the request.
 * Call this in action handlers *before* making changes.
 */
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed.');
    }
    $tok  = $_POST['csrf']  ?? '';
    $sess = $_SESSION['csrf'] ?? '';
    if (!$tok || !$sess || !hash_equals($sess, $tok)) {
        http_response_code(400);
        exit('CSRF verification failed.');
    }
}

/**
 * Convenience guard: require POST + CSRF in one call.
 * Example (in aksi_*.php): if ($act === 'hapus') require_post_csrf();
 */
function require_post_csrf(): void {
    csrf_check();
}

// ---------- Output escaping ----------
/**
 * Escape for HTML contexts (text and attributes).
 */
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

if (!function_exists('safe_url')) {
    /**
     * Sanitize URLs before using in href/src attributes.
     * Allows http/https/mailto schemes and blocks protocol-relative/javascript payloads.
     */
    function safe_url(?string $url, array $allowedSchemes = ['http', 'https', 'mailto']): string {
        $trimmed = trim((string)$url);
        if ($trimmed === '' || strpos($trimmed, '//') === 0) {
            return '#';
        }

        $parts = parse_url($trimmed);
        if ($parts === false) {
            return '#';
        }

        if (isset($parts['scheme'])) {
            $scheme = strtolower($parts['scheme']);
            if (!in_array($scheme, $allowedSchemes, true)) {
                return '#';
            }
        }

        return htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
    }
}

// ---------- Database bootstrap ----------
// This mirrors your original include pathing to load $koneksi (mysqli).
$root = dirname(dirname(__DIR__));           // .../adminweb -> project root
require_once $root . '/config/koneksi.php';  // must define $koneksi (mysqli)

// ---------- Auth guard ----------
function require_admin_login(): void {
    if (!isset($_SESSION['namauser'])) {
        header('Location: /login');
        exit;
    }
}
