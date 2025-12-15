<?php
// Minimal audit logging helper
require_once __DIR__ . '/audit_policy.php';
require_once __DIR__ . '/audit_alert_config.php';

function audit_log_sanitize_post($post)
{
    $clean = array();
    $max_len = 2000;
    $deny_keys = array('password', 'pass', 'passuser', 'token', 'csrf', 'csrf_token');

    if (!is_array($post)) {
        return $clean;
    }

    foreach ($post as $key => $val) {
        $key_str = (string)$key;
        if (in_array(strtolower($key_str), $deny_keys, true)) {
            continue;
        }

        if (is_array($val)) {
            $clean[$key_str] = audit_log_sanitize_post($val);
        } else {
            $val_str = (string)$val;
            if (strlen($val_str) > $max_len) {
                $val_str = substr($val_str, 0, $max_len);
            }
            $clean[$key_str] = $val_str;
        }
    }

    return $clean;
}

function audit_log_encode_json($data)
{
    if ($data === null) {
        return null;
    }

    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false || $json === null) {
        return null;
    }

    return $json;
}

function audit_alert_cache_dir()
{
    $dir = dirname(__DIR__) . '/cache/audit_alerts';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function audit_alert_rate_limited($key, $seconds)
{
    if ($seconds <= 0) {
        return false;
    }
    $dir = audit_alert_cache_dir();
    $file = $dir . '/' . md5((string)$key) . '.txt';
    $now = time();
    if (is_file($file)) {
        $last = (int)@file_get_contents($file);
        if ($last > 0 && ($now - $last) < $seconds) {
            return true;
        }
    }
    // Best-effort write; if fails, skip alert to stay fail-safe
    @file_put_contents($file, (string)$now);
    return false;
}

function audit_alert_should_send($module, $action, $entity, $entityId, $extra)
{
    global $auditAlertConfig;

    if (!isset($auditAlertConfig['enabled']) || $auditAlertConfig['enabled'] !== true) {
        return false;
    }

    $criticalActions = array(
        'ACCESS_DENIED',
        'DELETE',
        'BULK_DELETE',
        'ROLE_CHANGE',
        'DEACTIVATE',
        'RESET_PASSWORD',
        'LOGIN_BRUTEFORCE',
        'LOGIN_BLOCKED',
    );

    if (in_array($action, $criticalActions, true)) {
        return true;
    }

    if (in_array($action, array('BULK_UPDATE', 'SAVE_SORT', 'REORDER'), true)) {
        $criticalModules = array('menu', 'users', 'identitas', 'modul');
        if (in_array($module, $criticalModules, true)) {
            return true;
        }
    }

    if ($action === 'LOGIN_FAIL') {
        // handled separately by threshold in audit_alert_maybe
        return true;
    }

    return false;
}

function audit_alert_build_payload($module, $action, $entity, $entityId, $message, $extra_json)
{
    $payload = array(
        'time'       => date('Y-m-d H:i:s'),
        'username'   => isset($_SESSION['namauser']) ? (string)$_SESSION['namauser'] : null,
        'user_level' => isset($_SESSION['leveluser']) ? (string)$_SESSION['leveluser'] : null,
        'module'     => $module,
        'action'     => $action,
        'entity'     => $entity,
        'entity_id'  => $entityId,
        'message'    => $message,
        'url'        => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : null,
        'ip'         => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null,
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null,
    );

    if ($extra_json !== null) {
        $payload['extra'] = $extra_json;
    }

    return $payload;
}

function audit_alert_send_webhook($payload)
{
    global $auditAlertConfig;
    $url = isset($auditAlertConfig['webhook_url']) ? trim((string)$auditAlertConfig['webhook_url']) : '';
    if ($url === '') {
        return;
    }
    $json = json_encode($payload);
    if ($json === false) {
        return;
    }
    $opts = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $json,
            'timeout' => 5,
        ),
    );
    $context = stream_context_create($opts);
    @file_get_contents($url, false, $context);
}

function audit_alert_send_email($payload)
{
    global $auditAlertConfig;
    $to = isset($auditAlertConfig['email_to']) ? trim((string)$auditAlertConfig['email_to']) : '';
    if ($to === '') {
        return;
    }

    $from = isset($auditAlertConfig['from_email']) ? trim((string)$auditAlertConfig['from_email']) : '';
    $subject = '[Audit Alert] ' . ($payload['action'] ?? '') . ' ' . ($payload['module'] ?? '');
    $lines = array();
    foreach ($payload as $k => $v) {
        if (is_array($v)) {
            $v = json_encode($v);
        }
        $lines[] = $k . ': ' . (string)$v;
    }
    $body = implode("\n", $lines);
    $headers = '';
    if ($from !== '') {
        $headers .= 'From: ' . $from . "\r\n";
    }
    @mail($to, $subject, $body, $headers);
}

