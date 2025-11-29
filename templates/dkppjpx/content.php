<?php 
if (!function_exists('e')) {
    function e(?string $s): string {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('safe_url')) {
    /**
     * Sanitize URLs before placing them into href/src.
     * Allows http/https/mailto and rejects protocol-relative / javascript URLs.
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

$ambiliden=querydb("SELECT * FROM identitas LIMIT 1");
$tiden=$ambiliden->fetch_array();

function konversi_tanggal($format, $tanggal = "now", $bahasa = "id"): string
{
	
    // Map untuk berbagai bahasa
    $translations = [
        'id' => [
            // Hari (pendek)
            'Sun' => 'Minggu',
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',

            // Hari (panjang)
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',

            // Bulan (pendek, sesuai keluaran date("M"))
            'Jan' => 'Jan',
            'Feb' => 'Feb',
            'Mar' => 'Mar',
            'Apr' => 'Apr',
            'May' => 'Mei',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Agu',
            'Sep' => 'Sep',
            'Oct' => 'Okt',
            'Nov' => 'Nov',
            'Dec' => 'Des',

            // Bulan (panjang, untuk format "F")
            'January'   => 'Januari',
            'February'  => 'Februari',
            'March'     => 'Maret',
            'April'     => 'April',
            'May_long'  => 'Mei',       // trik kecil, lihat di bawah
            'June'      => 'Juni',
            'July'      => 'Juli',
            'August'    => 'Agustus',
            'September' => 'September',
            'October'   => 'Oktober',
            'November'  => 'November',
            'December'  => 'Desember',
        ],
        // Tambah bahasa lain di sini misalnya 'en', 'jp', etc.
    ];

    // Ubah $tanggal ke timestamp
    if (is_numeric($tanggal)) {
        $timestamp = (int) $tanggal;
    } else {
        $timestamp = strtotime($tanggal);
    }

    if ($timestamp === false) {
        // tanggal tidak valid, bisa juga return "" atau throw exception
        return '';
    }

    $result = date($format, $timestamp);

    // Kalau bahasa tidak dikenali, langsung return original
    if (!isset($translations[$bahasa])) {
        return $result;
    }

    $map = $translations[$bahasa];

    /**
     * Trik kecil:
     * "May" bisa muncul sebagai:
     *   - "May" (pendek) -> sudah di-map di atas
     *   - "May" (panjang / F) -> tetap sama, tapi kita ingin "Mei"
     * Untuk menghindari tabrakan key di array asosiatif, kita buat format khusus:
     *   - Di string asli, kita ganti dulu "May" (bulan panjang) menjadi "May_long"
     *   - Setelah itu strtr dengan mapping di atas (yang punya key 'May_long')
     */
    if (strpos($format, 'F') !== false) {
        // hanya kalau ada F di format
        $result = str_replace('May', 'May_long', $result);
    }

    // Ganti semua kata Inggris dengan padanan bahasa yang dipilih
    $result = strtr($result, $map);

    return $result;
}

function artikelTerkait(int $id, int $threshold = 40, int $maksArtikel = 5): string
{
    // Baca judul acuan
    $hasil = querydb_prepared(
        "SELECT judul FROM berita WHERE id_berita = ?",
        "i",
        [$id]
    );

    $data = $hasil ? $hasil->fetch_array() : null;
    if (!$data) {
        return "<p><center>Tidak ada artikel terkait</center></p>";
    }

    $judulAcuan   = $data['judul'] ?? '';
    $listArtikel  = [];

    // Baca berita lain
    $hasil = querydb_prepared(
        "SELECT id_berita, judul, judul_seo FROM berita WHERE id_berita <> ?",
        "i",
        [$id]
    );

    while ($row = $hasil->fetch_array()) {
        $judul    = $row['judul'] ?? '';
        $judulSeo = $row['judul_seo'] ?? '';
        $idBerita = (int)($row['id_berita'] ?? 0);

        similar_text($judulAcuan, $judul, $percent);

        if ($percent >= $threshold && count($listArtikel) < $maksArtikel) {
            $safeTitle = e($judul);
            $safeSlug  = rawurlencode($judulSeo);
            $url       = "baca-berita-{$idBerita}-{$safeSlug}.html";

            $listArtikel[] = "
                <a href='{$url}' class='list-group-item'>
                    <h4 class='list-group-item-heading'>{$safeTitle}</h4>
                </a>
            ";
        }
    }

    if (!$listArtikel) {
        return "<p><center>Tidak ada artikel terkait</center></p>";
    }

    return "<div class='list-group margin-top-0'>"
         . implode("\n", $listArtikel)
         . "</div>";
}

function clamp_page($raw, int $default = 1): int
{
    $page = (int)($raw ?? $default);
    return $page < 1 ? $default : $page;
}

function build_seo_url(string $prefix, int $id, string $slug): string
{
    return $prefix . $id . '-' . rawurlencode($slug) . '.html';
}

$module = isset($_GET['module']) ? trim((string) $_GET['module']) : '';

// MODUL BERANDA				
if ($module=='home'){
 ?>
<section class="site-section banner-atas-default hidden-xs hidden-sm">

	<div class="margin-bottom-100 visible-xs">
		<div class="clearfix"></div>
	</div>
	<div id="bootstrap-touch-slider" class="carousel bs-slider fade control-round indicators-line" data-ride="carousel" data-interval="4500" >

		<!-- Indicators -->
		
		<!-- Wrapper For Slides -->
		<div class="carousel-inner" role="listbox">
			<?php
			$no=1;
			$slider=querydb("SELECT * FROM slider ORDER BY id_slider DESC");
			while($tsd=$slider->fetch_array()){
			?>
			<!-- Start of Slide -->
			<div class="item <?php if($no==1){echo "active";} ?>">
			<a href="<?php echo safe_url($tsd['link']); ?>" target="_blank">
    			<!-- Slide Background -->
				<img src="./foto_slider/<?php echo e($tsd['gmb_slider']); ?>" alt="" class="slide-image"/>
				<!-- <div class="bs-slider-overlay"></div> -->

				<div class="container">
					<div class="row">
					</div>
				</div>
				</a>
			</div>
			<!-- End of Slide -->
			<?php $no++; } ?>
		</div>
		<!-- End of Wrapper For Slides -->
		
		<!-- Left Control -->
		<a class="left carousel-control" href="#bootstrap-touch-slider" role="button" data-slide="prev">
			<span class="fa fa-angle-left" aria-hidden="true"></span>
			<span class="sr-only">Previous</span>
		</a>

		<!-- Right Control -->
		<a class="right carousel-control" href="#bootstrap-touch-slider" role="button" data-slide="next">
			<span class="fa fa-angle-right" aria-hidden="true"></span>
			<span class="sr-only">Next</span>
		</a>
	</div><!-- End  bootstrap-touch-slider Slider -->
</section>
<section class="site-section no-margin padding-bottom-15 running-section hidden-xs hidden-sm">
	<div class="container paddingnya margin-top-10">
		<div class="row">
			<div class="col-md-3 no-padding">
				<?php 
				$ambiliden=querydb("SELECT * FROM identitas LIMIT 1");
				$tiden=$ambiliden->fetch_array();
				?>
				<img class="foto-bupati" src="images/<?php echo e($tiden['fopim']); ?>">
			</div>
			<div class="col-md-9 padding-right-0">
				<ul class="indicators-list slider-kecil margin-left-20 margin-right-10">
					<?php 
					$no=0;
					$listslide=querydb("SELECT * FROM listslider ORDER BY nama_menu ASC");
					while($tlist=$listslide->fetch_array()){
						$awback=array("","pink-box","yellow-box","green-box");
						$awicon=array("#0FA3DB","#C80974","#F18F21","#10D494");
						if($no>3){
							$no=0;
						}
					?>
					<li class="<?php echo $awback[$no]; ?> margin-top-0 margin-bottom-40">
						<a class="pie margin-top-20 margin-bottom-10" href="<?php echo safe_url($tlist['link']); ?>" target="_blank">
						<i class="fa fa-folder-open icon-circle icon-bordered fa-primary ikon-kecil" style="color: <?php echo $awicon[$no]; ?>; border-color: <?php echo $awicon[$no]; ?>;"></i><strong><?php echo e($tlist['nama_menu']); ?></strong>
							<span><p><?php echo e($tlist['keterangan']); ?></p>
							</span>
						</a>
					</li>
					<?php $no++;} ?>
				</ul><!-- .indicators-list -->
			</div><!-- .col-md-9 -->
		</div><!-- .row -->
	</div><!-- .container -->
</section>
<section class="site-content padding-bottom-0">
	<div class="container paddingnya">
		<div class="row margin-bottom-10">
			<div class="col-md-8 no-padding left-column">
				
				<!-- Start Carousel Slider Berita -->
				<div id="custom_carousel" class="carousel slide margin-bottom-15" data-ride="carousel" data-interval="5000">
					<!-- Carousel Inner -->
					<div class="carousel-inner">
						<?php
						$terkini2=querydb("SELECT * FROM berita WHERE id_kategori='2' ORDER BY id_berita DESC LIMIT 5");
						$no=1;
						while($t=$terkini2->fetch_array()){      
						$tgl = tgl_indo($t['tanggal']);
						
						if($no==1){$item="active";}else{$item="";}
						?>
						<div class="item <?php echo $item; ?>">
							<div class="container-fluid no-padding">
								<div class="row">                               
									<div class="col-sm-12 no-padding">
										<a href="<?php echo build_seo_url('baca-berita-', (int)$t['id_berita'], $t['judul_seo']); ?>">
											<img class="img-responsive slider-berita" src="foto_berita/<?php echo e($t['gambar']); ?>" alt="<?php echo e($t['judul']); ?>"/>
											<div class="carousel-caption">
												<span><?php echo e($t['hari']); ?>, <?php echo e(tgl_indo($t['tanggal']));?></span>
												<h2 align="left"><?php echo e($t['judul']); ?></h2>
											</div>                     
										</a>
									</div><!-- .col-sm-12 -->
								</div><!-- .row -->
							</div><!-- .container-fluid -->           
						</div>
					<?php $no++;}  ?>
						
					</div>
					<!-- End Carousel Inner -->

					<style type="text/css">
						p.text-page {
						    overflow: hidden;
						    height: 77px;
						    margin-bottom: 8px;
						}
					</style>
					
					<!-- Start Control Thumbnails -->
					<div class="controls">
						<ul class="nav slider margin-left-0">
							<?php
							$terkini2=querydb("SELECT * FROM berita WHERE id_kategori='2' ORDER BY id_berita DESC LIMIT 5");
							$no=0;
							while($t=$terkini2->fetch_array()){      
							$tgl = tgl_indo($t['tanggal']);
							?>
							<li data-target="#custom_carousel" data-slide-to="<?php echo $no; ?>" class="">
								<a href="#" class="slider-control">
									<div class="margin-bottom-10">
										<img class="img-responsive" src="foto_berita/small_<?php echo e($t['gambar']); ?>" alt="<?php echo e($t['judul']); ?>"/>
									</div>
									<p class="hidden-xs hidden-sm text-page">
										<span><?php echo e($t['judul']); ?></span>
									</p>
								</a>
							</li>
							<?php $no++;} ?>
						</ul>
					</div>
					<!-- End Control Thumbnails -->

				</div>
				<!-- End Carousel Slider Berita -->

				

			</div> <!-- .col-md-8 -->

			<div class="col-md-4 right-column no-padding margin-bottom-10">
				<!--/////////////////////////- start sidebar atas -/////////////////////-->
				<ul class="nav nav-tabs tabs-v3 nav-justified social-tabs custom" role="tablist">
		
					<li role="presentation" class="myriadpro active">
						<a href="#beritaterbaru" class="sidebar-utama" role="tab" data-toggle="tab">
							<span>Terbaru</span>
						</a>
					</li>
					<li role="presentation" class="myriadpro">
						<a href="#beritaterpopuler" class="sidebar-utama kanan" role="tab" data-toggle="tab">
							<span>Populer</span>
						</a>
					</li>
				</ul>

				<div class="tab-content social-tabs no-border no-padding">
					<div class="tab-pane no-padding active" id="beritaterbaru">	
						<?php 
						$terbaru=querydb("SELECT * FROM berita WHERE id_kategori='2' ORDER BY id_berita DESC LIMIT 3");
						while($bt=$terbaru->fetch_array()){      
						$tgl = tgl_indo($bt['tanggal']);
						?>
						<a href="<?php echo build_seo_url('baca-berita-', (int)$bt['id_berita'], $bt['judul_seo']); ?>" class="list-group-item custom">
							<div class="row">
								<div class="col-xs-4 no-padding">
									<img src="foto_berita/small_<?php echo e($bt['gambar']); ?>" class="img-responsive padding-right-10" alt="<?php echo e($bt['judul']); ?>">
								</div>
								<div class="col-xs-8 no-padding">
									<div style="overflow: hidden;height: 57px;">
										<h5 class="no-margin"><span style="color: #000000;"><?php echo e($bt['judul']); ?></span></h5>
									</div>
								</div>  
							</div>
						</a>
						<?php } ?>
					</div>

					<div class="tab-pane no-padding" id="beritaterpopuler">
						<?php 
						$populer=querydb("SELECT * FROM berita WHERE id_kategori='2' ORDER BY dibaca DESC LIMIT 3");
						while($bp=$populer->fetch_array()){      
						$tgl = tgl_indo($bp['tanggal']);
						?>
						<a href="<?php echo build_seo_url('baca-berita-', (int)$bp['id_berita'], $bp['judul_seo']); ?>" class="list-group-item custom">
							<div class="row">
								<div class="col-xs-4 no-padding">
									<img src="foto_berita/small_<?php echo e($bp['gambar']); ?>" class="img-responsive padding-right-10" alt="<?php echo e($bp['judul']); ?>">
								</div>
								<div class="col-xs-8 no-padding">
									<div style="overflow: hidden;height: 57px;">
										<h5 class="no-margin"><span style="color: #000000;"><?php echo e($bp['judul']); ?></span></h5>
									</div>
								</div>  
							</div>
						</a>
						<?php } ?>
					</div>
				</div>
				<!--/////////////////////////- end sidebar atas -/////////////////////-->
			</div>

			<div class="col-md-4 right-column no-padding margin-bottom-10">
				<!--/////////////////////////- start sidebar atas -/////////////////////-->
				<ul class="nav nav-tabs tabs-v3 nav-justified social-tabs custom" role="tablist">
		
					<li role="presentation" class="myriadpro active">
						<a href="#sms" class="sidebar-utama" role="tab" data-toggle="tab">
							<span>BANNER</span>
						</a>
					</li>
				</ul>

				<style type="text/css">
					a.list-group-item {
						text-decoration: none;
						color: #00276C;
					}

					.atas-sms {
						border-bottom: solid 1px;
						margin-bottom: 0px;
					}

					@media (max-width: 770px) {
						.atas-sms small {
							font-size: 11px;
						}
					}

					@media (max-width: 330px) {
						.atas-sms small {
							font-size: 9px;
						}
					}
				</style>

				
				<div class="tab-content social-tabs no-border no-padding">
					<div class="tab-pane no-padding active" id="sms">
						<?php 
							$ambilbanner=querydb("SELECT * FROM banner LIMIT 1");
							$tban=$ambilbanner->fetch_array();
						?>
						<a href="<?php echo safe_url($tban['link']); ?>" target="_blank">
							<img src="foto_banner/<?php echo e($tban['gambar']); ?>" width="100%" alt="Banner"/>
						</a>	
					</div>
				</div>
				<!--/////////////////////////- end sidebar atas -/////////////////////-->
			</div>

		</div> <!-- .row margin-bottom-10 -->
	</div> <!-- .container paddingnya -->
</section>
<section class="site-content padding-bottom-25 no-padding">
	<div class="container paddingnya">
		<div class="row">
			<div class="col-md-8 no-padding left-column">
			<!--/////////////////////////- start main -/////////////////////-->
			<ul class="nav nav-tabs tabs-v3 nav-justified social-tabs responsive-text" role="tablist">
				<li role="presentation" class="myriadpro uppercase active">
					<a href="#artikel" role="tab" data-toggle="tab">
						<i class="fa fa-folder-open"></i>
						<span>Berita</span>
					</a>
				</li>
				<li role="presentation" class="myriadpro uppercase">
					<a href="#agendakegiatan" role="tab" data-toggle="tab">
						<i class="fa fa-folder-open"></i>
						<span>Agenda</span>
					</a>
				</li>
				<li role="presentation" class="myriadpro uppercase">
					<a href="#pengumuman" role="tab" data-toggle="tab">
						<i class="fa fa-clipboard"></i>
						<span>Pengumuman</span>
					</a>
				</li>
			</ul>

			<div class="tab-content bg-light social-tabs no-border no-padding">
				<div class="tab-pane no-padding active" id="artikel">
					<ul class="media-list">
						<?php 
						$terkiniARTIKEL=querydb("SELECT * FROM berita WHERE id_kategori='2' ORDER BY id_berita DESC LIMIT 9");
						$no=1;
						while($a=$terkiniARTIKEL->fetch_array()){      
						$tgl = tgl_indo($a['tanggal']);
						
						?>
						<li class="media space margin-bottom-20">
							<div class="media-left">
								<img class="media-object borderthumb" 
									 src="foto_berita/small_<?php echo e($a['gambar']); ?>" 
									 alt="<?php echo e($a['judul']); ?>" 
									 width="125" 
									 height="70">
							</div> <!-- .media-left -->

							<div class="media-body">
								<p class="small text-muted no-bottom-spacing">
									<i class="fa fa-calendar margin-right-5"></i>
									<?php echo e(tgl_indo($a['tanggal'])); ?>
								</p>
								<h4 class="media-heading margin-top-5">
									<a href="baca-berita-<?php echo (int)$a['id_berita']."-".rawurlencode($a['judul_seo']); ?>.html">
										<?php echo e($a['judul']); ?>
									</a>

								</h4>
							</div> <!-- .media-body -->
						</li>
						<?php $no++;} ?>
					</ul>
				</div>

				<div class="tab-pane no-padding" id="agendakegiatan">
					<ul class="media-list">
						<?php
						$agenda = querydb("SELECT * FROM agenda ORDER BY id_agenda DESC LIMIT 5");
						while($tgd=$agenda->fetch_array()){
							$tgl_posting_raw = $tgd['tgl_posting']  ?? '';
							$tgl_mulai_raw   = $tgd['tgl_mulai']    ?? '';
							$tgl_selesai_raw = $tgd['tgl_selesai']  ?? '';

							$tgl_posting = tgl_indo($tgl_posting_raw);
							$tgl_mulai   = tgl_indo($tgl_mulai_raw);
							$tgl_selesai = tgl_indo($tgl_selesai_raw);

							$isi_agenda_raw = $tgd['isi_agenda'] ?? '';
							$isi_agenda     = nl2br(e($isi_agenda_raw));

							if ($tgl_mulai == $tgl_selesai){
								$rentang_tgl = $tgl_mulai;
							}
							else{
								$rentang_tgl = $tgl_mulai && $tgl_selesai ? "$tgl_mulai s/d $tgl_selesai" : ($tgl_mulai ?: $tgl_selesai);
							}
						?>
						<li class="media agenda-item margin-bottom-20">
							<div class="media-left">
								<div class="event-date">
									<p><?php echo konversi_tanggal("j",$tgd['tgl_mulai']); ?></p>
									<small class="month"><?php echo konversi_tanggal("M",$tgd['tgl_mulai']); ?></small>
									<small class="year"><?php echo konversi_tanggal("Y",$tgd['tgl_mulai']); ?></small>
								</div>
							</div>

							<div class="media-body">	
								<h5 class="agenda-title"><b><?php echo e($tgd['tema']); ?></b></h5>

								<div class="agenda-meta small">

									<div class="agenda-row">
										<i class="fa fa-map-marker"></i>
										<span><?php echo e($tgd['tempat']); ?></span>
									</div>
									
									<div class="agenda-row">
										<i class="fa fa-calendar"></i>
										<span><?php echo e($rentang_tgl); ?></span>
									</div>

									<div class="agenda-row">
										<i class="fa fa-clock-o"></i>
										<span>Pukul <?php echo e($tgd['jam']); ?></span>
									</div>

									<div class="agenda-row">
										<i class="fa fa-user"></i>
										<span><?php echo e($tgd['pengirim']); ?></span>
									</div>
								</div>

								<div class="agenda-desc small">
									<?php echo $isi_agenda; ?>
								</div>
							</div>
						</li>


						<?php } ?>
					</ul>
				</div>

				<div class="tab-pane no-padding" id="pengumuman">
					<ul class="media-list">
					<?php 
						$pengumuman = querydb("SELECT * FROM pengumuman ORDER BY id_pengumuman DESC LIMIT 6");
						while($tpe=$pengumuman->fetch_array()){
							$id_peng = (int)$tpe['id_pengumuman'];
							$slug  = rawurlencode($tpe['judul_seo'] ?? '');
							$judul = e($tpe['judul'] ?? '');
					?>
						<li class="media space margin-bottom-20">
							<div class="media-left">
								<div class="event-date margin-bottom-5">
									<i class="fa fa-bullhorn fa-3x"></i>
								</div>
							</div>

							<div class="media-body">
								<p class="small text-muted no-bottom-spacing">
									<i class="fa fa-calendar margin-right-5"></i>
									<?php echo e(konversi_tanggal("D, j M Y",$tpe['tgl_posting'])); ?>
								</p>
								<h4 class="media-heading margin-top-5">
									<a href="baca-pengumuman-<?php echo $id_peng . "-" . $slug; ?>.html">
										<?php echo $judul; ?>
									</a>
								</h4>
							</div>
						</li>
						<?php } ?>
					</ul>
				</div>
			</div>
			<!--/////////////////////////- end main -/////////////////////-->
			</div>
		<?php
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}
			$_SESSION['poll_csrf'] = bin2hex(random_bytes(16));
		?>
		<div class="col-md-4 no-padding right-column margin-bottom-15">
				<!-- jajak pendapat -->
					
				<div class="content-box box-img bg-light no-margin featured-news jajak-pendapat">
					<div class="col-sm-12 no-padding">
						<div class="container-fluid no-margin no-padding">
							<div class="col-xs-12 padding-top-10 hal-kecil-heading">
								<!-- judul -->
								<h1 class="panel-title custom-font"><i class="fa fa-signal"></i> &nbsp;Polling</h1>
							</div>
						</div>
					</div>
					<div class="box-body custom">
						<?php 
						$tanya = querydb("SELECT * FROM poling WHERE aktif='Y' and status='Pertanyaan'");
						$t     = $tanya->fetch_array();
						?>
						<p><?php echo e($t['pilihan']); ?></p>
						<form action="hasil-poling.html" method="post" class="bs-example form-horizontal">
							<input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['poll_csrf'] ?? ''); ?>">
							<div class="form-group">
								<div class="col-lg-10">
									<?php 
									$poling = querydb("SELECT * FROM poling WHERE aktif='Y' and status='Jawaban' ORDER BY id_poling DESC");
									while ($p = $poling->fetch_array()) {
										$idPilihan = (int)($p['id_poling'] ?? 0);
										$label     = e($p['pilihan'] ?? '');
										echo "
											<div class='radio'>
												<label>
													<input name='pilihan' type='radio' value='{$idPilihan}' />{$label}
												</label>
											</div>
										";
									}
									?>
								</div>
							</div>

							<div class="form-group">
								<div class="col-lg-12">
									<input type="submit" name="Submit" value="Vote" class="btn btn-sm btn-primary"/>
									<input type="button" name="lihat" value="Lihat Hasil" class="btn btn-sm btn-primary" onclick="window.location.href='lihat-poling.html'; return false;" />
									<input type="hidden" id="id" name="id" value="" />
								</div>
							</div>

						</form>

					</div> <!-- .box-body -->
				</div> <!-- .content-box .box-img -->

				<style type="text/css">
					.kritik-saran li a, .kritik-saran li p {
						color: #fff;
					}
				</style>

				<div class="margin-top-15">
					<div class="col-sm-12 no-padding">
						<div class="container-fluid no-margin no-padding">
							<div class="col-xs-12 padding-top-10 hal-kecil-heading">
								<!-- judul -->
								<h1 class="panel-title custom-font"><i class="fa fa-twitter"></i> &nbsp;Twitter</h1>
							</div>
							<div class="box-body">
								<?php echo $tiden['twitter_widget']; ?>
							</div>
						</div>
					</div>
				</div>


			</div>
			
		</div>
	</div>
