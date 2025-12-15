<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['namauser']) && isset($_SESSION['passuser'])) {
    header('Location: /admin');
    exit;
}

$loginError = isset($_GET['error']) ? (int)$_GET['error'] : 0;
$blockedSeconds = isset($_GET['blocked']) ? max(0, (int)$_GET['blocked']) : 0;

?>

<!DOCTYPE html>
<html>
<head>
    <?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
        <style>
        #dev-badge{position:fixed;top:12px;right:12px;background:#d32f2f;color:#fff;font-size:12px;font-weight:700;padding:6px 10px;border-radius:6px;z-index:99999;pointer-events:none;box-shadow:0 2px 6px rgba(0,0,0,.35)}
        </style>
    <?php endif; ?>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Login Administrator</title>

    <!-- CSS FIXED -->
    <link rel="stylesheet" href="/adminweb/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/adminweb/bootstrap/css/font-awesome.min.css">
    <link rel="stylesheet" href="/adminweb/dist/css/AdminLTE.min.css">

    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
</head>

<body class="login-page">
<?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
<div id="dev-badge">DEV MODE</div>
<?php endif; ?>

<div class="login-box">
    <div class="login-logo">
        <img src="/adminweb/jayapura.jpg" width="30%">
        <br>
        <b>Administrator</b>
    </div>

    <div class="login-box-body">
        <?php if ($loginError === 1): ?>
            <div class="alert alert-danger" role="alert">
                Username atau password salah.
            </div>
        <?php endif; ?>
        <?php if ($blockedSeconds > 0): ?>
            <div class="alert alert-warning" role="alert" id="block-message">
                Terlalu banyak percobaan login gagal. Silakan coba lagi dalam <span id="block-timer"></span>.
            </div>
        <?php endif; ?>
        <form id="login-form" action="/adminweb/cek_login.php" method="post">
            <div class="form-group has-feedback">
                <input type="text" name="username" class="form-control" placeholder="Username" required <?php echo $blockedSeconds > 0 ? 'disabled' : ''; ?>>
            </div>

            <div class="form-group has-feedback">
                <input type="password" name="password" class="form-control" placeholder="Password" required <?php echo $blockedSeconds > 0 ? 'disabled' : ''; ?>>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <button type="submit" class="btn btn-primary btn-block btn-flat" <?php echo $blockedSeconds > 0 ? 'disabled' : ''; ?>>LOGIN</button>
                </div>
            </div>
        </form>
    </div>

    <p style="text-align:center;margin-top:10px;">
        Copyright © 2025 DKPP Kota Jayapura. All rights reserved.
    </p>
</div>

<!-- JS FIXED -->
<script src="/adminweb/plugins/jQuery/jQuery-2.1.4.min.js"></script>
<script src="/adminweb/bootstrap/js/bootstrap.min.js"></script>
<?php if ($blockedSeconds > 0): ?>
<script>
(function() {
    var remaining = <?php echo (int)$blockedSeconds; ?>;
    var timerEl = document.getElementById('block-timer');
    var form = document.getElementById('login-form');
    var inputs = form ? form.querySelectorAll('input, button') : [];

    function setDisabled(disabled) {
        if (!inputs) return;
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].disabled = disabled;
        }
    }

    function formatTime(sec) {
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        if (m > 0) {
            return m + ' menit ' + s + ' detik';
        }
        return s + ' detik';
    }

    function tick() {
        if (!timerEl) return;
        if (remaining <= 0) {
            timerEl.textContent = '0 detik';
            setDisabled(false);
            return;
        }
        timerEl.textContent = formatTime(remaining);
        remaining--;
        setTimeout(tick, 1000);
    }

    setDisabled(true);
    tick();
})();
</script>
<?php endif; ?>

</body>
</html>