function audit_alert_send($payload)
{
    try {
        audit_alert_send_webhook($payload);
    } catch (Throwable $e) {
        // fail-safe
    }
    try {
        audit_alert_send_email($payload);
    } catch (Throwable $e) {
        // fail-safe
    }
}

function audit_alert_login_fail_exceeded($ip)
{
    global $auditAlertConfig;
    $threshold = isset($auditAlertConfig['login_fail_threshold']) ? $auditAlertConfig['login_fail_threshold'] : array();
    $maxFails = isset($threshold['max_fails']) ? (int)$threshold['max_fails'] : 0;
    $window   = isset($threshold['window_seconds']) ? (int)$threshold['window_seconds'] : 0;

    if ($maxFails <= 0 || $window <= 0) {
        return false;
    }

    $dir = audit_alert_cache_dir();
    $file = $dir . '/login_fail_' . md5((string)$ip) . '.txt';
    $now = time();
    $entries = array();
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        if ($raw !== false) {
            $parts = explode(',', $raw);
            foreach ($parts as $p) {
                $t = (int)$p;
                if ($t > 0 && ($now - $t) <= $window) {
                    $entries[] = $t;
                }
            }
        }
    }
    $entries[] = $now;
    // keep recent only
    $entries = array_filter($entries, function ($t) use ($now, $window) {
        return ($now - $t) <= $window;
    });
    @file_put_contents($file, implode(',', $entries));
    return count($entries) >= $maxFails;
}