</section>

<style type="text/css">
	.page-heading {
		color: #000;
		font-size: 30px;
		text-align: center;
		border-bottom: 1px solid;
	}
</style>
<section class="site-content padding-bottom-0">
	<div class="container no-padding margin-top-15">
		<div class="row">
			<div class="col-md-12">
				<h3 class="page-heading"></h3>
			</div>
			<!-- Video -->
			<style type="text/css">
				div.content-box.box-clickable {
				    background-color: #414141;
				}

				.box-body h4 a {
					color: #fff;
				}
			</style>
			<?php 
			$album=querydb("SELECT * FROM album WHERE aktif='Y' ORDER BY id_album DESC LIMIT 4");
			while($tfab=$album->fetch_array()){
				$idalbum = (int)$tfab['id_album'];
				$slug    = rawurlencode($tfab['album_seo'] ?? '');
				$judul   = e($tfab['jdl_album']);
				$gbr     = e($tfab['gbr_album']);
			?>
			<div class="col-md-3 col-sm-6 col-xs-12 margin-bottom-15">
				<div class="content-box box-img no-margin text-center featured-news box-clickable">
					<img class="img-responsive" src="img_album/small_<?php echo $gbr; ?>" alt="<?php echo $judul; ?>" width="100%" style="height:230px;">
					<div class="ua-square-logo overlap-top text-center">
						<center>
							<div class="overlay-custom" style="max-width: 150px;margin-top:30px;">
								<div class="overlay-post" style="background-color: #FE8C05;color: #fff;padding: 9px;">Galeri Foto</div>
							</div>
						</center>
					</div> <!-- .overlay-post -->
					<div class="box-body">
						<h4>
							<a href="lihat-foto-<?php echo $idalbum."-".$slug; ?>.html"><?php echo $judul; ?></a>
						</h4>
					</div> <!-- .box-body -->
				</div> <!-- .box-img -->
			</div> <!-- .col-md-3 -->
			<?php } ?>			
		<div class="col-md-12">
				<span class="pull-right">
					<a class="btn btn-sm btn-primary" href="arsip-foto.html">Lihat Galeri Foto Lainnya</a>
				</span>
				<br>
			</div>
		</div> <!-- .row -->
	</div>
