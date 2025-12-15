<?php
// Brute-force login tracking (file-based, fail-safe)

function login_fail_cache_dir()
{
    $dir = dirname(__DIR__) . '/cache/login_fail';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function login_fail_track($ip, $username)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '.json';
    $now = time();
    $window = 600;

    $state = array(
        'count' => 0,
        'first_ts' => $now,
        'last_ts' => $now,
        'usernames' => array(),
    );

    if (is_file($file)) {
        $raw = @file_get_contents($file);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $state = array_merge($state, $decoded);
            }
        }
    }

    if (!isset($state['first_ts']) || ($now - (int)$state['first_ts']) > $window) {
        $state['count'] = 1;
        $state['first_ts'] = $now;
        $state['usernames'] = array();
    } else {
        $state['count'] = (int)$state['count'] + 1;
    }

    $state['last_ts'] = $now;

    $uname = substr((string)$username, 0, 100);
    if ($uname !== '') {
        if (!isset($state['usernames']) || !is_array($state['usernames'])) {
            $state['usernames'] = array();
        }
        if (!in_array($uname, $state['usernames'], true)) {
            $state['usernames'][] = $uname;
            if (count($state['usernames']) > 10) {
                $state['usernames'] = array_slice($state['usernames'], -10);
            }
        }
    }

    @file_put_contents($file, json_encode($state));
    return $state;
}

function login_fail_reset($ip)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
    $marker = $dir . '/' . md5((string)$ip) . '_logged.txt';
    if (is_file($marker)) {
        @unlink($marker);
    }
}

function login_block_set($ip, $blockSeconds = 900)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '.block';
    $unblockTs = time() + max(0, (int)$blockSeconds);
    $result = @file_put_contents($file, (string)$unblockTs);
    return ($result !== false);
}

function login_block_is_active($ip)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '.block';
    if (!is_file($file)) {
        return 0;
    }
    $unblockTs = (int)@file_get_contents($file);
    $now = time();
    if ($unblockTs > $now) {
        return $unblockTs - $now;
    }
    @unlink($file);
    return 0;
}

function login_block_clear($ip)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '.block';
    if (is_file($file)) {
        @unlink($file);
    }
    $marker = $dir . '/' . md5((string)$ip) . '.block_logged';
    if (is_file($marker)) {
        @unlink($marker);
    }
}

function login_block_recently_logged($ip)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '.block_logged';
    $now = time();
    $window = 900;
    if (is_file($file)) {
        $last = (int)@file_get_contents($file);
        if ($last > 0 && ($now - $last) < $window) {
            return true;
        }
    }
    return false;
}

function login_block_mark_logged($ip)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '.block_logged';
    @file_put_contents($file, (string)time());
}

function login_fail_get_count($ip, $windowSeconds = 600)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '.json';
    $now = time();
    $window = max(1, (int)$windowSeconds);
    if (!is_file($file)) {
        return 0;
    }
    $raw = @file_get_contents($file);
    if ($raw === false) {
        return 0;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return 0;
    }
    $firstTs = isset($decoded['first_ts']) ? (int)$decoded['first_ts'] : 0;
    $count   = isset($decoded['count']) ? (int)$decoded['count'] : 0;
    if ($count > 0 && $firstTs > 0 && ($now - $firstTs) <= $window) {
        return $count;
    }
    return 0;
}

function login_throttle_delay_seconds($failCount)
{
    $c = (int)$failCount;
    if ($c <= 1) return 0;
    if ($c === 2) return 1;
    if ($c === 3) return 2;
    if ($c === 4) return 4;
    return 8; // cap for 5+
}

function login_throttle_sleep($seconds)
{
    $s = (float)$seconds;
    if ($s <= 0) {
        return;
    }
    if ($s >= 1) {
        sleep((int)$s);
    } else {
        usleep((int)($s * 1000000));
    }
}

function login_fail_is_bruteforce($state)
{
    if (!is_array($state)) {
        return false;
    }
    $window = 600;
    $now = time();
    $first = isset($state['first_ts']) ? (int)$state['first_ts'] : 0;
    $count = isset($state['count']) ? (int)$state['count'] : 0;

    if ($count >= 5 && $first > 0 && ($now - $first) <= $window) {
        return true;
    }
    return false;
}

function login_fail_recently_logged($ip)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '_logged.txt';
    $now = time();
    $window = 600;
    if (is_file($file)) {
        $last = (int)@file_get_contents($file);
        if ($last > 0 && ($now - $last) < $window) {
            return true;
        }
    }
    return false;
}

function login_fail_mark_logged($ip)
{
    $dir = login_fail_cache_dir();
    $file = $dir . '/' . md5((string)$ip) . '_logged.txt';
    @file_put_contents($file, (string)time());
}
