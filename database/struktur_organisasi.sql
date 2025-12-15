-- Struktur Organisasi module
-- Jalankan file ini di database aplikasi (mis. dkppjpx)

CREATE TABLE IF NOT EXISTS `struktur_posisi` (
  `id_posisi` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `nama_posisi` varchar(150) NOT NULL,
  `kode_posisi` varchar(50) DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT '0',
  `level` int(11) NOT NULL DEFAULT '0',
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`id_posisi`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `struktur_pejabat` (
  `id_pejabat` int(11) NOT NULL AUTO_INCREMENT,
  `id_posisi` int(11) NOT NULL,
  `nama_pejabat` varchar(150) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tmt_mulai` date DEFAULT NULL,
  `tmt_selesai` date DEFAULT NULL,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`id_pejabat`),
  KEY `idx_posisi` (`id_posisi`),
  KEY `idx_aktif` (`aktif`),
  CONSTRAINT `fk_pejabat_posisi` FOREIGN KEY (`id_posisi`) REFERENCES `struktur_posisi` (`id_posisi`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