</section>
 <?php
 }
//MODUL DETAIL BERITA 
elseif($module=='detailberita'){

	// Sanitasi & validasi ID dari URL
    $id_berita = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id_berita <= 0) {
        // ID tidak valid
        echo '<section class="site-content padding-bottom-0 margin-top-15">
                <div class="container">
                    <p>Berita tidak ditemukan.</p>
                </div>
              </section>';
    } else {

        // Ambil data berita dengan prepared statement
        $detail = querydb_prepared(
            "SELECT * FROM berita, users, kategori    
             WHERE users.username = berita.username 
               AND kategori.id_kategori = berita.id_kategori 
               AND id_berita = ?",
            "i",
            [$id_berita]
        );

        $d = $detail ? $detail->fetch_array() : null;
		$judul   = e($d['judul'] ?? '');
		$jam     = e($d['jam'] ?? '');
		$penulis = e($d['nama_lengkap'] ?? '');
		$tgl     = e($d['tanggal'] ?? '');
		$tglSafe = konversi_tanggal("D, j F Y",$tgl);

        if (!$d) {
            // ID valid tapi data tidak ada
            echo '<section class="site-content padding-bottom-0 margin-top-15">
                    <div class="container">
                        <p>Berita tidak ditemukan.</p>
                    </div>
                  </section>';
        } else {
            // Data ditemukan ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ lanjut seperti biasa
            $tgl = tgl_indo($d['tanggal']);

            // Start session jika belum
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Proteksi counter "dibaca"
            if (!isset($_SESSION['viewed_articles'])) {
                $_SESSION['viewed_articles'] = [];
            }

            if (!in_array($id_berita, $_SESSION['viewed_articles'], true)) {
                // Naikkan view di database
                exec_prepared(
                    "UPDATE berita SET dibaca = dibaca + 1 WHERE id_berita = ?",
                    "i",
                    [$id_berita]
                );
                $_SESSION['viewed_articles'][] = $id_berita;

                // Sinkronkan nilai lokal untuk tampilan (kalau dipakai)
                $d['dibaca'] = (int)($d['dibaca'] ?? 0) + 1;
            }
?>
<section class="site-content padding-bottom-0 margin-top-15">
	<div class="container">
		<div class="row">
			<div class="col-md-8 left-column no-padding">
				<div class="breadcrumb-wrapper bg-medium">
					<div class="container-fluid">
						<div class="row">
							<div class="col-xs-12">
								<ol class="breadcrumb">
									<li><a href="./">Beranda</a></li>
									<li><a href="#">Berita</a></li>
								</ol>
							</div> <!-- .col-md-12 -->
						</div> <!-- .row -->
					</div> <!-- .container -->
				</div>
				<h1 class="margin-top-15"><?php echo $judul; ?></h1>
				<ul class="list-inline small font-weight-600 myriadpro">
					<li><i class="fa fa-calendar"></i> <?php echo e($tglSafe)." - ".$jam; ?> WIT</li>
					<li><i class="fa fa-user"></i> <?php echo $penulis; ?></li>
				</ul>

				<?php
			$shareUrl = $tiden['alamat_website'] . "/baca-berita-{$d['id_berita']}-" . rawurlencode($d['judul_seo'] ?? '') . ".html";
			echo render_share_buttons($shareUrl, $d['judul']);
				?>

				<article class="news margin-bottom-15">
					<!-- gambar konten -->
					<?php 
					if (!empty($d['gambar'])) {
						$img   = e($d['gambar']);
						$alt   = e($d['judul'] ?? '');
						echo "<figure class='wp-caption margin-bottom-15'>
								<img style='align:center;width:100%;' class='size-medium img-responsive'
									src='foto_berita/{$img}'
									alt='{$alt}'/>
							</figure>";
					}
					?>
					<div class="divider-light"></div>
					<div style="text-align: justify;"><p><?php echo $d['isi_berita']; ?></p></div>
					<div class="clearfix"></div>
				</article>

		<?php
		$shareUrl = $tiden['alamat_website'] . "/baca-berita-{$d['id_berita']}-" . rawurlencode($d['judul_seo'] ?? '') . ".html";
		echo render_share_buttons($shareUrl, $d['judul']);
		?>

		<ul class="list-inline small font-weight-600 myriadpro">
			<li><i class="fa fa-calendar"></i> <?php echo e($tglSafe)." - ".$jam; ?> WIT</li>
			<li><i class="fa fa-user"></i> <?php echo $penulis; ?></li>
		</ul>

		<div class="divider-light"></div>
				<div id="disqus_thread"></div>
		<div class="clearfix margin-bottom-15"></div>
				<div class="list-group-item bg-primary">
							<h4 class="list-group-item-heading">
								<i class="fa fa-newspaper-o margin-right-10"></i>
								<b>Berita Terkait Lainnya</b>
							</h4>
				</div>
				<?php echo artikelTerkait((int)$d['id_berita']); ?>

			</div>

			<div class="col-md-4 right-column no-padding">
				<?php include "sidebarkanan.php"; ?>
			</div>
		</div>
	</div>
</section>
<?php
		}
	}
}

