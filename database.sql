CREATE DATABASE IF NOT EXISTS absensi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE absensi;
CREATE TABLE IF NOT EXISTS users(
 id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL, username VARCHAR(50) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL, role ENUM('admin','staff') NOT NULL DEFAULT 'staff', aktif TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS settings(
 id INT PRIMARY KEY, nama_kantor VARCHAR(100), lat DECIMAL(10,7), lng DECIMAL(10,7), radius INT, jam_masuk TIME);
INSERT IGNORE INTO settings VALUES(1,'Kantor Pusat',-0.0263000,109.3425000,100,'08:00:00');
CREATE TABLE IF NOT EXISTS absensi(
 id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, tanggal DATE NOT NULL,
 jam_masuk TIME, lat_masuk DECIMAL(10,7), lng_masuk DECIMAL(10,7), jarak_masuk INT, foto_masuk VARCHAR(60),
 jam_pulang TIME, lat_pulang DECIMAL(10,7), lng_pulang DECIMAL(10,7), jarak_pulang INT, foto_pulang VARCHAR(60),
 status ENUM('hadir','terlambat') NOT NULL DEFAULT 'hadir',
 UNIQUE KEY uq(user_id,tanggal), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);
