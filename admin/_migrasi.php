<?php
// Menambah kolom koreksi otomatis (hanya sekali, jika belum ada)
try{if(!$db->query("SHOW COLUMNS FROM absensi LIKE 'dikoreksi'")->fetch())$db->exec("ALTER TABLE absensi ADD dikoreksi TINYINT(1) NOT NULL DEFAULT 0, ADD catatan VARCHAR(255) NULL");}
catch(PDOException $e){die('Gagal menambah kolom koreksi. Jalankan manual di phpMyAdmin: ALTER TABLE absensi ADD dikoreksi TINYINT(1) NOT NULL DEFAULT 0, ADD catatan VARCHAR(255) NULL; ('.$e->getMessage().')');}