// MODUL HASIL POLLING
elseif($module=='hasilpoling'){
?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$postedToken  = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['poll_csrf'] ?? '');

if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
    echo "<p>Token tidak valid. Silakan coba lagi.</p>";
    return;
} else {
?>

<section class="site-content">
  <div class="container">
	<div class="row">
<?php

 if (isset($_COOKIE["poling"])) {
   echo "<h3><center>Sorry, anda sudah pernah melakukan voting terhadap poling ini.</center></h3>";
 }
 else{

   	// membuat cookie dengan nama poling
   	// cookie akan secara otomatis terhapus dalam waktu 24 jam
   	setcookie("poling", "sudah poling", time() + 3600 * 24);
   	// amankan id_poling dari POST (cast ke integer + prepared statement)
   	$id_pilihan = isset($_POST['pilihan']) ? (int)$_POST['pilihan'] : 0;
   	if ($id_pilihan > 0) {
		exec_prepared(
        "UPDATE poling SET rating = rating + 1 WHERE id_poling = ?",
        "i",
        [$id_pilihan]
		);
	}

?>
	  <div class="col-md-12 padding-bottom-20 left-column no-padding">
		<div class="breadcrumb-wrapper bg-medium margin-bottom-20">
		  <div class="container-fluid no-padding">
			<div class="row">
			  <div class="col-md-6 col-sm-6 col-xs-12">
				<h1 class="breadcrumb-page-title">Hasil Jajak Pendapat</h1>


			  </div> <!-- .col-md-6 -->
			  <div class="col-md-6 col-sm-6 col-xs-12">
				<ol class="breadcrumb">
				  <li><a href="./">Beranda</a></li>
				  <li>Hasil Jajak Pendapat</li>
				</ol>
			  </div> <!-- .col-md-6 -->
			</div> <!-- .row -->
		  </div> <!-- .container -->
		</div> <!-- .breadcrumb-wrapper -->
		<div class="row">
		<?php 
		$pertanyaan=querydb("SELECT * FROM poling WHERE aktif='Y' and status='Pertanyaan'");
		$dperta=$pertanyaan->fetch_array();
		?>
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
					  <tr>
						<td align="left" valign="middle"><strong>Pertanyaan:</strong></td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle">&nbsp;</td>
					  </tr>
					  <tr>
						<td colspan="5" align="left" valign="middle"><?php echo e($dperta['pilihan']); ?></td>
					  </tr>
					  <tr>
						<td align="left" valign="middle">&nbsp;</td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle">&nbsp;</td>
					  </tr>
					  <tr>
						<td align="left" valign="middle"><strong>Pilihan</strong></td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle"><strong>Hasil Persentase (%)</strong></td>
					  </tr>
					  <?php 
					  $jml=querydb("SELECT SUM(rating) as jml_vote FROM poling WHERE aktif='Y'");
					  $j = $jml->fetch_array();
					  $jml_vote = (int)($j['jml_vote'] ?? 0);
					  
					  if ($jml_vote <= 0) {
						echo "<p>Belum ada responden.</p>";
						$jml_vote = 1; // supaya tidak division by zero di bawah
						}
						
						$sql=querydb("SELECT * FROM poling WHERE aktif='Y' and status='Jawaban'");
						  
						  while ($s=$sql->fetch_array()){
						  	
						  	$prosentase = sprintf("%2.1f",(($s['rating']/$jml_vote)*100));
						  	$gbr_vote   = $prosentase * 3;
						 
					  ?>
					  <tr class="teks_utama">
						<td width="23%" align="left" valign="middle" bgcolor="#EFEFEF"><?php echo e($s['pilihan']); ?>&nbsp;</td>
						<td width="1%" align="center" valign="middle" bgcolor="#EFEFEF"></td>
						<td width="30%" align="left" valign="middle" bgcolor="#EFEFEF"><img src="images/bar.gif" width="<?php echo (int)$gbr_vote; ?>" height="14" /></td>
						<td width="4%" align="right" valign="middle" bgcolor="#EFEFEF"><?php echo (int)$s['rating']; ?>&nbsp;</td>
						<td width="20%" align="left" valign="middle" bgcolor="#EFEFEF"> (<?php echo e($prosentase); ?> %)</td>
					  </tr>
					  <tr class="teks_utama">
						<td align="left" valign="middle">&nbsp;</td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle">&nbsp;</td>
					  </tr>
					  <?php  } ?>
					  <tr class="teks_utama">
						<td align="left" valign="middle">&nbsp;</td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle">&nbsp;</td>
					  </tr>
					  <tr class="teks_utama">
						<td align="left" valign="middle">Total</td>
						<td align="center" valign="middle"></td>
						<td colspan="3" align="left" valign="middle"> <b><?php echo (int)$jml_vote; ?></b> Responden </td>
					  </tr>
				  </table>
<?php } ?>
	</div>
  </div>
</section>
<?php
}
}
// MODUL LIHAT POLLING
elseif ($module=='lihatpoling'){
?>
<section class="site-content">
  <div class="container">
	<div class="row">

	  <div class="col-md-12 padding-bottom-20 left-column no-padding">
		<div class="breadcrumb-wrapper bg-medium margin-bottom-20">
		  <div class="container-fluid no-padding">
			<div class="row">
			  <div class="col-md-6 col-sm-6 col-xs-12">
				<h1 class="breadcrumb-page-title">Hasil Jajak Pendapat</h1>


			  </div> <!-- .col-md-6 -->
			  <div class="col-md-6 col-sm-6 col-xs-12">
				<ol class="breadcrumb">
				  <li><a href="./">Beranda</a></li>
				  <li>Hasil Jajak Pendapat</li>
				</ol>
			  </div> <!-- .col-md-6 -->
			</div> <!-- .row -->
		  </div> <!-- .container -->
		</div> <!-- .breadcrumb-wrapper -->


		<div class="row">
		<?php 
		$pertanyaan=querydb("SELECT * FROM poling WHERE aktif='Y' and status='Pertanyaan'");
		$dperta=$pertanyaan->fetch_array();
		?>
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
					  <tr>
						<td align="left" valign="middle"><strong>Pertanyaan:</strong></td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle">&nbsp;</td>
					  </tr>
					  <tr>
						<td colspan="5" align="left" valign="middle"><?php echo e($dperta['pilihan']); ?></td>
					  </tr>
					  <tr>
						<td align="left" valign="middle">&nbsp;</td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle">&nbsp;</td>
					  </tr>
					  <tr>
						<td align="left" valign="middle"><strong>Pilihan</strong></td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle"><strong>Hasil Persentase (%)</strong></td>
					  </tr>
					  <?php 
					    $jml=querydb("SELECT SUM(rating) as jml_vote FROM poling WHERE aktif='Y'");
						$j=$jml->fetch_array();
						  
						$jml_vote = (int)($j['jml_vote'] ?? 0);
					  
					  	if ($jml_vote <= 0) {
							$jml_vote = 1; // supaya tidak division by zero di bawah
							}
						  
						  $sql=querydb("SELECT * FROM poling WHERE aktif='Y' and status='Jawaban'");
						  
						  while ($s=$sql->fetch_array()){
						  	
						  	$prosentase = sprintf("%2.1f",(($s['rating']/$jml_vote)*100));
						  	$gbr_vote   = $prosentase * 3;
						 
					  ?>
					  <tr class="teks_utama">
						<td width="23%" align="left" valign="middle" bgcolor="#EFEFEF"><?php echo e($s['pilihan']); ?>&nbsp;</td>
						<td width="1%" align="center" valign="middle" bgcolor="#EFEFEF"></td>
						<td width="30%" align="left" valign="middle" bgcolor="#EFEFEF"><img src="images/bar.gif" width="<?php echo (int)$gbr_vote; ?>" height="14" /></td>
						<td width="4%" align="right" valign="middle" bgcolor="#EFEFEF"><?php echo (int)$s['rating']; ?>&nbsp;</td>
						<td width="20%" align="left" valign="middle" bgcolor="#EFEFEF"> (<?php echo e($prosentase); ?> %)</td>
					  </tr>
					  <tr class="teks_utama">
						<td align="left" valign="middle">&nbsp;</td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle">&nbsp;</td>
					  </tr>
					  <?php  } ?>
					  <tr class="teks_utama">
						<td align="left" valign="middle">&nbsp;</td>
						<td align="center" valign="middle">&nbsp;</td>
						<td colspan="3" align="left" valign="middle">&nbsp;</td>
					  </tr>
					  <tr class="teks_utama">
						<td align="left" valign="middle">Total</td>
						<td align="center" valign="middle"></td>
						<td colspan="3" align="left" valign="middle"> <b><?php echo (int)$jml_vote; ?></b> Responden </td>
					  </tr>
				  </table>
									
	</div>
  </div> <!-- .container -->
</section>
<?php           
}
// MODUL HALAMAN STATIS
elseif ($module=='halamanstatis'){

    // 1) Ambil ID dan amankan
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        echo '<section class="site-content"><div class="container"><p>Halaman tidak ditemukan.</p></div></section>';
    } else {
        // 2) Gunakan prepared statement
        $detail = querydb_prepared(
            "SELECT * FROM halamanstatis WHERE id_halaman = ?",
            "i",
            [$id]
        );
        $d = $detail ? $detail->fetch_array() : null;

        if (!$d) {
            echo '<section class="site-content"><div class="container"><p>Halaman tidak ditemukan.</p></div></section>';
        } else {
            $tgl_posting = tgl_indo($d['tgl_posting']);
		
		$judul = e($d['judul'] ?? '');
		$tgl   = e($tgl_posting);
		
?>

<section class="site-content padding-bottom-0 margin-top-15">
	<div class="container">
		<div class="row">
			<div class="col-md-8 left-column no-padding">
				<div class="breadcrumb-wrapper bg-medium">
					<div class="container-fluid">
						<div class="row">
							<div class="col-xs-12">
								<ol class="breadcrumb">
									<li><a href="./">Beranda</a></li>
									<li><a href="#">Berita</a></li>
								</ol>
							</div> <!-- .col-md-12 -->
						</div> <!-- .row -->
					</div> <!-- .container -->
				</div>
				<h1 class="margin-top-15"><?php echo $judul; ?></h1>
				<ul class="list-inline small font-weight-600 myriadpro">
					<li><i class="fa fa-calendar"></i> <?php echo $tgl; ?></li>
				</ul>
				
				<article class="news margin-bottom-15">
					<!-- gambar konten -->
					<?php 
					if (!empty($d['gambar'])) {
						$img = e($d['gambar']);
						$alt = e($d['judul'] ?? '');
						echo "<figure class='wp-caption margin-bottom-15'>
								<img style='align:center;' class='size-medium img-responsive'
									src='foto_banner/{$img}'
									alt='{$alt}'/>
							</figure>";
					}

					?>
					<div class="divider-light"></div>
					<div style="text-align: justify;"><p><?php echo $d['isi_halaman']; ?></p></div>
					<div class="clearfix"></div>
				</article>

		<?php
		$shareUrl = $tiden['alamat_website'] . "/statis-" . $d['id_halaman'] . "-" . rawurlencode($d['judul_seo'] ?? '') . ".html";
		echo render_share_buttons($shareUrl, $d['judul']);
		?>
				
		<ul class="list-inline small font-weight-600 myriadpro">
			<li><i class="fa fa-calendar"></i> <?php echo e($tgl_posting); ?></li>
		</ul>

		<div class="divider-light"></div>
				<div id="disqus_thread"></div>
		<div class="clearfix margin-bottom-15"></div>
		
			</div>

			<div class="col-md-4 right-column no-padding">
				<?php include "sidebarkanan.php"; ?>
			</div>
		</div>
	</div>
</section>
<?php
		}
	}         
}
// MODUL BERITA PERKATEGORI
elseif ($module=='detailkategori'){

	// Tampilkan nama kategori (nama_kategori dari tabel kategori)
    $id_kat = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $n = ['nama_kategori' => ''];

    if ($id_kat > 0) {
        $rs_kat = querydb_prepared(
            "SELECT nama_kategori FROM kategori WHERE id_kategori = ?",
            "i",
            [$id_kat]
        );
        if ($rs_kat) {
            $n = $rs_kat->fetch_array();
        }
    }

	if ($id_kat <= 0) {
		echo '<section class="site-content"><div class="container"><p>Kategori tidak ditemukan.</p></div></section>';
	} else {
?>
<section class="site-content">
	<div class="container">
		<div class="row">
			<div class="col-md-8 padding-bottom-20 left-column no-padding">
				<div class="breadcrumb-wrapper bg-medium margin-bottom-20">
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-6 col-sm-6 col-xs-12">
								<h1 class="breadcrumb-page-title">Direktori <?php echo e($n['nama_kategori'] ?? ''); ?></h1>
							</div> <!-- .col-md-6 -->
							<div class="col-md-6 col-sm-6 col-xs-12">
								<ol class="breadcrumb">
									<li><a href="./">Beranda</a></li>
									<li><?php echo e($n['nama_kategori'] ?? ''); ?></li>
								</ol>
							</div> <!-- .col-md-6 -->
						</div> <!-- .row -->
					</div> <!-- .container -->
				</div> <!-- .breadcrumb-wrapper -->
				<div class="row">		
					<?php
					$p      = new Paging3;
					$batas  = 10;
					$posisi = $p->cariPosisi($batas);

					// Tampilkan daftar berita sesuai dengan kategori yang dipilih
					$offset = (int) $posisi;
					$limit  = (int) $batas;
					$hasil  = querydb_prepared(
						"SELECT * FROM berita WHERE id_kategori = ? ORDER BY id_berita DESC LIMIT ?, ?",
						"iii",
						[$id_kat, $offset, $limit]
					);
					$jumlah = $hasil ? $hasil->num_rows : 0;
						// Apabila ditemukan berita dalam kategori
						if ($jumlah > 0){
					   while($r=$hasil->fetch_array()){
						    // Tampilkan hanya sebagian isi berita
						    $isi_berita = htmlentities(strip_tags($r['isi_berita'])); // membuat paragraf pada isi berita dan mengabaikan tag html
						    $isi = substr($isi_berita,0,200); // ambil sebanyak 220 karakter
						    $isi = substr($isi_berita,0,strrpos($isi," ")); // potong per spasi kalimat
						 ?>
						 	<div class="col-md-12 col-sm-12 col-xs-12 masonry-grid-item no-padding">
						<article class="content-box box-img bg-light box-clickable media">
							<div class="box-body media">
								<!-- image-mobile -->
								<div class="media-left foto-list-kecil visible-xs no-padding margin-bottom-10">
									<img class="img-responsive media-object borderthumb" src="foto_berita/<?php echo e($r['gambar']); ?>" alt="<?php echo e($r['judul']); ?>">
								</div>
								<!-- image-dekstop -->
								<div class="media-left hidden-xs">
									<img class="media-object foto-list-besar borderthumb" src="foto_berita/<?php echo e($r['gambar']); ?>" alt="<?php echo e($r['judul']); ?>" width="190" height="120">
								</div>
								<div class="media-body">
									<h3>
										<a href="baca-berita-<?php echo (int)$r['id_berita']."-".rawurlencode($r['judul_seo']).".html"; ?>"><?php echo e($r['judul']); ?></a>
									</h3>

									<ul class="list-inline small">
										<li><span class="label label-warning"><i class="fa fa-calendar"></i> <?= e(tgl_indo($r['tanggal'])) ?></span></li>
										<li style="margin-top: 5px;"><?php echo $isi; ?> ...
											<a href="#">
												<span></span>
											</a>
										</li>
									</ul>								
								</div>
							</div>

						</article>
					</div>
						 <?php
						 }
						
					  $jmldataStmt = querydb_prepared("SELECT COUNT(*) AS jml FROM berita WHERE id_kategori = ?", "i", [$id_kat]);
					  $jmldataRow  = $jmldataStmt ? $jmldataStmt->fetch_assoc() : ['jml' => 0];
					  $jmldata     = (int)$jmldataRow['jml'];
					  $jmlhalaman  = $p->jumlahHalaman($jmldata, $batas);
					  $currentPage = clamp_page($_GET['halkategori'] ?? 1);
					  $linkHalaman = $p->navHalaman($currentPage, $jmlhalaman);

					  echo "<div class='col-md-12 col-sm-12 col-xs-12 text-center'>
							<div class='row' align='center'><center><ul class='pagination'>$linkHalaman</ul></center></div></div>";
					  }
					  else{
					    echo "<h3><center>Belum ada berita pada kategori ini.</center>";
					  }
					?>
				</div>
			</div> <!-- .col-md-9 -->
			<div class="col-md-4 right-column no-padding">
				<?php include "sidebarkanan.php"; ?>
			</div> <!-- .col-md-4 -->
		</div> <!-- .row -->
	</div> <!-- .container -->
</section>
<?php
	}
}	
// Modul semua pengumuman
elseif ($module=='semuapengumuman'){
?>
<section class="site-content">
	<div class="container">
		<div class="row">
			<div class="col-md-8 padding-bottom-20 left-column no-padding">
				<div class="breadcrumb-wrapper bg-medium margin-bottom-20">
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-6 col-sm-6 col-xs-12">
								<h1 class="breadcrumb-page-title">Direktori Pengumuman</h1>
							</div> <!-- .col-md-6 -->
							<div class="col-md-6 col-sm-6 col-xs-12">
								<ol class="breadcrumb">
									<li><a href="./">Beranda</a></li>
									<li>Direktori Pengumuman</li>
								</ol>
							</div> <!-- .col-md-6 -->
						</div> <!-- .row -->
					</div> <!-- .container -->
				</div>
				<div class="row">
				<?php 
				  $p      = new PagingPengumuman;
				  $batas  = 10;
				  $posisi = $p->cariPosisi($batas); 
				  // Tampilkan semua pengumuman
					$stmtPeng = querydb_prepared(
						"SELECT * FROM pengumuman ORDER BY id_pengumuman DESC LIMIT ?, ?",
						"ii",
						[(int)$posisi, (int)$batas]
					);		 
				  while($stmtPeng && $d=$stmtPeng->fetch_array()){
				    $tgl_posting = tgl_indo($d['tgl_posting']);
					$pengId      = (int)($d['id_pengumuman'] ?? 0);
					$slugPeng    = rawurlencode($d['judul_seo'] ?? '');
				    
				    // Tampilkan hanya sebagian isi berita
				      $isi_pengumuman = htmlentities(strip_tags($d['isi_pengumuman'])); // membuat paragraf pada isi berita dan mengabaikan tag html
				      $isi = substr($isi_pengumuman,0,220); // ambil sebanyak 150 karakter
				      $isi = substr($isi_pengumuman,0,strrpos($isi," ")); // potong per spasi kalimat
				    ?>
				    <div class="col-md-12 col-sm-12 col-xs-12 masonry-grid-item no-padding">
					<article class="content-box box-img bg-light box-clickable media">
						<div class="box-body media">
							<div class="media-body">
							<h3>
								<a href="baca-pengumuman-<?php echo $pengId . '-' . $slugPeng; ?>.html"><?php echo e($d['judul']); ?></a>
							</h3>

							<ul class="list-inline small">
								<li><i class="fa fa-calendar"></i> <?php echo e($tgl_posting); ?></li>
								<li style="margin-top: 5px;">
									<?php echo $isi; ?>  ...
		                            <a href="baca-pengumuman-<?php echo $pengId . '-' . $slugPeng; ?>.html">
		                            	<span></span>
		                            </a>
								</li>
							</ul>
							</div>
						</div>
					</article>
				</div>
				          <?php
					 }
					
				  $jmldataRow = querydb("SELECT COUNT(*) AS jml FROM pengumuman");
				  $jmldata    = $jmldataRow ? (int)$jmldataRow->fetch_assoc()['jml'] : 0;
				  $jmlhalaman = $p->jumlahHalaman($jmldata, $batas);
				  $currentPage = clamp_page($_GET['halpengumuman'] ?? 1);
				  $linkHalaman = $p->navHalaman($currentPage, $jmlhalaman);
				  echo "<div class='col-md-12 col-sm-12 col-xs-12 text-center'>
							<div class='row' align='center'><center><ul class='pagination'>$linkHalaman</ul></center></div></div>";
				?>	
				</div>
			</div>
			<div class="col-md-4 right-column no-padding">
			<?php include "sidebarkanan.php"; ?>
			</div>
		</div>
	</div>
</section>
<?php             
}
// Modul semua agenda
elseif ($module=='semuaagenda'){
?>
<section class="site-content">
	<div class="container">
		<div class="row">
			<div class="col-md-8 padding-bottom-20 left-column no-padding">
				<div class="breadcrumb-wrapper bg-medium margin-bottom-20">
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-6 col-sm-6 col-xs-12">
								<h1 class="breadcrumb-page-title">Daftar Agenda</h1>
							</div> <!-- .col-md-6 -->
							<div class="col-md-6 col-sm-6 col-xs-12">
								<ol class="breadcrumb">
									<li><a href="./">Beranda</a></li>
									<li>Agenda Kegiatan</li>
								</ol>
							</div> <!-- .col-md-6 -->
						</div> <!-- .row -->
					</div> <!-- .container -->
				</div>
				<div class="row">
				<?php 
				  $p      = new Paging4;
				  $batas  = 6;
				  $posisi = $p->cariPosisi($batas); 
				  // Tampilkan semua agenda
				  $stmtAgenda = querydb_prepared(
					"SELECT * FROM agenda ORDER BY id_agenda DESC LIMIT ?, ?",
					"ii",
					[(int)$posisi, (int)$batas]
				  );		 
				  while($stmtAgenda && $tgd=$stmtAgenda->fetch_array()){
				    $tgl_posting_raw = $tgd['tgl_posting']  ?? '';
					$tgl_mulai_raw   = $tgd['tgl_mulai']    ?? '';
					$tgl_selesai_raw = $tgd['tgl_selesai']  ?? '';

					$tgl_posting = tgl_indo($tgl_posting_raw);
					$tgl_mulai   = tgl_indo($tgl_mulai_raw);
					$tgl_selesai = tgl_indo($tgl_selesai_raw);

					$isi_agenda_raw = $tgd['isi_agenda'] ?? '';
					$isi_agenda  = nl2br(e($isi_agenda_raw));

					if ($tgl_mulai == $tgl_selesai){
						$rentang_tgl = $tgl_mulai;
					}
					else{
						$rentang_tgl = $tgl_mulai && $tgl_selesai ? "$tgl_mulai s/d $tgl_selesai" : ($tgl_mulai ?: $tgl_selesai);
					}
				?>
				        <div class="col-md-12 col-sm-12 col-xs-12 masonry-grid-item no-padding">
							<article class="content-box box-img bg-light box-clickable media agenda-item">
								<div class="box-body media">

									<!-- TANGGAL -->
									<div class="media-left">
										<div class="event-date">
											<p><?php echo e(konversi_tanggal("j",$tgd['tgl_mulai'])); ?></p>
											<small class="month"><?php echo e(konversi_tanggal("M",$tgd['tgl_mulai'])); ?></small>
											<small class="year"><?php echo e(konversi_tanggal("Y",$tgd['tgl_mulai'])); ?></small>
										</div>
									</div>

									<!-- ISI AGENDA -->
									<div class="media-body">	
										<h5 class="agenda-title"><b><?php echo e($tgd['tema']); ?></b></h5>

										<div class="agenda-meta small">

											<div class="agenda-row">
												<i class="fa fa-map-marker"></i>
												<span><?php echo e($tgd['tempat']); ?></span>
											</div>
											
											<div class="agenda-row">
												<i class="fa fa-calendar"></i>
												<span><?php echo e($rentang_tgl); ?></span>
											</div>

											<div class="agenda-row">
												<i class="fa fa-clock-o"></i>
												<span>Pukul <?php echo e($tgd['jam']); ?></span>
											</div>

											<div class="agenda-row">
												<i class="fa fa-user"></i>
												<span><?php echo e($tgd['pengirim']); ?></span>
											</div>
										</div>

										<div class="agenda-desc small">
											<?php echo $isi_agenda; ?>
										</div>
									</div>

								</div>
							</article>
						</div>

				          <?php
					 }
					
				  $jmldataRow = querydb("SELECT COUNT(*) AS jml FROM agenda");
				  $jmldata    = $jmldataRow ? (int)$jmldataRow->fetch_assoc()['jml'] : 0;
				  $jmlhalaman = $p->jumlahHalaman($jmldata, $batas);
				  $currentPage = clamp_page($_GET['halagenda'] ?? 1);
				  $linkHalaman = $p->navHalaman($currentPage, $jmlhalaman);
				  echo "<div class='col-md-12 col-sm-12 col-xs-12 text-center'>
							<div class='row' align='center'><center><ul class='pagination'>$linkHalaman</ul></center></div></div>";
				?>	
				</div>
			</div>
			<div class="col-md-4 right-column no-padding">
			<?php include "sidebarkanan.php"; ?>
			</div>
		</div>
	</div>
</section>
<?php             
}
// MODUL DETAIL PENGUMUMAN
elseif ($module=='detailpengumuman'){
	 // Sanitize and validate ID
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        echo '<section class="site-content"><div class="container">
                <p>Pengumuman tidak ditemukan.</p>
              </div></section>';
    } else {

        // Use prepared statement
        $detail = querydb_prepared(
            "SELECT pengumuman.*, users.nama_lengkap 
             FROM pengumuman 
             LEFT JOIN users ON pengumuman.username = users.username
             WHERE id_pengumuman = ?",
            "i",
            [$id]
        );

        $d = $detail ? $detail->fetch_array() : null;

        if (!$d) {
            echo '<section class="site-content"><div class="container">
                    <p>Pengumuman tidak ditemukan.</p>
                  </div></section>';
        } else {
            $tgl_posting = tgl_indo($d['tgl_posting'] ?? '');

            // SAFE OUTPUT
            $judul   = e($d['judul'] ?? '');
            $penulis = e($d['nama_lengkap'] ?? '');
            $gambar  = e($d['gambar'] ?? '');
?>
<section class="site-content padding-bottom-0 margin-top-15">
	<div class="container">
		<div class="row">
			<div class="col-md-8 left-column no-padding">
				<div class="breadcrumb-wrapper bg-medium">
					<div class="container-fluid">
						<div class="row">
							<div class="col-xs-12">
								<ol class="breadcrumb">
									<li><a href="./">Beranda</a></li>
									<li><a href="#">Pengumuman</a></li>
								</ol>
							</div> <!-- .col-md-12 -->
						</div> <!-- .row -->
					</div> <!-- .container -->
				</div>
				<h1 class="margin-top-15"><?php echo $judul; ?></h1>
				<ul class="list-inline small font-weight-600 myriadpro">
					<li><i class="fa fa-calendar"></i> <?php echo e($tgl_posting); ?></li>
					<li><i class="fa fa-user"></i> <?php echo $penulis; ?></li>
				</ul>
				
				<article class="news margin-bottom-15">
					<div class="divider-light"></div>
					<div style="text-align: justify;"><p><?php echo $d['isi_pengumuman']; ?></p></div>
					<div class="clearfix"></div>
				</article>

		<?php
		$shareUrl = $tiden['alamat_website'] . "/baca-pengumuman-{$d['id_pengumuman']}-" . rawurlencode($d['judul_seo'] ?? '') . ".html";
		echo render_share_buttons($shareUrl, $d['judul']);
		?>

		<ul class="list-inline small font-weight-600 myriadpro">
			<li><i class="fa fa-calendar"></i> <?php echo e($tgl_posting); ?></li>
			<li><i class="fa fa-user"></i> <?php echo $penulis; ?></li>
		</ul>

		<div class="divider-light"></div>
				<div id="disqus_thread"></div>
		<div class="clearfix margin-bottom-15"></div>
		
			</div>

			<div class="col-md-4 right-column no-padding">
				<?php include "sidebarkanan.php"; ?>
			</div>
		</div>
	</div>
</section>
<?php
		}
	}
}
// Modul semua download
elseif ($module=='semuadownload'){
?>
<section class="site-content">
	<div class="container">
		<div class="row">
			<div class="col-md-8 padding-bottom-20 left-column no-padding">
				<div class="breadcrumb-wrapper bg-medium margin-bottom-20">
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-6 col-sm-6 col-xs-12">
								<h1 class="breadcrumb-page-title">Direktori Download</h1>
							</div> <!-- .col-md-6 -->
							<div class="col-md-6 col-sm-6 col-xs-12">
								<ol class="breadcrumb">
									<li><a href="./">Beranda</a></li>
									<li>Direktori Download</li>
								</ol>
							</div> <!-- .col-md-6 -->
						</div> <!-- .row -->
					</div> <!-- .container -->
				</div>
				<div class="row">
				<?php
				  $p      = new Paging5;
				  $batas  = 10;
				  $posisi = $p->cariPosisi($batas);
				  // Tampilkan semua download
				 	$stmt = querydb_prepared(
												"SELECT id_download, judul, nama_file, hits
												FROM download
												ORDER BY id_download DESC
												LIMIT ?, ?",
												"ii",
												[(int)$posisi, (int)$batas]
											);
				    while ($row = $stmt->fetch_assoc()):
					$id        = (int)$row['id_download'];
					$judul     = e($row['judul']);
					$nama_file = $row['nama_file'];          // raw
					$hits      = (int)$row['hits'];

					$downloadurl = 'downlot.php?file=' . rawurlencode($nama_file);
                ?>
                    <div class="col-md-12 col-sm-12 col-xs-12 masonry-grid-item no-padding">
                        <article class="content-box box-img bg-light box-clickable media">
                            <div class="box-body media">
                                <div class="media-left">
                                    <div class="event-date margin-bottom-5">
                                        <img src="images/attachment.jpg" width="100%" />
                                    </div>
                                </div>
                                <div class="media-body">
                                    <h5><b><?php echo $judul; ?></b></h5>
                                    <ul class="list-inline small">
                                        <li><b><a href="<?php echo safe_url($downloadurl); ?>">
                                            <i class="fa fa-download"></i>
                                            <?php echo " Telah di download sebanyak $hits kali"; ?>
                                        </a></b></li>
                                    </ul>
                                </div>
                            </div>
                        </article>
                    </div>
					<?php endwhile; ?>
                    <?php
					$stmt->free();

					// paging
					$jmldata     = querydb("SELECT COUNT(*) AS jml FROM download");
					$totalRow    = $jmldata ? $jmldata->fetch_assoc() : ['jml' => 0];
					$jmlhalaman  = $p->jumlahHalaman((int)$totalRow['jml'], $batas);

					$currentPage = clamp_page($_GET['haldownload'] ?? 1);
					$linkHalaman = $p->navHalaman($currentPage, $jmlhalaman);
					echo "<div class='col-md-12 col-sm-12 col-xs-12 text-center'>
							<div class='row' align='center'><center><ul class='pagination'>$linkHalaman</ul></center></div></div>";
					?>	
				</div>
			</div>
			<div class="col-md-4 right-column no-padding">
			<?php include "sidebarkanan.php"; ?>
			</div>
		</div>
	</div>
</section>
<?php             
}
// Modul Detail Album (Lihat Gallery)
elseif ($module=='detailalbum'){
	$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

	if ($id < 1) {
		echo "<h3><center>Album tidak ditemukan.</center></h3>";
	} else {
		$ambilalbum = querydb_prepared("SELECT * FROM album WHERE id_album = ?", "i", [$id]);
		$dalb = $ambilalbum ? $ambilalbum->fetch_array() : null;

		if (!$dalb) {
			echo "<h3><center>Album tidak ditemukan.</center></h3>";
		} else {
?>
<section class="site-content">
	<div class="container">
		<div class="row">
			<div class="col-md-8 col-lg-8 left-column no-padding">
				<div class="breadcrumb-wrapper">
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-6 col-sm-6 col-xs-12">
								<h1 class="breadcrumb-page-title hidden-xs hidden-sm"></h1>
							</div>
							<div class="col-md-6 col-sm-6 col-xs-12">
								<ol class="breadcrumb">
									<li><a href="./">Beranda</a></li>
									<li><a href="#">Foto</a></li>
								</ol>
							</div>
						</div>
					</div>
				</div>

				<h1 class="margin-top-15"><?php echo e($dalb['jdl_album'] ?? ''); ?></h1>
				<style type="text/css">
					img.gambarnya { cursor: zoom-in; }
				</style>

				<div class="modal fade" id="enlargeImageModal" tabindex="-1" role="dialog" aria-labelledby="enlargeImageModal" aria-hidden="true">
				    <div class="modal-dialog modal-lg" role="document">
				      <div class="modal-content">
				        <div class="modal-header">
				          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">A-</span></button>
				        </div>
				        <div class="modal-body">
				          <img src="#" class="enlargeImageModalSource" style="width: 100%;">
				        </div>
				      </div>
				    </div>
				</div>

				<article id="post-88467" class="news margin-bottom-0">
					<div class="row">
						<?php 
						  $p      = new Paging6;
						  $batas  = 10;
						  $posisi = $p->cariPosisi($batas);

						  $g = querydb_prepared(
								"SELECT gbr_gallery, jdl_gallery, keterangan
								 FROM gallery
								 WHERE id_album = ?
								 ORDER BY id_gallery DESC
								 LIMIT ?, ?",
								"iii",
								[$id, (int)$posisi, (int)$batas]
						  );

  						  $ada = $g ? $g->num_rows : 0;
  						  if ($ada > 0) {
  						  while ($w = $g->fetch_array()) {
							$foto  = e($w['gbr_gallery'] ?? '');
							$judul = e($w['jdl_gallery'] ?? '');
							$ket   = e($w['keterangan'] ?? '');
						?>
						<div class="col-md-6">
							<figure class="wp-caption margin-bottom-15">
								<img style="width: 400px;height: 210px;" class="gambarnya size-medium img-responsive img-thumbnail" src="img_galeri/<?php echo $foto; ?>" alt="<?php echo $judul; ?>" title="<?php echo $ket; ?>"/>
							</figure>
						</div>						
						<?php }
						}	
						else{
							echo "<h3><center>Belum ada foto pada gallery ini</center></h3>";
						}
							$jmldataStmt = querydb_prepared("SELECT COUNT(*) AS jml FROM gallery WHERE id_album = ?", "i", [$id]);
							$jmldataRow  = $jmldataStmt ? $jmldataStmt->fetch_assoc() : ['jml' => 0];
						  	$jmlhalaman  = $p->jumlahHalaman((int)$jmldataRow['jml'], $batas);

							$currentPage = clamp_page($_GET['halgaleri'] ?? 1);
						  	$linkHalaman = $p->navHalaman($currentPage, $jmlhalaman);	
						?>					
				</div><!-- .row -->
				
				<div class="row" align="center">
      				<ul class="pagination"><?php echo $linkHalaman; ?></ul>
      			</div>

					<div class="divider-light"></div>
					<div id="disqus_thread"></div>
				</article>
			</div>
			<div class="col-md-4 right-column no-padding">
			<?php include "sidebarkanan.php"; ?>
			</div>
		</div>
	</div>
</section>
<?php
		}
	}
}
// Modul semua album
elseif ($module=='semuaalbum'){
 ?>
<section class="site-content">
	<div class="container">
		<div class="row">

			<div class="col-md-8 padding-bottom-20 left-column no-padding">
				<div class="breadcrumb-wrapper bg-medium margin-bottom-20">
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-6 col-sm-6 col-xs-12">
								<h1 class="breadcrumb-page-title">Direktori Foto</h1>
							</div>
							<div class="col-md-6 col-sm-6 col-xs-12">
								<ol class="breadcrumb">
									<li><a href="index.html">Beranda</a></li>
									<li>Foto</li>
								</ol>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
				<?php
				$p      = new PagingAlbum;
				$batas  = 7;
				$posisi = $p->cariPosisi($batas);
				$a = querydb_prepared(
					"SELECT jdl_album, album.id_album, gbr_album, album_seo,  
                  COUNT(gallery.id_gallery) as jumlah 
                  FROM album LEFT JOIN gallery 
                  ON album.id_album=gallery.id_album 
                  WHERE album.aktif='Y'  
                  GROUP BY jdl_album
                  LIMIT ?, ?",
					"ii",
					[(int)$posisi, (int)$batas]
				);
				if ($a) {
				while ($w = $a->fetch_array()) {
					$albumId    = (int)($w['id_album'] ?? 0);
					$albumSlug  = rawurlencode($w['album_seo'] ?? '');
					$judulAlbum = e($w['jdl_album'] ?? '');
					$coverAlbum = e($w['gbr_album'] ?? '');
					$jumlahFoto = (int)($w['jumlah'] ?? 0);
				?>				
				<div class="col-md-12 col-sm-12 col-xs-12 masonry-grid-item no-padding">

					<article class="content-box box-img bg-light box-clickable media">
						<div class="box-body media">
							<div class="media-left visible-xs no-padding margin-bottom-10">
								<img class="img-responsive media-object borderthumb" src="img_album/small_<?php echo $coverAlbum; ?>" alt="<?php echo $judulAlbum; ?>" style="width: 100%;height: auto; ">
							</div>
							<div style="float:left; margin-right: 10px;" class="media-left hidden-xs">
								<img class="media-object borderthumb" src="img_album/small_<?php echo $coverAlbum; ?>" alt="<?php echo $judulAlbum; ?>" width="190" height="120">
							</div>
							<div class="media-body">
							<h3>
								<a href="lihat-foto-<?php echo $albumId . '-' . $albumSlug; ?>.html"><?php echo $judulAlbum; ?></a>
							</h3>
							<ul class="list-inline small">
									<i class="fa fa-images"></i> - Jumlah Foto: <span class="label label-default"><?php echo $jumlahFoto; ?></span>
								</li>
								<li style="margin-top: 5px;">
		                            <a href="#">
		                            	<span></span>
		                            </a>
								</li>
							</ul>
							</div>
						</div>
					</article>
				</div>
				<?php }
				}
					$jmldataRow = querydb("SELECT COUNT(*) AS jml FROM album");
					$totalAlbum = $jmldataRow ? $jmldataRow->fetch_assoc() : ['jml' => 0];
					$jmlhalaman = $p->jumlahHalaman((int)$totalAlbum['jml'], $batas);

					$currentPage = clamp_page($_GET['halalbum'] ?? 1);
					$linkHalaman = $p->navHalaman($currentPage, $jmlhalaman);	
				?>
							
				<div class="col-md-12 col-sm-12 col-xs-12 text-center">
					<div class="row" align="center">
      					<ul class="pagination"><?php echo $linkHalaman; ?></ul>
      				</div>
				</div>

				</div>
			</div>
			
			<div class="col-md-4 right-column no-padding">
				<?php include "sidebarkanan.php"; ?>
			</div>

		</div>
	</div>
</section>
 <?php           
}
// Modul hubungi kami
elseif ($module=='hubungikami'){
    // Siapkan session untuk captcha
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['contact_csrf'])) {
        $_SESSION['contact_csrf'] = bin2hex(random_bytes(16));
    }
    $contactCsrfToken = $_SESSION['contact_csrf'];

    $errorMessage   = '';
    $successMessage = '';

    // Default form data (supaya sticky kalau error)
    $formData = [
        'nama'   => '',
        'email'  => '',
        'subjek' => '',
        'pesan'  => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ambil data kiriman form dengan aman
        $nama   = trim((string)($_POST['nama']   ?? ''));
        $email  = trim((string)($_POST['email']  ?? ''));
        $subjek = trim((string)($_POST['subjek'] ?? ''));
        $pesan  = trim((string)($_POST['pesan']  ?? ''));
        $kode   = (string)($_POST['kode']        ?? '');
        $postedContactToken  = (string)($_POST['csrf_token'] ?? '');
        $sessionContactToken = (string)($_SESSION['contact_csrf'] ?? '');

        $formData = [
            'nama'   => $nama,
            'email'  => $email,
            'subjek' => $subjek,
            'pesan'  => $pesan,
        ];

        // Validasi berurutan
        if ($postedContactToken === '' || $sessionContactToken === '' || !hash_equals($sessionContactToken, $postedContactToken)) {
            $errorMessage = "Token tidak valid. Silakan muat ulang halaman.";
        } elseif ($kode != ($_SESSION['captcha_session'] ?? '')) {
            $errorMessage = "Kode keamanan yang Anda masukkan salah. Silakan ulangi kembali.";
        } elseif ($nama === '' || $email === '' || $subjek === '' || $pesan === '') {
            $errorMessage = "Semua field wajib diisi. Silakan lengkapi data Anda.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Alamat email tidak valid. Silakan periksa kembali.";
        } else {
            // Batasi panjang field
            $nama   = mb_substr($nama,   0, 100);
            $email  = mb_substr($email,  0, 150);
            $subjek = mb_substr($subjek, 0, 150);
            $pesan  = mb_substr($pesan,  0, 2000);

            $tgl_sekarang = date("Y-m-d");

            $ok = exec_prepared(
                "INSERT INTO hubungi (nama_pengirim, email, subjek, pesan, tanggal) VALUES (?, ?, ?, ?, ?)",
                "sssss",
                [$nama, $email, $subjek, $pesan, $tgl_sekarang]
            );

            if ($ok) {
                $successMessage = "Terima kasih <strong>".htmlspecialchars($nama, ENT_QUOTES, 'UTF-8')."</strong>, pesan Anda sudah kami terima.";
                // Kosongkan form setelah sukses
                $formData = [
                    'nama'   => '',
                    'email'  => '',
                    'subjek' => '',
                    'pesan'  => '',
                ];
            } else {
                $errorMessage = "Terjadi kesalahan saat menyimpan pesan. Silakan coba beberapa saat lagi.";
            }
        }
    }

    // Ganti token untuk render form berikutnya
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(16));
    $contactCsrfToken = $_SESSION['contact_csrf'];

    // Siapkan nilai aman untuk ditampilkan kembali di form
    $captchaUrl = 'captcha.php?t=' . time();
    $namaSafe   = e($formData['nama']);
    $emailSafe  = e($formData['email']);
    $subjekSafe = e($formData['subjek']);
    $pesanSafe  = e($formData['pesan']);
