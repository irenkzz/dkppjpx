<?php
// Apabila user belum login
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
}
// Apabila user sudah login dengan benar, maka terbentuklah session
else{
	require_once __DIR__ . '/../../includes/bootstrap.php';

	$isAdmin    = isset($_SESSION['leveluser']) && $_SESSION['leveluser'] === 'admin';
	$today      = date("Y-m-d");
	$yesterday  = date("Y-m-d", strtotime("-1 day"));
	$startRange = date("Y-m-d", strtotime("-6 days"));

	if (!function_exists('dash_fetch_count')) {
		function dash_fetch_count($sql) {
			$res = querydb($sql);
			if (!$res) return 0;
			$row = $res->fetch_assoc();
			return isset($row['total']) ? (int)$row['total'] : 0;
		}
	}

	$totalBerita         = dash_fetch_count("SELECT COUNT(*) AS total FROM berita");
	$totalArtikel        = dash_fetch_count("SELECT COUNT(*) AS total FROM berita WHERE id_kategori = 1");
	$totalAgenda         = dash_fetch_count("SELECT COUNT(*) AS total FROM agenda");
	$totalPengumuman     = dash_fetch_count("SELECT COUNT(*) AS total FROM pengumuman");
	$totalHalaman        = dash_fetch_count("SELECT COUNT(*) AS total FROM halamanstatis");
	$totalAlbumAktif     = dash_fetch_count("SELECT COUNT(*) AS total FROM album WHERE aktif = 'Y'");
	$galleryAktifQuery   = db_column_exists('gallery', 'aktif') ? "SELECT COUNT(*) AS total FROM gallery WHERE aktif = 'Y'" : "SELECT COUNT(*) AS total FROM gallery";
	$totalGalleryAktif   = dash_fetch_count($galleryAktifQuery);
	$totalPesan          = dash_fetch_count("SELECT COUNT(*) AS total FROM hubungi");
	$totalKomentarPending= dash_fetch_count("SELECT COUNT(*) AS total FROM komentar WHERE aktif = 'N'");

	$pengunjungHariIni   = dash_fetch_count("SELECT COUNT(DISTINCT ip) AS total FROM statistik WHERE tanggal = '$today'");
	$pengunjungKemarin   = dash_fetch_count("SELECT COUNT(DISTINCT ip) AS total FROM statistik WHERE tanggal = '$yesterday'");
	$totalPengunjung     = dash_fetch_count("SELECT COUNT(DISTINCT ip) AS total FROM statistik");
	$totalHits           = dash_fetch_count("SELECT COALESCE(SUM(hits),0) AS total FROM statistik");

	$albumNonAktif       = dash_fetch_count("SELECT COUNT(*) AS total FROM album WHERE aktif = 'N'");
	$galeriNonAktif      = db_column_exists('gallery', 'aktif') ? dash_fetch_count("SELECT COUNT(*) AS total FROM gallery WHERE aktif = 'N'") : 0;

	$hubungiHasStatus    = db_column_exists('hubungi', 'dibaca');
	$hubungiUnreadCount  = $hubungiHasStatus ? dash_fetch_count("SELECT COUNT(*) AS total FROM hubungi WHERE dibaca = 'N'") : $totalPesan;
	$hubungiFilterWhere  = $hubungiHasStatus ? "WHERE dibaca = 'N'" : "";

    $pendingBeritaRev     = dash_fetch_count("SELECT COUNT(*) AS total FROM berita_revisions WHERE status = 'PENDING'");
    $pendingPengumumanRev = dash_fetch_count("SELECT COUNT(*) AS total FROM pengumuman_revisions WHERE status = 'PENDING'");
    $pendingAgendaRev     = dash_fetch_count("SELECT COUNT(*) AS total FROM agenda_revisions WHERE status = 'PENDING'");
    $pendingApprovalTotal = $pendingBeritaRev + $pendingPengumumanRev + $pendingAgendaRev;

	$identitasMissing = array();
	$idenRow          = array();
	$idenRes          = querydb("SELECT * FROM identitas LIMIT 1");
	if ($idenRes) {
		$idenRow = $idenRes->fetch_assoc();
	}
	$idenTargets = array(
		'nama_website' => 'Nama Website',
		'email'        => 'Email',
		'logo'         => 'Logo',
		'favicon'      => 'Favicon',
		'telpon'       => 'Telepon',
		'alamat'       => 'Alamat'
	);
	foreach ($idenTargets as $field => $label) {
		if (db_column_exists('identitas', $field)) {
			$val = isset($idenRow[$field]) ? trim($idenRow[$field]) : '';
			if ($val === '' || $val === '-') {
				$identitasMissing[] = $label;
			}
		}
	}

	$recentBerita = array();
	$beritaSql    = "SELECT b.id_berita, b.judul, b.tanggal, b.username, k.nama_kategori, u.nama_lengkap
					 FROM berita b
					 LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
					 LEFT JOIN users u ON b.username = u.username
					 ORDER BY b.id_berita DESC
					 LIMIT 5";
	$beritaRes    = querydb($beritaSql);
	while ($beritaRes && $row = $beritaRes->fetch_assoc()) {
		$recentBerita[] = array(
			'id'       => (int)$row['id_berita'],
			'judul'    => $row['judul'],
			'kategori' => $row['nama_kategori'],
			'tanggal'  => $row['tanggal'],
			'penulis'  => !empty($row['nama_lengkap']) ? $row['nama_lengkap'] : $row['username']
		);
	}

	$recentAgenda = array();
	$agendaSql    = "SELECT id_agenda, tema, tgl_mulai, tgl_selesai, tgl_posting, tempat
					 FROM agenda
					 ORDER BY id_agenda DESC
					 LIMIT 5";
	$agendaRes    = querydb($agendaSql);
	while ($agendaRes && $row = $agendaRes->fetch_assoc()) {
		$recentAgenda[] = array(
			'id'       => (int)$row['id_agenda'],
			'tema'     => $row['tema'],
			'mulai'    => $row['tgl_mulai'],
			'selesai'  => $row['tgl_selesai'],
			'post'     => $row['tgl_posting'],
			'tempat'   => isset($row['tempat']) ? $row['tempat'] : ''
		);
	}

	$recentPengumuman = array();
	$pengumumanSql    = "SELECT id_pengumuman, judul, tgl_posting, username
						  FROM pengumuman
						  ORDER BY id_pengumuman DESC
						  LIMIT 5";
	$pengumumanRes    = querydb($pengumumanSql);
	while ($pengumumanRes && $row = $pengumumanRes->fetch_assoc()) {
		$recentPengumuman[] = array(
			'id'      => (int)$row['id_pengumuman'],
			'judul'   => $row['judul'],
			'tanggal' => $row['tgl_posting'],
			'penulis' => $row['username']
		);
	}

	$recentHubungi = array();
	$hubungiSql    = "SELECT id_hubungi, nama_pengirim, email, subjek, tanggal
					  FROM hubungi $hubungiFilterWhere
					  ORDER BY id_hubungi DESC
					  LIMIT 5";
	$hubungiRes    = querydb($hubungiSql);
	while ($hubungiRes && $row = $hubungiRes->fetch_assoc()) {
		$recentHubungi[] = array(
			'id'      => (int)$row['id_hubungi'],
			'nama'    => $row['nama_pengirim'],
			'email'   => $row['email'],
			'subjek'  => $row['subjek'],
			'tanggal' => $row['tanggal']
		);
	}

	$statistikMap   = array();
	$statistikRows  = querydb("
		SELECT tanggal, COUNT(DISTINCT ip) AS visitors, COALESCE(SUM(hits),0) AS hits
		FROM statistik
		WHERE tanggal >= '$startRange'
		GROUP BY tanggal
		ORDER BY tanggal ASC
	");
	while ($statistikRows && $row = $statistikRows->fetch_assoc()) {
		$statistikMap[$row['tanggal']] = array(
			'visitors' => (int)$row['visitors'],
			'hits'     => (int)$row['hits']
		);
	}

	$chartLabels    = array();
	$chartVisitors  = array();
	$chartHits      = array();
	$statistikTable = array();
	for ($i = 6; $i >= 0; $i--) {
		$dt      = date("Y-m-d", strtotime("-$i days"));
		$label   = date("d M", strtotime($dt));
		$vis     = isset($statistikMap[$dt]['visitors']) ? $statistikMap[$dt]['visitors'] : 0;
		$hit     = isset($statistikMap[$dt]['hits']) ? $statistikMap[$dt]['hits'] : 0;
		$chartLabels[]   = $label;
		$chartVisitors[] = $vis;
		$chartHits[]     = $hit;
		$statistikTable[] = array(
			'label'    => $label,
			'visitors' => $vis,
			'hits'     => $hit
		);
	}
	?>
	<section class="content-header">
		<h1>Beranda</h1>
		<small>Gambaran cepat konten dan kesehatan website</small>
	</section>
	
	<section class="content">
		<div class="callout callout-info">
			<p>Hai, <strong><?php echo e($_SESSION['namalengkap'] ?? ''); ?></strong>. Anda login tanggal <strong><?php echo tgl_indo($today); ?></strong>. Panduan pengelolaan bisa <a href="../document/Buku_Panduan_Pengelolaan_Website.pdf" target="_blank">diunduh di sini</a>.</p>
		</div>

		<div class="row">
			<?php
			$kpiTiles = array(
                array('title' => 'Revisi Pending',       'value' => $pendingApprovalTotal, 'desc' => 'Menunggu persetujuan admin', 'icon' => 'fa-bell',        'color' => 'bg-yellow', 'adminOnly' => true, 'link' => '?module=approvalqueue'),
                array('title' => 'Pending Berita',       'value' => $pendingBeritaRev,     'desc' => 'Revisi berita butuh review', 'icon' => 'fa-newspaper-o','color' => 'bg-orange', 'adminOnly' => true, 'link' => '?module=approvalqueue&type=berita'),
                array('title' => 'Pending Pengumuman',   'value' => $pendingPengumumanRev, 'desc' => 'Menunggu persetujuan',       'icon' => 'fa-bullhorn',   'color' => 'bg-orange', 'adminOnly' => true, 'link' => '?module=approvalqueue&type=pengumuman'),
                array('title' => 'Pending Agenda',       'value' => $pendingAgendaRev,     'desc' => 'Revisi agenda menunggu',     'icon' => 'fa-calendar',    'color' => 'bg-orange', 'adminOnly' => true, 'link' => '?module=approvalqueue&type=agenda'),
				array('title' => 'Total Berita',           'value' => $totalBerita,          'desc' => 'Semua berita yang terpublikasi',      'icon' => 'fa-newspaper-o',   'color' => 'bg-green'),
				array('title' => 'Artikel (Kategori 1)',   'value' => $totalArtikel,         'desc' => 'Konten pada kategori ID 1',           'icon' => 'fa-file-text-o',   'color' => 'bg-aqua'),
				array('title' => 'Agenda',                 'value' => $totalAgenda,          'desc' => 'Jadwal & acara',                       'icon' => 'fa-calendar',      'color' => 'bg-blue'),
				array('title' => 'Pengumuman',             'value' => $totalPengumuman,      'desc' => 'Informasi resmi',                      'icon' => 'fa-bullhorn',      'color' => 'bg-purple'),
				array('title' => 'Halaman Statis',         'value' => $totalHalaman,         'desc' => 'Profil & halaman layanan',             'icon' => 'fa-file',          'color' => 'bg-teal'),
				array('title' => 'Album Aktif',            'value' => $totalAlbumAktif,      'desc' => 'Album dengan status aktif',            'icon' => 'fa-book',          'color' => 'bg-olive'),
				array('title' => 'Galeri Foto Aktif',      'value' => $totalGalleryAktif,    'desc' => 'Foto siap tampil di galeri',           'icon' => 'fa-camera',        'color' => 'bg-maroon'),
				array('title' => 'Pesan Masuk',            'value' => $totalPesan,           'desc' => 'Semua pesan kontak',                   'icon' => 'fa-envelope',      'color' => 'bg-orange', 'adminOnly' => true),
				array('title' => 'Komentar Pending',       'value' => $totalKomentarPending, 'desc' => 'Perlu moderasi',                        'icon' => 'fa-comments',      'color' => 'bg-yellow', 'adminOnly' => true),
				array('title' => 'Pengunjung Hari Ini',    'value' => $pengunjungHariIni,    'desc' => 'Kemarin: '.$pengunjungKemarin.' | Total: '.$totalPengunjung.' pengunjung, '.$totalHits.' hits', 'icon' => 'fa-users', 'color' => 'bg-navy', 'adminOnly' => true),
			);

			foreach ($kpiTiles as $tile) {
				if (!empty($tile['adminOnly']) && !$isAdmin) {
					continue;
				}
				?>
				<div class="col-lg-3 col-md-4 col-sm-6">
                    <?php if (!empty($tile['link'])): ?><a href="<?php echo safe_url($tile['link']); ?>" style="color:inherit;"><?php endif; ?>
					<div class="info-box">
						<span class="info-box-icon <?php echo $tile['color']; ?>"><i class="fa <?php echo $tile['icon']; ?>"></i></span>
						<div class="info-box-content">
							<span class="info-box-text"><?php echo e($tile['title']); ?></span>
							<span class="info-box-number"><?php echo number_format($tile['value']); ?></span>
							<span class="progress-description"><?php echo e($tile['desc']); ?></span>
						</div>
					</div>
                    <?php if (!empty($tile['link'])): ?></a><?php endif; ?>
				</div>
				<?php
			}
			?>
		</div>

		<div class="row">
			<div class="col-md-6">
				<div class="box box-primary">
					<div class="box-header with-border">
						<h3 class="box-title">Berita Terbaru</h3>
						<div class="box-tools pull-right"><a class="btn btn-box-tool" href="?module=berita"><i class="fa fa-arrow-right"></i></a></div>
					</div>
					<div class="box-body no-padding">
						<ul class="products-list product-list-in-box">
							<?php if (empty($recentBerita)): ?>
								<li class="item"><div class="product-info">Belum ada berita.</div></li>
							<?php else: foreach ($recentBerita as $item): ?>
								<li class="item">
									<div class="product-info">
										<a class="product-title" href="?module=berita&act=editberita&id=<?php echo $item['id']; ?>"><?php echo e($item['judul']); ?></a>
										<span class="label label-primary pull-right"><?php echo e($item['kategori']); ?></span>
										<span class="product-description">
											<?php echo tgl_indo($item['tanggal']); ?> &middot; <?php echo e($item['penulis']); ?>
										</span>
									</div>
								</li>
							<?php endforeach; endif; ?>
						</ul>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="box box-success">
					<div class="box-header with-border">
						<h3 class="box-title">Agenda Terbaru</h3>
						<div class="box-tools pull-right"><a class="btn btn-box-tool" href="?module=agenda"><i class="fa fa-arrow-right"></i></a></div>
					</div>
					<div class="box-body no-padding">
						<ul class="todo-list">
							<?php if (empty($recentAgenda)): ?>
								<li>Tidak ada agenda.</li>
							<?php else: foreach ($recentAgenda as $item): ?>
								<li>
									<span class="text"><?php echo e($item['tema']); ?></span>
									<small class="label label-info"><i class="fa fa-calendar"></i> <?php echo tgl_indo($item['mulai']); ?><?php echo $item['mulai'] != $item['selesai'] ? ' - '.tgl_indo($item['selesai']) : ''; ?></small>
									<div class="pull-right">
										<span class="label label-default"><?php echo tgl_indo($item['post']); ?></span>
									</div>
								</li>
							<?php endforeach; endif; ?>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-6">
				<div class="box box-info">
					<div class="box-header with-border">
						<h3 class="box-title">Pengumuman Terbaru</h3>
						<div class="box-tools pull-right"><a class="btn btn-box-tool" href="?module=pengumuman"><i class="fa fa-arrow-right"></i></a></div>
					</div>
					<div class="box-body table-responsive no-padding">
						<table class="table table-hover">
							<thead>
								<tr><th>Judul</th><th>Penulis</th><th>Tanggal</th></tr>
							</thead>
							<tbody>
							<?php if (empty($recentPengumuman)): ?>
								<tr><td colspan="3">Belum ada pengumuman.</td></tr>
							<?php else: foreach ($recentPengumuman as $item): ?>
								<tr>
									<td><a href="?module=pengumuman&act=editpengumuman&id=<?php echo $item['id']; ?>"><?php echo e($item['judul']); ?></a></td>
									<td><?php echo e($item['penulis']); ?></td>
									<td><?php echo tgl_indo($item['tanggal']); ?></td>
								</tr>
							<?php endforeach; endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<?php if ($isAdmin): ?>
			<div class="col-md-6">
				<div class="box box-warning">
					<div class="box-header with-border">
						<h3 class="box-title">Pesan Kontak (Belum Dibaca)</h3>
						<div class="box-tools pull-right"><a class="btn btn-box-tool" href="?module=hubungi"><i class="fa fa-arrow-right"></i></a></div>
					</div>
					<div class="box-body table-responsive no-padding">
						<table class="table table-striped">
							<thead>
								<tr><th>Nama</th><th>Subjek</th><th>Tanggal</th></tr>
							</thead>
							<tbody>
							<?php if (empty($recentHubungi)): ?>
								<tr><td colspan="3">Belum ada pesan.</td></tr>
							<?php else: foreach ($recentHubungi as $item): ?>
								<tr>
									<td><?php echo e($item['nama']); ?></td>
									<td><?php echo e($item['subjek']); ?></td>
									<td><?php echo tgl_indo($item['tanggal']); ?></td>
								</tr>
							<?php endforeach; endif; ?>
							</tbody>
						</table>
						<?php if (!$hubungiHasStatus): ?>
							<p class="text-muted" style="margin:10px 0 0 10px;">Penanda baca belum tersedia di tabel, menampilkan pesan terbaru.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<?php if ($isAdmin): ?>
		<div class="row">
			<div class="col-md-6">
				<div class="box box-danger">
					<div class="box-header with-border">
						<h3 class="box-title">Perlu Perhatian</h3>
					</div>
					<div class="box-body">
						<ul class="list-unstyled">
							<li><i class="fa fa-comments text-yellow"></i> Komentar pending: <strong><?php echo number_format($totalKomentarPending); ?></strong></li>
							<li><i class="fa fa-envelope text-orange"></i> Pesan belum dibaca: <strong><?php echo number_format($hubungiUnreadCount); ?></strong></li>
							<li><i class="fa fa-book text-maroon"></i> Album non-aktif: <strong><?php echo number_format($albumNonAktif); ?></strong></li>
							<li><i class="fa fa-camera text-maroon"></i> Galeri non-aktif: <strong><?php echo number_format($galeriNonAktif); ?></strong></li>
							<li><i class="fa fa-cog text-red"></i> Identitas belum lengkap:
								<?php
								if (empty($identitasMissing)) {
									echo " <span class=\"text-green\">Sudah lengkap</span>";
								} else {
									echo " <strong>" . e(implode(', ', $identitasMissing)) . "</strong>";
								}
								?>
							</li>
						</ul>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="box box-default">
					<div class="box-header with-border">
						<h3 class="box-title">Quick Actions</h3>
					</div>
					<div class="box-body">
						<div class="row" style="margin-bottom:10px;">
							<div class="col-sm-6"><a class="btn btn-block btn-success" href="?module=berita&act=tambahberita"><i class="fa fa-plus"></i> Tambah Berita</a></div>
							<div class="col-sm-6"><a class="btn btn-block btn-primary" href="?module=agenda&act=tambahagenda"><i class="fa fa-calendar"></i> Tambah Agenda</a></div>
						</div>
						<div class="row" style="margin-bottom:10px;">
							<div class="col-sm-6"><a class="btn btn-block btn-warning" href="?module=album&act=tambahalbum"><i class="fa fa-book"></i> Tambah Album</a></div>
							<div class="col-sm-6"><a class="btn btn-block btn-info" href="?module=galerifoto&act=tambahgalerifoto"><i class="fa fa-cloud-upload"></i> Upload Foto</a></div>
						</div>
						<div class="row">
							<div class="col-sm-6"><a class="btn btn-block btn-default" href="?module=halamanstatis&act=tambahhalamanstatis"><i class="fa fa-file-text-o"></i> Halaman Statis</a></div>
							<div class="col-sm-6"><a class="btn btn-block btn-default" href="?module=identitas"><i class="fa fa-cog"></i> Setting Website</a></div>
						</div>
						<div class="row" style="margin-top:10px;">
							<div class="col-sm-6"><a class="btn btn-block btn-default" href="?module=menu"><i class="fa fa-sitemap"></i> Manajemen Menu</a></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php else: ?>
		<div class="box box-default">
			<div class="box-header with-border">
				<h3 class="box-title">Quick Actions</h3>
			</div>
			<div class="box-body">
				<div class="row" style="margin-bottom:10px;">
					<div class="col-sm-4"><a class="btn btn-block btn-success" href="?module=berita&act=tambahberita"><i class="fa fa-plus"></i> Tambah Berita</a></div>
					<div class="col-sm-4"><a class="btn btn-block btn-primary" href="?module=agenda&act=tambahagenda"><i class="fa fa-calendar"></i> Tambah Agenda</a></div>
					<div class="col-sm-4"><a class="btn btn-block btn-warning" href="?module=pengumuman&act=tambahpengumuman"><i class="fa fa-bullhorn"></i> Tambah Pengumuman</a></div>
				</div>
				<div class="row">
					<div class="col-sm-4"><a class="btn btn-block btn-info" href="?module=album&act=tambahalbum"><i class="fa fa-book"></i> Tambah Album</a></div>
					<div class="col-sm-4"><a class="btn btn-block btn-info" href="?module=galerifoto&act=tambahgalerifoto"><i class="fa fa-cloud-upload"></i> Upload Foto</a></div>
					<div class="col-sm-4"><a class="btn btn-block btn-default" href="?module=halamanstatis&act=tambahhalamanstatis"><i class="fa fa-file-text-o"></i> Halaman Statis</a></div>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<?php if ($isAdmin): ?>
		<div class="box box-info">
			<div class="box-header with-border">
				<h3 class="box-title">Analitik Pengunjung (7 Hari Terakhir)</h3>
			</div>
			<div class="box-body">
				<div class="row">
					<div class="col-md-8">
						<canvas id="visitorsChart" style="height:250px;"></canvas>
					</div>
					<div class="col-md-4">
						<table class="table table-condensed table-striped">
							<thead>
								<tr><th>Tanggal</th><th class="text-center">Pengunjung</th><th class="text-center">Hits</th></tr>
							</thead>
							<tbody>
								<?php foreach ($statistikTable as $row): ?>
									<tr>
										<td><?php echo e($row['label']); ?></td>
										<td class="text-center"><?php echo number_format($row['visitors']); ?></td>
										<td class="text-center"><?php echo number_format($row['hits']); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<script>
			window.dashboardChart = {
				labels: <?php echo json_encode($chartLabels); ?>,
				visitors: <?php echo json_encode($chartVisitors); ?>,
				hits: <?php echo json_encode($chartHits); ?>
			};
		</script>
		<?php endif; ?>
	</section>
<?php
}
?>