function audit_alert_maybe($module, $action, $entity, $entityId, $message, $extra_json)
{
    global $auditAlertConfig;
    if (!isset($auditAlertConfig['enabled']) || $auditAlertConfig['enabled'] !== true) {
        return;
    }

    if (!audit_alert_should_send($module, $action, $entity, $entityId, $extra_json)) {
        return;
    }

    $actionLimit = 0;
    if (isset($auditAlertConfig['rate_limit_seconds_by_action'][$action])) {
        $actionLimit = (int)$auditAlertConfig['rate_limit_seconds_by_action'][$action];
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';

    if ($action === 'LOGIN_FAIL') {
        if (!audit_alert_login_fail_exceeded($ip)) {
            return;
        }
        $actionLimit = max($actionLimit, 60);
    }

    $rateKey = $action . '|' . $module . '|' . $entity . '|' . $entityId . '|' . $ip;
    if ($actionLimit > 0 && audit_alert_rate_limited($rateKey, $actionLimit)) {
        return;
    }

    $payload = audit_alert_build_payload($module, $action, $entity, $entityId, $message, $extra_json);
    audit_alert_send($payload);
}
function audit_normalize_module($moduleKey)
{
    if ($moduleKey === null) {
        return null;
    }

    $key = strtolower(trim((string)$moduleKey));

    $map = array(
        'menuutama' => 'menu',
        'menu' => 'menu',
        'mod_menu' => 'menu',
        'user' => 'users',
        'users' => 'users',
        'mod_users' => 'users',
        'identitas' => 'identitas',
        'setting' => 'identitas',
        'konfigurasi' => 'identitas',
        'config' => 'identitas',
    );

    if (isset($map[$key])) {
        return $map[$key];
    }

    return $key;
}

function audit_normalize_action($actOrAction)
{
    if ($actOrAction === null) {
        return null;
    }

    $action = strtolower(trim((string)$actOrAction));

    $map = array(
        'input' => 'CREATE',
        'tambah' => 'CREATE',
        'add' => 'CREATE',
        'create' => 'CREATE',
        'simpan' => 'CREATE',
        'insert' => 'CREATE',
        'edit' => 'UPDATE',
        'ubah' => 'UPDATE',
        'update' => 'UPDATE',
        'hapus' => 'DELETE',
        'delete' => 'DELETE',
        'remove' => 'DELETE',
        'aktif' => 'TOGGLE',
        'nonaktif' => 'TOGGLE',
        'toggle' => 'TOGGLE',
        'publish' => 'PUBLISH',
        'unpublish' => 'UNPUBLISH',
        'sort' => 'REORDER',
        'urut' => 'REORDER',
        'reorder' => 'REORDER',
        'drag' => 'REORDER',
        'bulk_update' => 'BULK_UPDATE',
        'mass_update' => 'BULK_UPDATE',
        'bulk_delete' => 'BULK_DELETE',
        'mass_delete' => 'BULK_DELETE',
        'login_bruteforce' => 'LOGIN_BRUTEFORCE',
        'login_blocked' => 'LOGIN_BLOCKED',
    );

    if (isset($map[$action])) {
        return $map[$action];
    }

    $upper = strtoupper($action);

    $standard = array(
        'CREATE',
        'UPDATE',
        'DELETE',
        'TOGGLE',
        'PUBLISH',
        'UNPUBLISH',
        'REORDER',
        'SAVE_SORT',
        'BULK_UPDATE',
        'BULK_DELETE',
        'LOGIN_OK',
        'LOGIN_FAIL',
        'LOGOUT',
        'LOGIN',
        'LOGIN_BRUTEFORCE',
        'LOGIN_BLOCKED',
    );

    if (in_array($upper, $standard, true)) {
        return $upper;
    }

    return $upper;
}

function audit_should_log($moduleKey, $action)
{
    global $auditPolicy;

    $module = audit_normalize_module($moduleKey);
    $normalizedAction = audit_normalize_action($action);

    if (!is_array($auditPolicy)) {
        return true;
    }

    $alwaysActions = isset($auditPolicy['always_actions']) && is_array($auditPolicy['always_actions'])
        ? $auditPolicy['always_actions']
        : array();

    if ($normalizedAction !== null && in_array($normalizedAction, $alwaysActions, true)) {
        return true;
    }

    $modules = isset($auditPolicy['modules']) && is_array($auditPolicy['modules'])
        ? $auditPolicy['modules']
        : array();

    if ($module !== null && isset($modules[$module])) {
        $moduleActions = isset($modules[$module]['log_actions']) && is_array($modules[$module]['log_actions'])
            ? $modules[$module]['log_actions']
            : array();

        return $normalizedAction !== null && in_array($normalizedAction, $moduleActions, true);
    }

    $defaultActions = isset($auditPolicy['default']['log_actions']) && is_array($auditPolicy['default']['log_actions'])
        ? $auditPolicy['default']['log_actions']
        : array();

    if ($normalizedAction !== null && in_array($normalizedAction, $defaultActions, true)) {
        return true;
    }

    return false;
}

function audit_event($moduleKey, $action, $entity, $entityId, $message, $beforeArr, $afterArr, $extraArr)
{
    $module = audit_normalize_module($moduleKey);
    $normalizedAction = audit_normalize_action($action);

    if (!audit_should_log($module, $normalizedAction)) {
        return false;
    }

    $safeExtra = is_array($extraArr) ? audit_log_sanitize_post($extraArr) : array();

    try {
        audit_log_write($normalizedAction, $module, $entity, $entityId, $message, $beforeArr, $afterArr, $safeExtra);
        audit_alert_maybe($module, $normalizedAction, $entity, $entityId, $message, $safeExtra);
        return true;
    } catch (Throwable $e) {
        error_log('audit_event failed: ' . $e->getMessage());
        return false;
    }
}

function audit_log_write($action, $module, $entity, $entity_id, $message, $before_arr, $after_arr, $extra_arr)
{
    global $dbconnection;

    $created_at = date('Y-m-d H:i:s');
    $username   = isset($_SESSION['namauser']) ? substr((string)$_SESSION['namauser'], 0, 100) : null;
    $user_level = isset($_SESSION['leveluser']) ? substr((string)$_SESSION['leveluser'], 0, 50) : null;
    $ip         = isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'], 0, 45) : null;
    $ua         = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
    $url        = isset($_SERVER['REQUEST_URI']) ? substr((string)$_SERVER['REQUEST_URI'], 0, 255) : null;

    $action     = substr((string)$action, 0, 30);
    $module     = $module !== null ? substr((string)$module, 0, 100) : null;
    $entity     = $entity !== null ? substr((string)$entity, 0, 100) : null;
    $entity_id  = $entity_id !== null ? substr((string)$entity_id, 0, 100) : null;
    $message    = $message !== null ? substr((string)$message, 0, 255) : null;

    $extra_clean = is_array($extra_arr) ? audit_log_sanitize_post($extra_arr) : array();
    $before_json = audit_log_encode_json($before_arr);
    $after_json  = audit_log_encode_json($after_arr);
    $extra_json  = audit_log_encode_json($extra_clean);

    try {
        if (!isset($dbconnection) || !($dbconnection instanceof mysqli)) {
            if (class_exists('mysqli') && isset($GLOBALS['dbhost'], $GLOBALS['dbuser'], $GLOBALS['dbpassword'], $GLOBALS['dbname'])) {
                $tmp_conn = @new mysqli($GLOBALS['dbhost'], $GLOBALS['dbuser'], $GLOBALS['dbpassword'], $GLOBALS['dbname']);
                if ($tmp_conn && !$tmp_conn->connect_error) {
                    $dbconnection = $tmp_conn;
                }
            }
        }

        if (!isset($dbconnection) || !($dbconnection instanceof mysqli) || $dbconnection->connect_error) {
            return;
        }

        $stmt = @$dbconnection->prepare("
            INSERT INTO audit_log
                (created_at, username, user_level, module, action, entity, entity_id, message, ip_address, user_agent, url, before_json, after_json, extra_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt === false) {
            return;
        }

        $stmt->bind_param(
            "ssssssssssssss",
            $created_at,
            $username,
            $user_level,
            $module,
            $action,
            $entity,
            $entity_id,
            $message,
            $ip,
            $ua,
            $url,
            $before_json,
            $after_json,
            $extra_json
        );

        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('audit_log_write failed: ' . $e->getMessage());
    }
}