?>
<section class="site-content">
    <div class="container">
        <div class="row">

            <div class="col-md-8 padding-bottom-20 left-column no-padding">
                <div class="breadcrumb-wrapper bg-medium margin-bottom-20">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <h1 class="breadcrumb-page-title">Contact Us</h1>
                            </div> <!-- .col-md-6 -->
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <ol class="breadcrumb">
                                    <li><a href="index.html">Beranda</a></li>
                                    <li>Contact Us</li>
                                </ol>
                            </div> <!-- .col-md-6 -->
                        </div> <!-- .row -->
                    </div> <!-- .container -->
                </div> <!-- .breadcrumb-wrapper -->

                <div class="row">
                    <div class="col-md-12 col-sm-12 no-padding">
                        <div class="well">
                            <?php if ($successMessage !== ''): ?>
                                <p><?php echo $successMessage; ?></p>
                            <?php else: ?>
                                <?php if ($errorMessage !== ''): ?>
                                    <p><?php echo e($errorMessage); ?></p>
                                <?php endif; ?>

                                <form method="post" action="hubungi-kami.html">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($contactCsrfToken); ?>">
                                    <div class="col-md-12 col-xs-12">
                                        <label>Nama Anda</label>
                                        <input type="text" name="nama" class="form-control" id="nama" value="<?php echo $namaSafe; ?>">&nbsp;
                                    </div>
                                    <div class="col-md-12 col-xs-12">
                                        <label>Email Anda</label>
                                        <input type="text" name="email" class="form-control" id="email" value="<?php echo $emailSafe; ?>">&nbsp;
                                    </div>
                                    <div class="col-md-12 col-xs-12">
                                        <label>Subjek</label>
                                        <input type="text" name="subjek" class="form-control" id="subjek" value="<?php echo $subjekSafe; ?>">&nbsp;
                                    </div>
                                    <div class="col-md-12 col-xs-12">
                                        <label>Pesan</label>
                                        <textarea class="form-control" name="pesan" id="pesan"><?php echo $pesanSafe; ?></textarea>&nbsp;
                                    </div>
                                    <div class="col-md-12 col-xs-12">
                                        <label>Kode keamanan</label><br>

                                        <div class="form-group">
                                            <img src="<?php echo e($captchaUrl); ?>"
                                                 id="captchaImage"
                                                 alt="Kode keamanan"
                                                 style="max-width: 100%; width: 20%; display: inline-block; vertical-align: middle;">

                                            <button type="button"
                                                    class="btn btn-default btn-sm"
                                                    style="margin-left: 10px; vertical-align: middle;"
                                                    aria-label="Ganti kode"
                                                    onclick="var img = document.getElementById('captchaImage'); img.src = 'captcha.php?' + new Date().getTime(); return false;">
                                                <i class="fa fa-refresh" aria-hidden="true"></i>
                                            </button>
                                        </div>

                                        <label>Masukkan 6 kode di atas</label>&nbsp;
                                        <input type="text"
                                               name="kode"
                                               size="6"
                                               maxlength="6"
                                               style="width: 30%;"
                                               class="form-control"
                                               id="kode_captcha">&nbsp;
                                    </div>

                                    <hr />
                                    <input value="Kirim Pesan" name="kirim" id="submit" type="submit" class="btn btn-primary btn-lg btn-block"/>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div> <!-- .col-md-12 -->
                </div> <!-- .row -->
            </div> <!-- .col-md-8 -->

            <div class="col-md-4 right-column no-padding">
                <?php include "sidebarkanan.php"; ?>
            </div> <!-- .col-md-4 -->

        </div> <!-- .row -->
    </div> <!-- .container -->
