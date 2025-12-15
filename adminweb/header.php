<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$isAdmin = isset($_SESSION['leveluser']) && $_SESSION['leveluser'] === 'admin';

$ambiliden = querydb("SELECT * FROM identitas LIMIT 1");
$tdin	   = $ambiliden->fetch_array();
$favicon   = '/' . ltrim($tdin['favicon'], '/');
$pendingApprovalCount = 0;
if ($isAdmin) {
    $pending = querydb("
        SELECT
          (SELECT COUNT(*) FROM berita_revisions WHERE status = 'PENDING') +
          (SELECT COUNT(*) FROM pengumuman_revisions WHERE status = 'PENDING') +
          (SELECT COUNT(*) FROM agenda_revisions WHERE status = 'PENDING') AS total_pending
    ");
    if ($pending) {
        $row = $pending->fetch_array();
        $pendingApprovalCount = isset($row['total_pending']) ? (int)$row['total_pending'] : 0;
    }
}
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
    <title>Administrator <?php echo $tdin['nama_pemilik']; ?></title>
	<link rel="shortcut icon" href="<?php echo $favicon; ?>" />
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="/adminweb/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/adminweb/bootstrap/css/font-awesome.min.css">
    <link rel="stylesheet" href="/adminweb/plugins/daterangepicker/daterangepicker-bs3.css">
    <link rel="stylesheet" href="/adminweb/plugins/iCheck/all.css">
    <link rel="stylesheet" href="/adminweb/plugins/datatables/dataTables.bootstrap.css">
    <link rel="stylesheet" href="/adminweb/plugins/select2/select2.min.css">
    <link rel="stylesheet" href="/adminweb/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="/adminweb/dist/css/skins/_all-skins.min.css">
    <link rel="stylesheet" href="/adminweb/dist/css/yn-toggle.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        @media (max-width: 767px) {
          #dropmen{background-color: #000000;}
        }
    </style>
  </head>
  <body class="hold-transition skin-green-light sidebar-mini">
    <?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
      <div id="dev-badge">DEV MODE</div>
    <?php endif; ?>

    <div class="wrapper">
      <header class="main-header">
		<a href="/admin" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
          <span class="logo-mini">PA</span>
		  <span class="logo-lg pull-center"><b>PANEL-ADMIN</b></span>
      </a>
        <nav class="navbar navbar-static-top" role="navigation">
          <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
		  <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
              <li class="dropdown user user-menu">
                <a href="../" target="_blank" title="Lihat Website"><i class="fa fa-laptop"></i></a>
              </li>
              <?php if ($isAdmin): ?>
              <li>
                <a href="?module=approvalqueue" title="Antrean Persetujuan">
                  <i class="fa fa-bell"></i>
                  <?php if ($pendingApprovalCount > 0): ?>
                    <span class="label label-warning"><?php echo $pendingApprovalCount; ?></span>
                  <?php endif; ?>
                </a>
              </li>
              <?php endif; ?>
			  <li class="dropdown">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Tambah"><i class="fa fa-plus"></i></a>
				<ul class="dropdown-menu" id="dropmen">
          <li><a href="?module=berita&act=tambahberita" title="Tambah Berita">Berita</a></li>
          <li><a href="?module=halamanstatis&act=tambahhalamanstatis" title="Tambah Halaman">Halaman</a></li>
          <li><a href="?module=agenda&act=tambahagenda" title="Agenda">Agenda</a></li>
					<li><a href="?module=pengumuman&act=tambahpengumuman" title="Pengumuman">Pengumuman</a></li>
          <li><a href="?module=album&act=tambahalbum" title="Berita Photo">Berita Photo</a></li>
          <li><a href="?module=galerifoto&act=tambahgalerifoto" title="Tambah Galeri Berita">Galeri Berita</a></li>
          <?php if ($isAdmin): ?>
          <li><a href="?module=download&act=tambahdownload" title="Tambah Download">Download</a></li>
          <?php endif; ?>
				</ul>
			  </li>
              <li class="dropdown user user-menu">
                <a href="#">
					<i class="fa fa-user"></i>
                  <span class="hidden-xs"><?php echo $_SESSION['namalengkap']; ?></span>
                </a>
              </li>
			  <li>
                <a href="/adminweb/logout.php" title="KELUAR"><i class="fa fa-sign-out"></i></a>
              </li>
			  <!--<li>
                <a href="#" data-toggle="control-sidebar" title="Option"><i class="fa fa-gears"></i></a>
              </li>-->
            </ul>
          </div>
        </nav>
      </header>
