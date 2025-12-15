<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/inc/audit_log.php';

$username = isset($_SESSION['namauser']) ? $_SESSION['namauser'] : '';

audit_event('auth', 'LOGOUT', 'user', $username, 'Logout', null, null, ['username' => $username]);

session_destroy();
header('Location: /login');
exit;
?>