</section>
<?php
}

// Modul hubungi aksi (legacy) ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“ redirect ke hubungi-kami
elseif ($module=='hubungiaksi'){
    // Untuk kompatibilitas jika masih ada link lama ke hubungi-aksi.html
    header("Location: hubungi-kami.html");
    exit;
}

// Modul hasil pencarian berita 
elseif ($module=='hasilcari'){
?>
<section class="site-content">
	<div class="container">
		<div class="row">

			<div class="col-md-8 padding-bottom-20 left-column no-padding">
				<div class="breadcrumb-wrapper bg-medium margin-bottom-20">
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-6 col-sm-6 col-xs-12">
								<h1 class="breadcrumb-page-title">Hasil Pencarian</h1>
							</div> <!-- .col-md-6 -->
							<div class="col-md-6 col-sm-6 col-xs-12">
								<ol class="breadcrumb">
									<li><a href="index.html">Beranda</a></li>
									<li>Hasil Pencarian</li>
								</ol>
							</div> <!-- .col-md-6 -->
						</div> <!-- .row -->
					</div> <!-- .container -->
				</div> <!-- .breadcrumb-wrapper -->

				<div class="row">
					<div class="col-md-12 col-sm-12 no-padding">
						<div class="well">
<?php
  // Ambil kata kunci dari form
    $kata = trim((string)($_POST['kata'] ?? ''));
    // Simpan versi bersih untuk ditampilkan di HTML
    $kata_tampil = htmlspecialchars($kata, ENT_QUOTES, 'UTF-8');

    // Normalisasi spasi dan pecah jadi kata-kata
    $pisah_kata = preg_split('/\s+/', $kata, -1, PREG_SPLIT_NO_EMPTY);

    // Jika tidak ada kata kunci, langsung tampilkan pesan dan hentikan query
    if (!$pisah_kata) {
        $hasil  = null;
        $ketemu = 0;
    } else {
        // Bangun query pencarian dengan prepared statement
        // (judul LIKE ? OR isi_berita LIKE ?) untuk setiap kata
        $conditions = [];
        $params     = [];
        $types      = '';

        foreach ($pisah_kata as $kw) {
            // batasi panjang keyword biar tidak berlebihan
            $kw = mb_substr($kw, 0, 100);
            $conditions[] = "(judul LIKE ? OR isi_berita LIKE ?)";
            $like = '%'.$kw.'%';
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }

        $sql = "
            SELECT * FROM berita
            WHERE ".implode(' OR ', $conditions)."
            ORDER BY id_berita DESC
            LIMIT 7
        ";

        // Gunakan helper prepared (sesuai pola yang sudah kita pakai di halaman statis)
        $hasil = querydb_prepared($sql, $types, $params);
        $ketemu = $hasil ? $hasil->num_rows : 0;
    }

  if ($ketemu > 0){
	
    echo "<p>Ditemukan <b>$ketemu</b> berita dengan kata <font style='background-color:#00FFFF'><b>$kata_tampil</b></font> : </p>"; 
    while ($t = $hasil->fetch_array()) {
		$id      = (int)($t['id_berita'] ?? 0);
		$slug    = $t['judul_seo'] ?? '';
		$judul   = $t['judul'] ?? '';

		// Escape untuk tampilan judul
		$judul_safe = e($judul);

		// Pastikan slug aman dalam URL. Kalau slug kamu biasa pakai huruf/angka/dash,
		// cukup pakai rawurlencode dan tetap kita bungkus dalam href="...".
		$slug_safe = rawurlencode($slug);

		$url = "baca-berita-{$id}-{$slug_safe}.html";

		// Tampilkan hanya sebagian isi berita (sama seperti sebelumnya)
		$isi_berita = htmlentities(strip_tags($t['isi_berita'])); 
		$isi        = substr($isi_berita, 0, 250);
		$isi        = substr($isi_berita, 0, strrpos($isi, " "));

		echo "<table>
				<tr>
					<td>
						<span class='judul'>
							<a href=\"{$url}\">{$judul_safe}</a>
						</span><br />
						{$isi} .
						<a href=\"{$url}\">Selengkapnya</a>
						<br />
					</td>
				</tr>
			</table>
			<hr color=\"#CCC\" noshade=\"noshade\" />";
	}
                                                          
  }
  else{
    echo "<p></p><p align=center>Tidak ditemukan berita dengan kata <b>$kata_tampil</b></p>";
  }
?>
		</div>
					</div> 
				</div> <!-- .row -->
			</div> <!-- .col-md-9 -->

			<div class="col-md-4 right-column no-padding">
				<?php include "sidebarkanan.php"; ?>
			</div> <!-- .col-md-4 -->


		</div> <!-- .row -->
	</div> <!-- .container -->
</section>
<?php          
}

else{
	echo "<h1><center>HALAMAN TIDAK DITEMUKAN ATAU MODUL BELUM TERSEDIA</center></h1>";
}
 ?>
