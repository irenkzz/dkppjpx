<?php
/*
SQL migration for approval workflow (revisions are approved before touching live tables).

CREATE TABLE berita_revisions (
  rev_id INT AUTO_INCREMENT PRIMARY KEY,
  berita_id INT NULL, -- NULL berarti konten baru
  status VARCHAR(16) NOT NULL DEFAULT 'PENDING', -- PENDING, APPROVED, REJECTED
  created_by VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL,
  approved_by VARCHAR(50) NULL,
  approved_at DATETIME NULL,
  note VARCHAR(255) NULL,
  judul VARCHAR(255) NOT NULL,
  judul_seo VARCHAR(255) NOT NULL,
  id_kategori INT NOT NULL,
  username VARCHAR(50) NOT NULL,
  isi_berita TEXT NOT NULL,
  hari VARCHAR(20) NOT NULL,
  tanggal VARCHAR(20) NOT NULL,
  jam VARCHAR(20) NOT NULL,
  tag VARCHAR(255) NULL,
  gambar VARCHAR(255) NULL,
  INDEX(status),
  INDEX(berita_id),
  INDEX(created_by)
);

CREATE TABLE pengumuman_revisions (
  rev_id INT AUTO_INCREMENT PRIMARY KEY,
  pengumuman_id INT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'PENDING',
  created_by VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL,
  approved_by VARCHAR(50) NULL,
  approved_at DATETIME NULL,
  note VARCHAR(255) NULL,
  judul VARCHAR(255) NOT NULL,
  judul_seo VARCHAR(255) NOT NULL,
  isi_pengumuman TEXT NOT NULL,
  tgl_posting DATE NOT NULL,
  username VARCHAR(50) NOT NULL,
  INDEX(status),
  INDEX(pengumuman_id),
  INDEX(created_by)
);

CREATE TABLE agenda_revisions (
  rev_id INT AUTO_INCREMENT PRIMARY KEY,
  agenda_id INT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'PENDING',
  created_by VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL,
  approved_by VARCHAR(50) NULL,
  approved_at DATETIME NULL,
  note VARCHAR(255) NULL,
  tema VARCHAR(255) NOT NULL,
  tema_seo VARCHAR(255) NOT NULL,
  isi_agenda TEXT NOT NULL,
  tempat VARCHAR(255) NOT NULL,
  tgl_mulai DATE NOT NULL,
  tgl_selesai DATE NOT NULL,
  tgl_posting DATE NOT NULL,
  jam VARCHAR(50) NOT NULL,
  pengirim VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL,
  gambar VARCHAR(255) NULL,
  INDEX(status),
  INDEX(agenda_id),
  INDEX(created_by)
);
*/
